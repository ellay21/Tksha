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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure data is not empty
if(!empty($data->name)) {
    // Set user property values
    $user->name = $data->name;
    $user->bio = isset($data->bio) ? $data->bio : $user->bio;
    
    // Update the user profile
    if($user->updateProfile()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        // Return updated user data
        echo json_encode(array(
            "message" => "Profile was updated.",
            "user" => array(
                "id" => $user->id,
                "name" => $user->name,
                "email" => $user->email,
                "gender" => $user->gender,
                "bio" => $user->bio,
                "profile_picture" => $user->profile_picture,
                "lat" => $user->lat,
                "lng" => $user->lng,
                "radius_preference" => $user->radius_preference
            )
        ));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("message" => "Unable to update profile."));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "Unable to update profile. Name is required."));
}
?>
