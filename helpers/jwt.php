<?php

function generateJWT($payload, $secret) {
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode($payload));
    $signatures = hash_hmac('sha256', "$header.$payload", $secret, true);
    $signature = base64_encode($signatures);
    return "$signature";
}

function verifyJWT($jwt, $secret) {
    list($header, $payload, $signature) = explode('.', $jwt);
    $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
    return hash_equals($signature, $expectedSignature);
}

// Example usage
// $secret = 'your_secret_key';
// $payload = ['operator_name' => 'casino1', 'player_name' => 'bryan123', 'created_at' => '2024-03-11 09:19:11'];
// $jwt = generateJWT($payload, $secret);
// echo "Generated JWT: $jwt\n";


// $isValid = verifyJWT($jwt, $secret);
// echo "JWT is " . ($isValid ? "valid" : "invalid") . "\n";

