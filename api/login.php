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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure email and password are not empty
if(!empty($data->email) && !empty($data->password)) {
    // Set user email
    $user->email = $data->email;
    
    // Check if email exists and get user data
    if($user->emailExists()) {
        // Check if password is correct
        if(password_verify($data->password, $user->password)) {
            // Create JWT handler
            $jwt_handler = new JwtHandler();
            
            // Generate token
            $token = $jwt_handler->generateToken($user->id);
            
            // Set response code - 200 OK
            http_response_code(200);
            
            // Response including the token
            echo json_encode(
                array(
                    "message" => "Login successful.",
                    "token" => $token,
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
                )
            );
        } else {
            // Set response code - 401 Unauthorized
            http_response_code(401);
            
            // Tell the user login failed
            echo json_encode(array("message" => "Login failed. Incorrect password."));
        }
    } else {
        // Set response code - 401 Unauthorized
        http_response_code(401);
        
        // Tell the user login failed
        echo json_encode(array("message" => "Login failed. User not found."));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "Login failed. Email and password are required."));
}
?>