<?php
// JWT Token Handler for TKSHA Dating App
require_once __DIR__.'/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler {
    // Configuration - CHANGE THESE VALUES FOR PRODUCTION
    private $secret_key = "YOUR_VERY_STRONG_SECRET_KEY_HERE"; // Minimum 32 characters recommended
    private $issuer = "tksha_dating_app";                     // Token issuer identifier
    private $audience = "tksha_users";                        // Intended token audience
    private $algorithm = 'HS256';                             // Hashing algorithm
    
    /**
     * Generate a JWT token for a user
     * @param int $user_id The user ID to include in the token
     * @return string The generated JWT token
     */
    public function generateToken($user_id) {
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60 * 24); // Token valid for 24 hours
        
        $payload = [
            "iss" => $this->issuer,       // Issuer claim
            "aud" => $this->audience,     // Audience claim
            "iat" => $issued_at,          // Issued at timestamp
            "exp" => $expiration_time,   // Expiration timestamp
            "data" => [                  // Custom data payload
                "id" => $user_id         // User identifier
            ]
        ];
        
        // Generate and return the token
        return JWT::encode($payload, $this->secret_key, $this->algorithm);
    }
    
    /**
     * Validate a JWT token and return the user ID if valid
     * @param string $jwt The JWT token to validate
     * @return int|false Returns user ID if valid, false otherwise
     */
    public function validateToken($jwt) {
        try {
            // Decode the token using the Key object (required for Firebase JWT 6.0+)
            $decoded = JWT::decode(
                $jwt, 
                new Key($this->secret_key, $this->algorithm)
            );
            
            // Verify issuer and audience matches our expectations
            if ($decoded->iss !== $this->issuer || $decoded->aud !== $this->audience) {
                return false;
            }
            
            // Return the user ID from the token payload
            return $decoded->data->id;
            
        } catch (Exception $e) {
            // Catch all JWT exceptions (expired, invalid signature, etc)
            error_log("JWT Validation Error: " . $e->getMessage());
            return false;
        }
    }
}
?>