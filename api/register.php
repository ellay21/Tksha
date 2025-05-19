<?php
// First handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 3600");
    http_response_code(204);
    exit();
}

// Standard CORS headers for actual request
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include files
include_once '../config/database.php';
include_once '../models/User.php';
include_once '../utils/location.php';
include_once '../utils/validation.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate required fields
if(!ValidationUtil::validateRegistrationData($data)) {
    http_response_code(400);
    echo json_encode(array("message" => ValidationUtil::$lastError));
    exit;
}

// Set user properties
$user->name = $data->name;
$user->email = $data->email;
$user->password = $data->password;
$user->gender = $data->gender;
$user->radius_preference = $data->radius_preference ?? 50;

// Validate radius preference
if(!ValidationUtil::validateRadius($user->radius_preference)) {
    http_response_code(400);
    echo json_encode(array("message" => "Radius must be between 1 and 1000 km"));
    exit;
}

// Check if email exists
if($user->emailExists()) {
    http_response_code(400);
    echo json_encode(array("message" => "Email already exists."));
    exit;
}

// Handle location data
try {
    if(!empty($data->lat) && !empty($data->lng)) {
        // Validate coordinates
        if(!ValidationUtil::validateCoordinates($data->lat, $data->lng)) {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid coordinates provided"));
            exit;
        }
        $user->lat = $data->lat;
        $user->lng = $data->lng;
    } 
    else if(!empty($data->address)) {
        // Geocode address
        $coordinates = LocationUtil::getCoordinatesFromAddress($data->address);
        if(!$coordinates || !ValidationUtil::validateCoordinates($coordinates['lat'], $coordinates['lng'])) {
            http_response_code(400);
            echo json_encode(array("message" => "Could not validate address location"));
            exit;
        }
        $user->lat = $coordinates['lat'];
        $user->lng = $coordinates['lng'];
    }
    else {
        http_response_code(400);
        echo json_encode(array("message" => "Location information is required"));
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("message" => "Location processing error: " . $e->getMessage()));
    exit;
}

// Create the user
if($user->create()) {
    http_response_code(201);
    echo json_encode(array(
        "message" => "User created successfully",
        "data" => [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "location" => [
                "lat" => $user->lat,
                "lng" => $user->lng
            ],
            "radius_preference" => $user->radius_preference
        ]
    ));
} else {
    http_response_code(503);
    echo json_encode(array("message" => "Unable to create user."));
}
