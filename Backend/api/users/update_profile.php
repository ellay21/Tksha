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

function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    return $headers;
}

$auth_header = getAuthorizationHeader();
$token = null;

if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$secret_key = 'ellay21';
$user_id = verifyJWT($token, $secret_key);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit();
}

if (isset($_FILES['profile_pic'])) {
    $upload_dir = '../../uploads/profile_pics/';

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = $user_id . '_' . time() . '_' . basename($_FILES['profile_pic']['name']);
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $file_path)) {
        $profile_pic_url = '../uploads/profile_pics/' . $file_name;

        $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$profile_pic_url, $user_id]);

        $stmt = $pdo->prepare("SELECT id, name, email, gender, age, profile_pic, profile_completed FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
        $stmt->execute([$user_id]);
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
            'message' => 'Profile picture updated successfully',
            'user' => $userData
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to upload profile picture']);
    }

    exit();
}

if (isset($_FILES['profile_photos'])) {
    $upload_dir = '../../uploads/profile_pics/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $photo_urls = [];
    foreach ($_FILES['profile_photos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $user_id . '_' . time() . '_' . basename($_FILES['profile_photos']['name'][$key]);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($tmp_name, $file_path)) {
            $profile_photo_url = '../uploads/profile_pics/' . $file_name;
            $stmt = $pdo->prepare("INSERT INTO profile_photos (user_id, photo_url) VALUES (?, ?)");
            $stmt->execute([$user_id, $profile_photo_url]);
            $photo_urls[] = $profile_photo_url;
        }
    }
    // Fetch all profile photos for the user
    $stmt = $pdo->prepare("SELECT photo_url FROM profile_photos WHERE user_id = ? ORDER BY uploaded_at ASC");
    $stmt->execute([$user_id]);
    $profile_photos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt = $pdo->prepare("SELECT id, name, email, gender, age, profile_completed FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $interests = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $userData = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'gender' => $user['gender'],
        'age' => $user['age'],
        'profile_photos' => $profile_photos,
        'profile_completed' => $user['profile_completed'] == 1,
        'interests' => $interests
    ];
    echo json_encode([
        'success' => true,
        'message' => 'Profile photos updated successfully',
        'user' => $userData
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$pdo->beginTransaction();

try {
    $bio = $data['bio'] ?? null;
    $location = $data['location'] ?? null;
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;
    $interests = $data['interests'] ?? [];

    $stmt = $pdo->prepare("UPDATE users SET bio = ?, location = ?, latitude = ?, longitude = ?, profile_completed = 1 WHERE id = ?");
    $stmt->execute([$bio, $location, $latitude, $longitude, $user_id]);

    $stmt = $pdo->prepare("DELETE FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);

    if (!empty($interests)) {
        $values = [];
        $placeholders = [];

        foreach ($interests as $interest) {
            $values[] = $user_id;
            $values[] = $interest;
            $placeholders[] = "(?, ?)";
        }

        $placeholders_str = implode(', ', $placeholders);
        $stmt = $pdo->prepare("INSERT INTO user_interests (user_id, interest) VALUES $placeholders_str");
        $stmt->execute($values);
    }

    $pdo->commit();

    $stmt = $pdo->prepare("SELECT id, name, email, gender, age, profile_pic, profile_completed FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
    $stmt->execute([$user_id]);
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
        'message' => 'Profile updated successfully',
        'user' => $userData
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Profile update failed: ' . $e->getMessage()]);
}

function verifyJWT($token, $secret_key)
{
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

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}
