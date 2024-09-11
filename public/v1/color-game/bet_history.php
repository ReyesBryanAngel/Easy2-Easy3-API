<?php
require('../../../config/config-colorgame.php');

header('Content-Type: application/json; charset=utf-8');

$input = @json_decode(@file_get_contents('php://input'), true);

if ($input) {

    $check_user_transaction = "SELECT e.event_name, bd.board_title, r.winning_result, b.round_count, b.bet, b.bet_amount, b.payout, b.income, b.bet_status, b.time_of_bet
    FROM bets b
    LEFT JOIN events e ON e.event_id = b.event_id
    LEFT JOIN boards bd  ON bd.board_id = b.board_id
    LEFT JOIN rounds r  ON r.round_id = b.round_id
    WHERE 
    player_id = :player_id";

if (isset($input["date_from"]) && isset($input["date_to"])) {
    $check_user_transaction .= " AND DATE(b.time_of_bet) BETWEEN :date_from AND :date_to";
} elseif (isset($input["date_from"])) {
    $check_user_transaction .= " AND DATE(b.time_of_bet) >= :date_from";
} elseif (isset($input["date_to"])) {
    $check_user_transaction .= " AND DATE(b.time_of_bet) <= :date_to";
}

if (isset($input["bet_status"])) {
    $check_user_transaction .= " AND b.bet_status = :bet_status";
}

$check_user_transaction .= " ORDER BY b.bet_id DESC LIMIT 50";

$transactionAuth = $conn->prepare($check_user_transaction);
$transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);

if (isset($input["date_from"])) {
    $transactionAuth->bindParam(':date_from', $input["date_from"], PDO::PARAM_STR);
}

if (isset($input["date_to"])) {
    $transactionAuth->bindParam(':date_to', $input["date_to"], PDO::PARAM_STR);
}

if (isset($input["bet_status"])) {
    $transactionAuth->bindParam(':bet_status', $input["bet_status"], PDO::PARAM_STR);
}

$transactionAuth->execute();
$user_transaction = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

  //SUCCESS
  $result = array(
    "status" => 'success',
    "message" => "Fetch Complete",
    "data" => array(
        'user_transaction' => $user_transaction)
);
http_response_code(200);
} else {
//FAILED
$result = array(
    "status" => 'failed',
    "message" => "failed to fetch data!"
);

http_response_code(400);
}

echo json_encode($result);

//CLOSE DATABASE CONNECTION
$conn = null;




?>
