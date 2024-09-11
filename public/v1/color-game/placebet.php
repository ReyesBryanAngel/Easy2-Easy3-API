<?php
require('../../../config/config-colorgame.php');
 
header('Content-Type: application/json; charset=utf-8');

$input = @json_decode(@file_get_contents('php://input'), true);
$currentDateTime = new DateTime();
$dateString = $currentDateTime->format('Y-m-d H:i:s');

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


     //check events if open
     $sql_check_event = "SELECT * FROM events  
     WHERE 
     `event_id` = :event_id AND `event_status` = 'open'";
  
      $checkEventAuth = $conn->prepare($sql_check_event);
      $checkEventAuth->bindParam(':event_id', $input["event_id"], PDO::PARAM_STR);
      $checkEventAuth->execute();
      $check_event = $checkEventAuth->fetch(PDO::FETCH_ASSOC);
      

    if($check_event){

                // check board if open
        $sql_check_board = "SELECT * FROM boards  
        WHERE 
        `board_id` = :board_id AND `board_status` = 'open'";
    
        $checkBoardAuth = $conn->prepare($sql_check_board);
        $checkBoardAuth->bindParam(':board_id', $input["board_id"], PDO::PARAM_STR);
        $checkBoardAuth->execute();
        $check_round = $checkBoardAuth->fetch(PDO::FETCH_ASSOC);


        if($check_round){

            // check round_count if open
            $sql_check_round = "SELECT * FROM rounds  
            WHERE 
            `round_id` = :round_id AND `round_status` = 'open'";

            $checkRoundAuth = $conn->prepare($sql_check_round);
            $checkRoundAuth->bindParam(':round_id', $input["round_id"], PDO::PARAM_STR);
            $checkRoundAuth->execute();
            $check_round = $checkRoundAuth->fetch(PDO::FETCH_ASSOC);

            if($check_round){
                
                if(isset($rows['current_balance']) >= $input['bet_amount']){
                    $check_gamesession = check_gamesession($input,$conn);
                    if($check_gamesession){
                    $reference_id = strtoupper(generateUniqueReferenceId(8, $conn));
                    $previous_bal = floatval($rows['current_balance']);
                    $current_bal = floatval($rows['current_balance'] - floatval($input['bet_amount']));

                    $insert_transaction = "INSERT 
                                                INTO transactions (transaction_status_id, reference_id,amount, player_id, signature, previous_bal, current_bal,date_created,operator_id) 
                                                VALUES (:transaction_status_id, :reference_id,:amount, :player_id, :signature, :previous_bal, :current_bal, :date_created, :operator_id)";
                    $statement = $conn->prepare($insert_transaction);
                    $statement->bindValue(':transaction_status_id', 3);
                    $statement->bindValue(':reference_id', $reference_id);
                    $statement->bindValue(':amount', $input['bet_amount']);
                    $statement->bindValue(':player_id', $input['playerUsername']);
                    $statement->bindValue(':signature', $signature);
                    $statement->bindValue(':previous_bal', $previous_bal);
                    $statement->bindValue(':current_bal', $current_bal);
                    $statement->bindValue(':date_created', $dateString);
                    $statement->bindValue(':operator_id', $input['operatorID']);
                    $statement->execute();

                    //check bet if existing same color just add
                    $sql_betexist = "SELECT * FROM bets  
                    WHERE 
                    `board_id` = :board_id AND `round_id` = :round_id AND `bet` = :bet AND `player_id` = :player_id";

                    $betexistAuth = $conn->prepare($sql_betexist);
                    $betexistAuth->bindParam(':board_id', $input["board_id"]);
                    $betexistAuth->bindParam(':round_id', $input["round_id"]);
                    $betexistAuth->bindParam(':bet', $input["bet"]);
                    $betexistAuth->bindParam(':player_id', $input["playerUsername"]);
                    $betexistAuth->execute();
                    $check_betexist = $betexistAuth->fetch(PDO::FETCH_ASSOC);
                    $bet_status = "PENDING";
                    $win_type_id = 0;

                    if($check_betexist){
                       // If bet exists, update the existing record
                        $update_bet = "UPDATE bets 
                        SET bet_amount = bet_amount + :bet_amount
                        WHERE 
                        `board_id` = :board_id AND `round_id` = :round_id AND `bet` = :bet AND `player_id` = :player_id";
                        
                        $statement = $conn->prepare($update_bet);
                        $statement->bindParam(':bet_amount', $input['bet_amount']);
                        $statement->bindParam(':board_id', $input['board_id']);
                        $statement->bindParam(':round_id', $input['round_id']);
                        $statement->bindParam(':bet', $input['bet']);
                        $statement->bindParam(':player_id', $input['playerUsername']);
                        $statement->execute();
                    }else{
                        $insert_transaction = "INSERT 
                        INTO bets (event_id, board_id,round_id, round_count, bet, bet_amount, player_id, game_id,time_of_bet, operator_id, bet_status, reference_id, win_type_id) 
                        VALUES (:event_id, :board_id,:round_id, :round_count, :bet, :bet_amount, :player_id, :game_id,:time_of_bet, :operatorID, :bet_status,  :reference_id, :win_type_id)";
                        $statement = $conn->prepare($insert_transaction);
                        $statement->bindValue(':event_id', $input['event_id']);
                        $statement->bindValue(':board_id', $input['board_id']);
                        $statement->bindValue(':round_id', $input['round_id']);
                        $statement->bindValue(':round_count', $input['round_count']);
                        $statement->bindValue(':bet', $input['bet']);
                        $statement->bindValue(':bet_amount', $input['bet_amount']);
                        $statement->bindParam(':player_id', $input['playerUsername']);
                        $statement->bindParam(':game_id', $input['game_id']);
                        $statement->bindValue(':time_of_bet', $dateString);
                        $statement->bindValue(':operatorID', $input['operatorID']);
                        $statement->bindValue(':bet_status', $bet_status);
                        $statement->bindValue(':reference_id', $reference_id);
                        $statement->bindValue(':win_type_id', $win_type_id);
                        $statement->execute();
                    }
            
                    $check_user_transaction = "SELECT t.transaction_id,ts.title as type,t.amount,t.reference_id,previous_bal,current_bal
                                                    FROM transactions t
                                                    LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
                                                    WHERE 
                                                    t.player_id = :player_id AND t.reference_id = :reference_id";
                
                    $transactionAuth = $conn->prepare($check_user_transaction);
                    $transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
                    $transactionAuth->bindParam(':reference_id', $reference_id, PDO::PARAM_STR);
                    $transactionAuth->execute();
                    $user_transaction = $transactionAuth->fetch(PDO::FETCH_ASSOC);



                    $betslip_sql = "SELECT event_id,board_id,round_id,round_count,bet,bet_amount,payout
                    FROM bets 
                    WHERE 
                    player_id = :player_id AND event_id = :event_id AND board_id = :board_id AND round_id = :round_id";

                    $betslipAuth = $conn->prepare($betslip_sql);
                    $betslipAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);
                    $betslipAuth->bindParam(':event_id', $input['event_id'], PDO::PARAM_INT);
                    $betslipAuth->bindParam(':board_id', $input['board_id'], PDO::PARAM_INT);
                    $betslipAuth->bindParam(':round_id', $input['round_id'], PDO::PARAM_INT);
                    $betslipAuth->execute();
                    $bet_slip= $betslipAuth->fetchAll(PDO::FETCH_ASSOC);



                    $all_bets_sql = "SELECT event_id,board_id,round_id,round_count,bet,bet_amount,payout
                    FROM bets 
                    WHERE 
                    event_id = :event_id AND board_id = :board_id AND round_id = :round_id";

                    $all_betsAuth = $conn->prepare($all_bets_sql);
                    $all_betsAuth->bindParam(':event_id', $input['event_id'], PDO::PARAM_STR);
                    $all_betsAuth->bindParam(':board_id', $input['board_id'], PDO::PARAM_STR);
                    $all_betsAuth->bindParam(':round_id', $input['round_id'], PDO::PARAM_STR);
                    $all_betsAuth->execute();
                    $all_bets= $all_betsAuth->fetchAll(PDO::FETCH_ASSOC);
                

                    //SUCCESS
                    $result = array(
                        "status" => 'success',
                        "message" => "Place bet on ".$input['bet'],
                        "data" => array(
                            'transaction' => $user_transaction,
                            'bet_slip' => $bet_slip,
                            'all_bets' => $all_bets
                            
                            )
                    );
                    http_response_code(200);
                }else{
                    $result = array(
                        "status" => 'failed',
                        "message" => "Game session is not valid!"
                    );
                
                    http_response_code(400);

                }

                }else{
                    //user does not exist
                 $result = array(
                     "status" => 'failed',
                     "message" => "Insufficient Funds!"
                 );
             
                 http_response_code(400);
             
             
                 }



            }else{
                //Round not available
                $result = array(
                    "status" => 'failed',
                    "message" => "Round is not available"
                );

                http_response_code(400);
            }
        }else{
            //board not available
            $result = array(
                "status" => 'failed',
                "message" => "Board is not available"
            );

            http_response_code(400);
        }

    }else{
       //event not available
       $result = array(
        "status" => 'failed',
        "message" => "Event is not available"
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
function check_gamesession($data, $conn)
{
    
    // Check if a game session exists
    $sql_check_playerid = "SELECT id,token,expiration_date FROM game_session WHERE player_id = :player_id AND operator_id = :operator_id AND valid = :valid AND token = :token ";
    $valid = 1;
    $sthAuth = $conn->prepare($sql_check_playerid);
    $sthAuth->bindParam(':player_id', $data['playerUsername']);
    $sthAuth->bindParam(':operator_id', $data['operatorID']);
    $sthAuth->bindParam(':valid', $valid);
    $sthAuth->bindParam(':token', $data['game_session']);
    $sthAuth->execute();
    $rows = $sthAuth->fetch(PDO::FETCH_ASSOC);
    
    // Calculate the new expiration time (10 minutes from now)
    $expiration_time = time() + (10 * 60);
    $expiration_date = date('Y-m-d H:i:s', $expiration_time);
    
    if ($rows) {
        // Check if the current expiration date is in the future
        if ($rows['expiration_date'] >= date('Y-m-d H:i:s')) {
            // Update the expiration date
            $sql_update_expiration = "UPDATE game_session SET expiration_date = :expiration_date WHERE token = :token";
            $sthUpdate = $conn->prepare($sql_update_expiration);
            $sthUpdate->bindParam(':expiration_date', $expiration_date);
            $sthUpdate->bindParam(':token', $rows['token']);
            $sthUpdate->execute();
            $update_row = $rows;
        } else {
            // Update the valid status
            $sql_update_expiration = "UPDATE game_session SET valid = :valid WHERE token = :token";
            $valid = 2;
            $sthUpdate = $conn->prepare($sql_update_expiration);
            $sthUpdate->bindParam(':valid', $valid);
            $sthUpdate->bindParam(':token', $rows['token']);
            $sthUpdate->execute();
            $update_row = null;
        }
    } else{
        $update_row = null;
    }
    
    return $update_row;
}

?>
