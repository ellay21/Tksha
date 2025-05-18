<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../utils/jwt.php';
include_once '../utils/pusher.php';
//file_put_contents("debug.txt", print_r($_POST, true));

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

// Get posted data
// Sanitize and normalize keys
$clean_post = [];
foreach ($_POST as $key => $value) {
    $clean_post[trim($key)] = $value;
}

$channel_name = $clean_post['channel_name'] ?? null;
$socket_id = $clean_post['socket_id'] ?? null;


// Create Pusher handler
$pusher = new PusherHandler();

// Generate auth signature
$auth = $pusher->auth($channel_name, $socket_id, $user_id);

if($auth) {
    // Return auth signature
    echo $auth;
} else {
    // Set response code - 403 Forbidden
    http_response_code(403);
    
    // Tell the user
    echo json_encode(array("message" => "Not authorized to subscribe to this channel."));
}
?>
