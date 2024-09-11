<?php
require('../../../config/config-colorgame.php');
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

$input = @json_decode(@file_get_contents('php://input'), true);
$currentDateTime = new DateTime();
$dateString = $currentDateTime->format('Y-m-d H:i:s');
if (!is_numeric($input['amount']) || $input['amount'] <= 49) {
    // Return an error response indicating that the amount is not a valid number or exceeds the limit
    $result = array(
        "status" => 'failed',
        "message" => "The amount is not a valid number or not greater than to minimum amount(50)!"
    );

    http_response_code(400);
} else {

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

    $check_user = "SELECT * FROM summation_transaction  
   WHERE 
   `player_id` = :player_id";

    $checkUserAuth = $conn->prepare($check_user);
    $checkUserAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
    $checkUserAuth->execute();
    $rows = $checkUserAuth->fetch(PDO::FETCH_ASSOC);

    if (sizeof($rows) > 0) {
        $reference_id = strtoupper(generateUniqueReferenceId(8, $conn));
        
        if($input['type'] == "cashin"){
            $insert_transaction = "INSERT INTO transactions (transaction_status_id, reference_id,amount, player_id, signature,previous_bal,current_bal,date_created) 
                                    VALUES (:transaction_status_id, :reference_id,:amount, :player_id, :signature,:previous_bal,:current_bal,:date_created)";
            $statement = $conn->prepare($insert_transaction);
            $statement->bindValue(':transaction_status_id', 1);
            $previous_bal = floatval($rows['current_balance']);
            $current_bal = floatval($rows['current_balance'] + floatval($input['amount']));
            $statement->bindValue(':reference_id', $reference_id);
            $statement->bindValue(':amount', $input['amount']);
            $statement->bindValue(':player_id', $input['playerUsername']);
            $statement->bindValue(':signature', $signature);
            $statement->bindValue(':previous_bal', $previous_bal);
            $statement->bindValue(':current_bal', $current_bal);
            $statement->bindValue(':date_created', $dateString);
            $statement->execute();
    
    
            $check_user_transaction = "SELECT * FROM transactions t
            LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
            WHERE 
            t.player_id = :player_id AND t.reference_id = :reference_id";
         
             $transactionAuth = $conn->prepare($check_user_transaction);
             $transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
             $transactionAuth->bindParam(':reference_id', $reference_id, PDO::PARAM_STR);
             $transactionAuth->execute();
             $user_transaction = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

              //SUCCESS
            $result = array(
                "status" => 'success',
                "message" => "Wallet Updated!",
                "data" => array(
                    'user_transaction' => $user_transaction)
            );
            http_response_code(200);
        }elseif($input['type'] == "cashout"){

            if($rows['current_balance'] >= $input['amount']){
                $insert_transaction = "INSERT INTO transactions (transaction_status_id, reference_id,amount, player_id, signature,previous_bal,current_bal,date_created) 
                                            VALUES (:transaction_status_id, :reference_id,:amount, :player_id, :signature,:previous_bal,:current_bal,:date_created)";
                $statement = $conn->prepare($insert_transaction);
                $statement->bindValue(':transaction_status_id', 2);
                $previous_bal = floatval($rows['current_balance']);
                $current_bal = floatval($rows['current_balance'] - floatval($input['amount']));
                $statement->bindValue(':reference_id', $reference_id);
                $statement->bindValue(':amount', $input['amount']);
                $statement->bindValue(':player_id', $input['playerUsername']);
                $statement->bindValue(':signature', $signature);
                $statement->bindValue(':previous_bal', $previous_bal);
                $statement->bindValue(':current_bal', $current_bal);
                $statement->bindValue(':date_created', $dateString);
                $statement->execute();
        
        
                $check_user_transaction = "SELECT * FROM transactions t
                LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
                WHERE 
                t.player_id = :player_id AND t.reference_id = :reference_id";
             
                 $transactionAuth = $conn->prepare($check_user_transaction);
                 $transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
                 $transactionAuth->bindParam(':reference_id', $reference_id, PDO::PARAM_STR);
                 $transactionAuth->execute();
                 $user_transaction = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

                  //SUCCESS
                $result = array(
                    "status" => 'success',
                    "message" => "Wallet Updated!",
                    "data" => array(
                        'user_transaction' => $user_transaction)
                );
                http_response_code(200);
            }else{
                $result = array(
                    "status" => 'failed',
                    "message" => "Insufficient funds!"
                );
            
                http_response_code(400);
            }

            
        }else{
            $result = array(
                "status" => 'failed',
                "message" => "Incorrect type!"
            );
        
            http_response_code(400);
        }

    }else{
       //user does not exist
    $result = array(
        "status" => 'failed',
        "message" => "User does not exist!"
    );

    http_response_code(400);


    }
   
} else {
    //FAILED
    $result = array(
        "status" => 'failed',
        "message" => "Operator does not exist!"
    );

    http_response_code(400);
}
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


?>
