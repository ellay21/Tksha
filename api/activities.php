<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../models/Activity.php';
include_once '../utils/jwt.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate activity object
$activity = new Activity($db);

// Get JWT from headers
$headers = getallheaders();
$jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

// Validate JWT
$jwt_handler = new JwtHandler();
$user_id = $jwt_handler->validateToken($jwt);

if(!$user_id) {
    // Set response code - 401 Unauthorized
    http_response_code(401);
    
    // Tell the user
    echo json_encode(array("message" => "Access denied."));
    exit;
}

// Handle request methods
$request_method = $_SERVER["REQUEST_METHOD"];

switch($request_method) {
    case 'GET':
        // Get all activities
        $stmt = $activity->getAll();
        $num = $stmt->rowCount();
        
        if($num > 0) {
            $activities_arr = array();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                
                $activity_item = array(
                    "id" => $id,
                    "name" => $name
                );
                
                array_push($activities_arr, $activity_item);
            }
            
            // Set response code - 200 OK
            http_response_code(200);
            
            // Show activities
            echo json_encode($activities_arr);
        } else {
            // Set response code - 404 Not found
            http_response_code(404);
            
            // Tell the user no activities found
            echo json_encode(array("message" => "No activities found."));
        }
        break;
        
    case 'POST':
        // Save user activities
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->activity_ids)) {
            if($activity->saveUserActivities($user_id, $data->activity_ids)) {
                // Set response code - 200 OK
                http_response_code(200);
                
                // Tell the user
                echo json_encode(array("message" => "Activities were saved."));
            } else {
                // Set response code - 503 service unavailable
                http_response_code(503);
                
                // Tell the user
                echo json_encode(array("message" => "Unable to save activities."));
            }
        } else {
            // Set response code - 400 bad request
            http_response_code(400);
            
            // Tell the user
            echo json_encode(array("message" => "Unable to save activities. No activity IDs provided."));
        }
        break;
        
    default:
        // Set response code - 405 Method Not Allowed
        http_response_code(405);
        
        // Tell the user
        echo json_encode(array("message" => "Method not allowed."));
        break;
}
?>