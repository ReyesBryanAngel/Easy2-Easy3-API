<?php
require('../../../config/config-colorgame.php');

header('Content-Type: application/json; charset=utf-8');

$input = @json_decode(@file_get_contents('php://input'), true);

if ($input) {
    $check_user_transaction = "SELECT t.transaction_id,ts.title,ts.type,t.amount,t.reference_id,t.player_id,t.previous_bal,t.current_bal,t.date_created
    FROM transactions t
    LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
    WHERE 
    t.player_id = :player_id";

    if (isset($input['date_from']) && isset($input['date_to'])) {
        // Both date_from and date_to are provided
        $check_user_transaction .= " AND DATE(t.date_created) BETWEEN :date_from AND :date_to";
    } elseif (isset($input['date_from'])) {
        // Only date_from is provided
        $check_user_transaction .= " AND DATE(t.date_created) >= :date_from";
    }

    if (isset($input['transaction_status_id'])) {
        // Status is provided
        $check_user_transaction .= " AND ts.transaction_status_id = :transaction_status_id";
    }

    $check_user_transaction .= " ORDER BY t.transaction_id DESC LIMIT 50";

    $transactionAuth = $conn->prepare($check_user_transaction);
    $transactionAuth->bindParam(':player_id', $input["playerUsername"], PDO::PARAM_STR);

    if (isset($input['date_from']) && isset($input['date_to'])) {
        // Both date_from and date_to are provided
        $transactionAuth->bindParam(':date_from', $input['date_from'], PDO::PARAM_STR);
        $transactionAuth->bindParam(':date_to', $input['date_to'], PDO::PARAM_STR);
    } elseif (isset($input['date_from'])) {
        // Only date_from is provided
        $transactionAuth->bindParam(':date_from', $input['date_from'], PDO::PARAM_STR);
    }

    if (isset($input['transaction_status_id'])) {
        // Status is provided
        $transactionAuth->bindParam(':transaction_status_id', $input['transaction_status_id'], PDO::PARAM_STR);
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
