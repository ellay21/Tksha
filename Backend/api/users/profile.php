<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

// for debugging
// $headers = getallheaders();
// file_put_contents('headers.txt', print_r($headers, true));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once '../../config/db.php';
$pdo = require '../../config/db.php';

function getAuthorizationHeader() {
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Fix case sensitivity in some environments
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    return $headers;
}

$auth_header = getAuthorizationHeader();

if (!$auth_header) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - No Authorization header']);
    exit();
}

$token = null;
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Invalid Bearer token']);
    exit();
}

$secret_key = 'ellay21'; 
$user_id = verifyJWT($token, $secret_key);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, email, gender, age, bio, location, profile_pic, profile_completed FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
$stmt->execute([$user_id]);
$interests = $stmt->fetchAll(PDO::FETCH_COLUMN);

$profileData = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'gender' => $user['gender'],
    'age' => $user['age'],
    'bio' => $user['bio'],
    'location' => $user['location'],
    'profile_pic' => $user['profile_pic'],
    'profile_completed' => $user['profile_completed'] == 1,
    'interests' => $interests
];

echo json_encode([
    'success' => true,
    'profile' => $profileData
]);

function verifyJWT($token, $secret_key) {
    $token_parts = explode('.', $token);

    if (count($token_parts) != 3) {
        return false;
    }

    list($header_encoded, $payload_encoded, $signature_provided) = $token_parts;

    $payload = json_decode(base64url_decode($payload_encoded), true);
    $expiration = $payload['exp'] ?? 0;

    if ($expiration < time()) {
        return false;
    }

    $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret_key, true);
    $signature_encoded = base64url_encode($signature);

    if (!hash_equals($signature_encoded, $signature_provided)) {
        return false;
    }

    return $payload['user_id'] ?? false;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

?>
