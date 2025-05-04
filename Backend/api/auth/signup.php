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

if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['gender']) || !isset($data['birthdate'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

$name = $data['name'];
$email = $data['email'];
$password = $data['password'];
$gender = $data['gender'];
$birthdate = $data['birthdate'];
$age = $data['age'] ?? null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

if (!$age) {
    $birthDate = new DateTime($birthdate);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
    
    if ($age < 18) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You must be at least 18 years old to register']);
        exit();
    }
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, gender, birthdate, age, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $hashed_password, $gender, $birthdate, $age]);
    
    $user_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    // Generate JWT token
    $issued_at = time();
    $expiration = $issued_at + (60 * 60 * 24 * 7); // Token valid for 7 days
    $payload = [
        'iat' => $issued_at,
        'exp' => $expiration,
        'user_id' => $user_id
    ];
    
    $secret_key = 'ellay21'; 
    
    $jwt = generateJWT($payload, $secret_key);
    
    $userData = [
        'id' => $user_id,
        'name' => $name,
        'email' => $email,
        'gender' => $gender,
        'age' => $age,
        'profile_completed' => false,
        'interests' => []
    ];
    
    echo json_encode([
        'success' => true,
        'token' => $jwt,
        'user' => $userData
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}

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