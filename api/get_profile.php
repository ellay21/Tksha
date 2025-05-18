<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once '../config/database.php';
include_once '../models/User.php';
include_once '../models/Activity.php';
include_once '../utils/jwt.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);
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

// Get user ID from URL
$profile_id = isset($_GET['id']) ? $_GET['id'] : $user_id;

// Set user ID
$user->id = $profile_id;

// Get user data
if($user->readOne()) {
    // Get user activities
    $activity_stmt = $activity->getUserActivities($profile_id);
    $activities = array();
    
    while($activity_row = $activity_stmt->fetch(PDO::FETCH_ASSOC)) {
        array_push($activities, array(
            "id" => $activity_row['id'],
            "name" => $activity_row['name']
        ));
    }
    
    // Create user array
    $user_data = array(
        "id" => $user->id,
        "name" => $user->name,
        "email" => ($profile_id == $user_id) ? $user->email : null, // Only show email to the user themselves
        "gender" => $user->gender,
        "bio" => $user->bio,
        "profile_picture" => $user->profile_picture,
        "lat" => ($profile_id == $user_id) ? $user->lat : null, // Only show exact coordinates to the user themselves
        "lng" => ($profile_id == $user_id) ? $user->lng : null,
        "radius_preference" => ($profile_id == $user_id) ? $user->radius_preference : null,
        "activities" => $activities,
        "created_at" => $user->created_at
    );
    
    // Set response code - 200 OK
    http_response_code(200);
    
    // Return user data
    echo json_encode($user_data);
} else {
    // Set response code - 404 Not found
    http_response_code(404);
    
    // Tell the user
    echo json_encode(array("message" => "User not found."));
}
?>