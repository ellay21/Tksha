<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../../config/db.php';
$pdo = require '../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

$email = $data['email'];
$password = $data['password'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

// Generate JWT token
$issued_at = time();
$expiration = $issued_at + (60 * 60 * 24 * 7); // Token valid for 7 days
$payload = [
    'iat' => $issued_at,
    'exp' => $expiration,
    'user_id' => $user['id']
];

$secret_key = 'ellay21';

$jwt = generateJWT($payload, $secret_key);

// Get user interests
$stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
$stmt->execute([$user['id']]);
$interests = $stmt->fetchAll(PDO::FETCH_COLUMN);

$userData = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'gender' => $user['gender'],
    'age' => $user['age'],
    'profile_pic' => $user['profile_pic'],
    'profile_completed' => $user['profile_completed'] == 1,
    'interests' => $interests
];

echo json_encode([
    'success' => true,
    'token' => $jwt,
    'user' => $userData
]);

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generateJWT($payload, $secret_key) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $header = base64url_encode($header);
    
    $payload = json_encode($payload);
    $payload = base64url_encode($payload);
    
    $signature = hash_hmac('sha256', "$header.$payload", $secret_key, true);
    $signature = base64url_encode($signature);
    
    $token = "$header.$payload.$signature";
    
    return $token;
}

?>