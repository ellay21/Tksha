<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../models/Message.php';
include_once '../utils/jwt.php';
include_once '../utils/pusher.php';
include_once '../models/UserMatch.php';
require_once __DIR__ . '/../config/cors.php';
handleCORS();
// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate message object
$message = new Message($db);
$match = new UserMatch($db);

// Get JWT from headers
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validate JWT
$jwt_handler = new JwtHandler();
$user_id = $jwt_handler->validateToken($jwt);

if(!$user_id) {
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit;
}

// Handle request methods
$request_method = $_SERVER["REQUEST_METHOD"];

switch($request_method) {
    case 'GET':
        // Get match ID from URL
        $match_id = isset($_GET['match_id']) ? $_GET['match_id'] : die();
        
        // Verify user is part of this match
        $stmt = $match->getUserMatches($user_id);
        $is_valid_match = false;
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if($row['id'] == $match_id) {
                $is_valid_match = true;
                break;
            }
        }
        
        if(!$is_valid_match) {
            http_response_code(403);
            echo json_encode(["message" => "You are not authorized to view these messages."]);
            exit;
        }
        
        // Get messages for this match
        $stmt = $message->getMatchMessages($match_id);
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $messages_arr = array();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $messages_arr[] = [
                    "id" => $row['id'],
                    "sender_id" => $row['sender_id'],
                    "sender_name" => $row['sender_name'],
                    "content" => $row['content'],
                    "sent_at" => $row['sent_at'],
                    "is_mine" => ($row['sender_id'] == $user_id)
                ];
            }
            
            http_response_code(200);
            echo json_encode($messages_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No messages found."]);
        }
        break;
        
    case 'POST':
        // Get posted data
        $data = json_decode(file_get_contents("php://input"));
        
        // Make sure data is not empty
        if(!empty($data->match_id) && !empty($data->content)) {
            // Verify user is part of this match
            $stmt = $match->getUserMatches($user_id);
            $is_valid_match = false;
            $matched_user_id = null;
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if($row['id'] == $data->match_id) {
                    $is_valid_match = true;
                    $matched_user_id = $row['matched_user_id'];
                    break;
                }
            }
            
            if(!$is_valid_match) {
                http_response_code(403);
                echo json_encode(["message" => "You are not authorized to send messages to this match."]);
                exit;
            }
            
            // Set message property values
            $message->match_id = $data->match_id;
            $message->sender_id = $user_id;
            $message->content = $data->content;
            
            // Create the message
            if($message->create()) {
                // Get user name
                $user_query = "SELECT name FROM users WHERE id = ?";
                $user_stmt = $db->prepare($user_query);
                $user_stmt->bindParam(1, $user_id);
                $user_stmt->execute();
                $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Prepare message data for Pusher
                $message_data = [
                    "id" => $message->id,
                    "sender_id" => $user_id,
                    "sender_name" => $user_row['name'],
                    "content" => $message->content,
                    "sent_at" => date('Y-m-d H:i:s')
                ];
                
                // Trigger Pusher event
                $pusher = new PusherHandler();
                $pusher->triggerNewMessage($data->match_id, $message_data);
                
                // Set response code - 201 created
                http_response_code(201);
                
                // Tell the user
                echo json_encode([
                    "message" => "Message was sent.",
                    "data" => $message_data
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to send message."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Unable to send message. Data is incomplete."]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}
?>