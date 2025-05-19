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
include_once '../utils/location.php';
require_once __DIR__ . '/../config/cors.php';
handleCORS();
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
    http_response_code(401);
    echo json_encode(["message" => "Access denied."]);
    exit;
}

$user->id = $user_id;

try {
    if (!$user->ensureCompleteProfile()) {
        throw new Exception("User profile is incomplete for matching");
    }

    if (!LocationUtil::validateCoordinates($user->lat, $user->lng)) {
        http_response_code(400);
        echo json_encode(["message" => "Your location data is invalid"]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["message" => $e->getMessage()]);
    exit;
}

// Get potential matches
$stmt = $user->getPotentialMatches();
$num = $stmt->rowCount();

if($num > 0) {
    $matches_arr = [];

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        // Get user activities
        $activity_stmt = $activity->getUserActivities($id);
        $activities = [];

        while($activity_row = $activity_stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = [
                "id" => $activity_row['id'],
                "name" => $activity_row['name']
            ];
        }

        $matches_arr[] = [
            "id" => $id,
            "name" => $name,
            "gender" => $gender,
            "lat" => $lat,
            "lng" => $lng,
            "distance" => round($distance, 1),
            "shared_activities" => $shared_activities,
            "activities" => $activities
        ];
    }

    http_response_code(200);
    echo json_encode($matches_arr);
} else {
    http_response_code(404);
    echo json_encode(["message" => "No potential matches found."]);
}
?>
