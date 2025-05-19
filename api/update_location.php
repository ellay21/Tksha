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
include_once '../utils/location.php';
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

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Handle location update
if(!empty($data->lat) && !empty($data->lng)) {
    // Use provided coordinates
    $user->lat = $data->lat;
    $user->lng = $data->lng;
    
    if($user->updateLocation()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        // Tell the user
        echo json_encode(array("message" => "Location was updated."));
    } else {
        // Set response code - 503 service unavailable
        http_response_code(503);
        
        // Tell the user
        echo json_encode(array("message" => "Unable to update location."));
    }
} else if(!empty($data->address)) {
    // Get coordinates from address
    $coordinates = LocationUtil::getCoordinatesFromAddress($data->address);
    
    if($coordinates) {
        $user->lat = $coordinates['lat'];
        $user->lng = $coordinates['lng'];
        
        if($user->updateLocation()) {
            // Set response code - 200 OK
            http_response_code(200);
            
            // Tell the user
            echo json_encode(array(
                "message" => "Location was updated.",
                "lat" => $user->lat,
                "lng" => $user->lng
            ));
        } else {
            // Set response code - 503 service unavailable
            http_response_code(503);
            
            // Tell the user
            echo json_encode(array("message" => "Unable to update location."));
        }
    } else {
        // Set response code - 400 bad request
        http_response_code(400);
        
        // Tell the user
        echo json_encode(array("message" => "Could not geocode the provided address."));
    }
} else {
    // Set response code - 400 bad request
    http_response_code(400);
    
    // Tell the user
    echo json_encode(array("message" => "Unable to update location. No location data provided."));
}
?>
