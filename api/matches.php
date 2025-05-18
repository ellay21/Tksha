<?php
// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and object files
include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/UserMatch.php';
include_once __DIR__ . '/../utils/jwt.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate match object
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

// Get user matches
$stmt = $match->getUserMatches($user_id);
$num = $stmt->rowCount();

if($num > 0) {
    $matches_arr = array();
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $matches_arr[] = [
            "id" => $row['id'],
            "matched_user_id" => $row['matched_user_id'],
            "matched_user_name" => $row['matched_user_name'],
            "matched_at" => $row['matched_at']
        ];
    }
    
    http_response_code(200);
    echo json_encode($matches_arr);
} else {
    http_response_code(404);
    echo json_encode(["message" => "No matches found."]);
}
?>