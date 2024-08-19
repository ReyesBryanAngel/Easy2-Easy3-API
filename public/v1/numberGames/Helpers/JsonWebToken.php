<?php
namespace v1\numberGames\Helpers;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use Dotenv\Dotenv as Dotenv;

class JsonWebToken {

    public $secretKey;
    
    public function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__ .'/../../../../');
        $dotenv->load();
        $this->secretKey = $_ENV['SECRET_KEY'];
    }    
    public function generateToken($payload, $algorithm = 'HS256') {
        $issuedAt = time();
        $expirationTime = $issuedAt + (60 * 15);
        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expirationTime;
    
        $token = JWT::encode($payload, $this->secretKey, $algorithm);

        return $token;
    }
    
    public function verifyToken($token) {
        $algorithm = 'HS256';
        
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $algorithm));
            return $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            $errorMessage = [
                'code' => 401,
                'status' => 'failed',
                'message' => $e->getMessage()
            ];
            
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode($errorMessage);
            exit;
        } catch (\Exception $e) {
            $errorMessage = [
                'code' => 401,
                'status' => 'failed',
                'message' => "Token is invalid: " . $e->getMessage()
            ];
            
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode($errorMessage);
            exit;
        }
    }
    
    function getBearerToken() {
        $headers = apache_request_headers();
        if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) { 
            return $matches[1];
        }
    
        return false;
    }
}