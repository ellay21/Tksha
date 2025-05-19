<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../models/User.php';
include_once '../utils/jwt.php';
include_once '../utils/image.php';
require_once __DIR__ . '/../config/cors.php';
handleCORS();
// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

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

// Set user ID
$user->id = $user_id;

// Get user data first
if(!$user->readOne()) {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user
    echo json_encode(array("message" => "User not found."));
    exit;
}

// Check if file was uploaded
if(isset($_FILES['profile_picture'])) {
    // Delete old profile picture if exists
    if($user->profile_picture) {
        ImageUtil::deleteProfilePicture($user->profile_picture);
    }
    
    // Upload new profile picture
    $result = ImageUtil::uploadProfilePicture($_FILES['profile_picture'], $user_id);
    
    if(isset($result['error'])) {
        // Set response code - 400 bad request
        http_response_code(400);
        
        // Tell the user
        echo json_encode(array("message" => $result['error']));
        exit;
    }
    
    // Update user profile picture in database
    $user->profile_picture = $result['profile_picture'];
    
    if($user->updateProfilePicture()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        // Return updated user data
        echo json_encode(array(
            "message" => "Profile picture was updated.",
            "profile_picture" => $user->profile_picture,
            "thumbnail" => $result['thumbnail']
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("message" => "Unable to update profile picture in database."));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "No file uploaded."));
}
?>
