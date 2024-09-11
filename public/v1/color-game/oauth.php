<?php
require('../../../config/config-colorgame.php');
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

$currentDateTime = new DateTime();
$dateString = $currentDateTime->format('Y-m-d H:i:s');

$input = @json_decode(@file_get_contents('php://input'), true);
$operatorID = $input["operatorID"];
$secret_key = $input["secret_key"];
$signature = md5($input["operatorID"].$input['playerUsername']);
$sql_login = "SELECT * FROM operators  
   WHERE 
   `operator_id` = :operatorID AND `game_api_key` = :secret_key";

$sthAuth = $conn->prepare($sql_login);
$sthAuth->bindParam(':operatorID', $operatorID, PDO::PARAM_INT);
$sthAuth->bindParam(':secret_key', $secret_key, PDO::PARAM_STR);
$sthAuth->execute();
$rows = $sthAuth->fetchAll(PDO::FETCH_ASSOC);

if (sizeof($rows) > 0) {
   
$check_blocklisted = checkBlockListed($input, $conn);

if($check_blocklisted == false){
    $check_user = "SELECT * FROM summation_transaction  
   WHERE 
   `player_id` = :player_id";

    $checkUserAuth = $conn->prepare($check_user);
    $checkUserAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
    $checkUserAuth->execute();
    $rows = $checkUserAuth->fetchAll(PDO::FETCH_ASSOC);

    if (sizeof($rows) > 0) {
        $rows = $rows;
    }else{
        $reference_id = strtoupper(generateUniqueReferenceId(8, $conn));
 
       $insert_transaction = "INSERT INTO transactions (transaction_status_id, reference_id, player_id, signature) VALUES (:transaction_status_id, :reference_id, :player_id, :signature)";
        $statement = $conn->prepare($insert_transaction);
        $statement->bindValue(':transaction_status_id', 1);
        $statement->bindValue(':reference_id', $reference_id);
        $statement->bindValue(':player_id', $input['playerUsername']);
        $statement->bindValue(':signature', $signature);
        $statement->execute();

        $check_user = "SELECT * FROM summation_transaction  
        WHERE 
        `player_id` = :player_id";
     
         $checkUserAuth = $conn->prepare($check_user);
         $checkUserAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
         $checkUserAuth->execute();
         $rows = $checkUserAuth->fetchAll(PDO::FETCH_ASSOC);

    }
    $check_user_transaction = "SELECT * FROM transactions t
    LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
    WHERE 
    t.player_id = :player_id ORDER BY transaction_id DESC";
 
     $transactionAuth = $conn->prepare($check_user_transaction);
     $transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
     $transactionAuth->execute();
     $user_transaction = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

    $input['game_session'] = check_gamesession($input, $conn);

    //SUCCESS
    $result = array(
        "status" => 'success',
        "message" => "Login Successfully!",
        "data" => array(
            'url' => 'localhost/gtbe-colorgame/public/v1/color-game/dashboard.php?token=' . generateJWT($input).'&id='.$operatorID.'&game_session='.$input['game_session'],
            'user_details'=> $rows,
            'user_transaction' => $user_transaction)
    );
    http_response_code(200);

}else{
    $result = array(
        "status" => 'failed',
        "message" => "User is blocked!"
    );

    http_response_code(400);
}
} else {
    //FAILED
    $result = array(
        "status" => 'failed',
        "message" => "Login Failed!"
    );

    http_response_code(400);
}

echo json_encode($result);

//CLOSE DATABASE CONNECTION
$conn = null;

function generateUniqueReferenceId($length, $conn)
{
    $maxAttempts = 10;
    $attempts = 0;

    do {
        $ref = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
        $attempts++;
    } while (checkReferenceIdExists($ref, $conn) && $attempts < $maxAttempts);

    return $ref;
}

function checkReferenceIdExists($code, $conn)
{
    $sql_check_ref = "SELECT COUNT(*) FROM transactions WHERE `reference_id` = :reference_id";

    $sthAuth = $conn->prepare($sql_check_ref);
    $sthAuth->bindParam(':reference_id', $code);
    $sthAuth->execute();
    $count = $sthAuth->fetchColumn();

    return $count > 0;
}

function checkBlockListed($data, $conn)
{
    $sql_check_playerid = "SELECT COUNT(*) FROM blocklisted WHERE `player_id` = :player_id and `operator_id` = :operator_id and `status` = :status";
    $status = 1;
    $sthAuth = $conn->prepare($sql_check_playerid);
    $sthAuth->bindParam(':player_id', $data['playerUsername']);
    $sthAuth->bindParam(':operator_id', $data['operatorID']);
    $sthAuth->bindParam(':status', $status);
    $sthAuth->execute();
    $count = $sthAuth->fetchColumn();
    return $count > 0;
}
function check_gamesession($data, $conn)
{
    // Check if a game session exists
    $sql_check_playerid = "SELECT token FROM game_session WHERE player_id = :player_id AND operator_id = :operator_id AND valid = :valid";
    $valid = 1;
    $sthAuth = $conn->prepare($sql_check_playerid);
    $sthAuth->bindParam(':player_id', $data['playerUsername']);
    $sthAuth->bindParam(':operator_id', $data['operatorID']);
    $sthAuth->bindParam(':valid', $valid);
    $sthAuth->execute();
    $rows = $sthAuth->fetch(PDO::FETCH_ASSOC);
    
    // Calculate the new expiration time (10 minutes from now)
    $expiration_time = time() + (10 * 60);
    $expiration_date = date('Y-m-d H:i:s', $expiration_time);
    
    if ($rows) {
        // If a row is found, update the expiration date and valid status
        $sql_update_expiration = "UPDATE game_session SET expiration_date = :expiration_date, valid = :valid WHERE token = :token";
        $valid_update = 2;
        $sthUpdate = $conn->prepare($sql_update_expiration);
        $sthUpdate->bindParam(':expiration_date', $expiration_date);
        $sthUpdate->bindParam(':token', $rows['token']);
        $sthUpdate->bindParam(':valid', $valid_update);
        $sthUpdate->execute();
        
        // Return the existing token
        $game_session = insert_gamesession($data, $conn);
    } else {
        // Insert a new game session and get the token
        $game_session = insert_gamesession($data, $conn);
    }
    
    return $game_session;
}

function insert_gamesession($data, $conn)
{
    $insert_transaction = "INSERT INTO game_session (token, valid, date_created, expiration_date, player_id, operator_id) 
                           VALUES (:token, :valid, :date_created, :expiration_date, :player_id, :operator_id)";
    
    $encoded_data = json_encode($data);

    // Generate a unique token using MD5 hash of the encoded data
    $token = generate_token();
    $valid = 1;
    $expiration = time() + (10 * 60); // Expiration time 10 minutes
    $expiration_date = date('Y-m-d H:i:s', $expiration); 
    $dateString = date('Y-m-d H:i:s'); // Current date and time for date_created

    $sthAuth = $conn->prepare($insert_transaction);
    $sthAuth->bindParam(':token', $token);
    $sthAuth->bindParam(':valid', $valid);
    $sthAuth->bindParam(':date_created', $dateString);
    $sthAuth->bindParam(':expiration_date', $expiration_date);
    $sthAuth->bindParam(':player_id', $data['playerUsername']);
    $sthAuth->bindParam(':operator_id', $data['operatorID']);
    $sthAuth->execute();
    
    return $token;
}
function generate_token()
{
    return bin2hex(random_bytes(16)); // Generates a 32-character hexadecimal token
}
function generateJWT($payload)
{
    // Encode the payload
    $payload['exp'] = time() + (24 * 60 * 60); // Expiration time 1 day

    // Encode payload to JSON
    $encoded_payload = json_encode($payload);

    // Base64 encode the payload
    $base64_payload = base64_encode($encoded_payload);

    // Encode the header
    $header = json_encode([
        'typ' => 'JWT',
        'alg' => 'HS256'
    ]);

    // Base64 encode the header
    $base64_header = base64_encode($header);

    // Create signature
    $signature = hash_hmac('sha256', "$base64_header.$base64_payload", $payload['secret_key'], true);

    // Base64 encode the signature
    $base64_signature = base64_encode($signature);

    // Concatenate header, payload, and signature with dots
    $jwt_token = "$base64_header.$base64_payload.$base64_signature";

    return $jwt_token;
}

?>
