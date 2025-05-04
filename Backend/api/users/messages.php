<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['match_id'])) {
        $match_id = $_GET['match_id'];

        $stmt = $pdo->prepare("
            SELECT * FROM matches 
            WHERE id = ? AND (user_id_1 = ? OR user_id_2 = ?)
        ");
        $stmt->execute([$match_id, $user_id, $user_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Match not found']);
            exit();
        }

        $other_user_id = $match['user_id_1'] == $user_id ? $match['user_id_2'] : $match['user_id_1'];

        $stmt = $pdo->prepare("SELECT id, name, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$other_user_id]);
        $other_user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE match_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$match_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'match' => $other_user,
            'messages' => $messages
        ]);
        exit();
    }
    
    $stmt = $pdo->prepare("
        SELECT m.id, m.created_at,
            CASE 
                WHEN m.user_id_1 = ? THEN m.user_id_2
                ELSE m.user_id_1
            END AS other_user_id
        FROM matches m
        WHERE m.user_id_1 = ? OR m.user_id_2 = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($matches)) {
        echo json_encode(['success' => true, 'matches' => []]);
        exit();
    }

    $match_details = [];
    
    foreach ($matches as $match) {

        $stmt = $pdo->prepare("SELECT id, name, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$match['other_user_id']]);
        $other_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT message, created_at FROM messages 
            WHERE match_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$match['id']]);
        $last_message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM messages 
            WHERE match_id = ? AND sender_id = ? AND is_read = 0
        ");
        $stmt->execute([$match['id'], $match['other_user_id']]);
        $unread = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $match_details[] = [
            'id' => $match['id'],
            'name' => $other_user['name'],
            'profile_pic' => $other_user['profile_pic'],
            'last_message' => $last_message ? $last_message['message'] : null,
            'last_message_time' => $last_message ? $last_message['created_at'] : null,
            'unread_count' => $unread['count']
        ];
    }
    
    echo json_encode(['success' => true, 'matches' => $match_details]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['match_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Match ID is required']);
        exit();
    }
    
    $match_id = $data['match_id'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM matches 
        WHERE id = ? AND (user_id_1 = ? OR user_id_2 = ?)
    ");
    $stmt->execute([$match_id, $user_id, $user_id]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Match not found']);
        exit();
    }
    
    if (isset($data['action'])) {
        if ($data['action'] === 'mark_read') {
            $stmt = $pdo->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE match_id = ? AND sender_id != ? AND is_read = 0
            ");
            $stmt->execute([$match_id, $user_id]);
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        if ($data['action'] === 'unmatch') {
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("DELETE FROM messages WHERE match_id = ?");
                $stmt->execute([$match_id]);

                $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
                $stmt->execute([$match_id]);
                
                $pdo->commit();
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                $pdo->rollBack();
                
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Unmatch failed: ' . $e->getMessage()]);
            }
            
            exit();
        }
    }
    
    if (!isset($data['message']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }
    
    $message = $data['message'];

    $stmt = $pdo->prepare("
        INSERT INTO messages (match_id, sender_id, message, is_read, created_at) 
        VALUES (?, ?, ?, 0, NOW())
    ");
    $stmt->execute([$match_id, $user_id, $message]);
    
    echo json_encode(['success' => true]);
    exit();
}
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
