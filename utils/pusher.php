<?php
require_once __DIR__ . '/../vendor/autoload.php';

class PusherHandler {
    // Your Pusher credentials (from the App Keys tab in Pusher dashboard)
    private $app_id = 'id';
    private $key = 'key';
    private $secret = 'secret_key';
    private $cluster = 'CLusteres';  // The cluster you selected (mt1 = US East)
    private $pusher;
    
    public function __construct() {
        $this->pusher = new Pusher\Pusher(
            $this->key,
            $this->secret,
            $this->app_id,
            array(
                'cluster' => $this->cluster,
                'useTLS' => true  // Always enable TLS for security
            )
        );
    }
    
    // Trigger a new match event
    public function triggerNewMatch($user1_id, $user2_id, $match_data) {
        // Notify both users
        $this->pusher->trigger("private-user-{$user1_id}", 'new-match', $match_data);
        $this->pusher->trigger("private-user-{$user2_id}", 'new-match', $match_data);
    }
    
    // Trigger a new message event
    public function triggerNewMessage($match_id, $message_data) {
        $this->pusher->trigger("private-match-{$match_id}", 'new-message', $message_data);
    }
    
    // Generate auth signature for private channels
    public function auth($channel_name, $socket_id, $user_id) {
        // Check if user is authorized to access this channel
        if(strpos($channel_name, "private-user-{$user_id}") === 0 || 
           $this->isUserInMatch($channel_name, $user_id)) {
            return $this->pusher->socket_auth($channel_name, $socket_id);
        }
        
        return false;
    }
    
    // Check if user is part of the match for a match channel
    private function isUserInMatch($channel_name, $user_id) {
        if(strpos($channel_name, 'private-match-') === 0) {
            $match_id = str_replace('private-match-', '', $channel_name);
            
            // Connect to database
            $db = new Database();
            $conn = $db->getConnection();
            
            $query = "SELECT id FROM matches WHERE id = ? AND (user1_id = ? OR user2_id = ?)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(1, $match_id);
            $stmt->bindParam(2, $user_id);
            $stmt->bindParam(3, $user_id);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        }
        
        return false;
    }
}
?>
