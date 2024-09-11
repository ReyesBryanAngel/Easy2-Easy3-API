<?php
header('Content-Type: application/json; charset=utf-8');
require('../../../../config/config-colorgame.php');

$input = @json_decode(@file_get_contents('php://input'), true);
$page_number = isset($input["pageNumber"]) && $input["pageNumber"] > 0 ? (int)$input["pageNumber"] : 1;
$perPage = isset($input["perPage"]) && $input["perPage"] > 0 ? (int)$input["perPage"] : 4;
$offset = ($page_number - 1) * $perPage;

$total = countRecord($conn, $input);
$total_pages = $total > 0 ? ceil($total / $perPage) : 6;

if ($page_number > $total_pages) {
    $result = array(
        "status" => "success",
        "response_code" => 200,
        "message" => "The maximum page allowed is only " . $total_pages,
        "data" => []
    );

    header('HTTP/1.1 200 OK');
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($result, JSON_PRETTY_PRINT);
    $conn = null;
    die();
}

$check_user_transaction = "SELECT b.bet_id, b.bet, b.bet_amount, b.payout, wt.description as winning_type, b.bet_status, o.company_name as operator_name, b.player_id, b.time_of_bet, g.game_name, b.reference_id
                           FROM bets b
                           LEFT JOIN winning_type wt ON wt.win_type_id = b.win_type_id
                           LEFT JOIN operators o ON o.operator_id = b.operator_id
                           LEFT JOIN games g ON g.id = b.game_id";

$conditions = [];
$params = [];

if (isset($input["reference_id"])) {
    $conditions[] = "b.reference_id = :reference_id";
    $params[':reference_id'] = $input["reference_id"];
}
if (isset($input["bet_status"])) {
    $conditions[] = "b.bet_status = :bet_status";
    $params[':bet_status'] = $input["bet_status"];
}
if (isset($input["player_id"])) {
    $conditions[] = "b.player_id = :player_id";
    $params[':player_id'] = $input["player_id"];
}
if (isset($input["game_id"])) {
    $conditions[] = "b.game_id = :game_id";
    $params[':game_id'] = $input["game_id"];
}
if (isset($input["operator_id"])) {
    $conditions[] = "b.operator_id = :operator_id";
    $params[':operator_id'] = $input["operator_id"];
}
if (isset($input["date_from"]) && isset($input["date_to"])) {
    $conditions[] = "b.time_of_bet BETWEEN :date_from AND :date_to";
    $params[':date_from'] = $input["date_from"];
    $params[':date_to'] = $input["date_to"];
} elseif (isset($input["date_from"])) {
    $conditions[] = "b.time_of_bet >= :date_from";
    $params[':date_from'] = $input["date_from"];
} elseif (isset($input["date_to"])) {
    $conditions[] = "b.time_of_bet <= :date_to";
    $params[':date_to'] = $input["date_to"];
}

if (count($conditions) > 0) {
    $check_user_transaction .= " WHERE " . implode(" AND ", $conditions);
}

$check_user_transaction .= " ORDER BY b.bet_id DESC LIMIT :limit OFFSET :offset";

$transactionAuth = $conn->prepare($check_user_transaction);
$transactionAuth->bindParam(':limit', $perPage, PDO::PARAM_INT);
$transactionAuth->bindParam(':offset', $offset, PDO::PARAM_INT);

foreach ($params as $param => $value) {
    $transactionAuth->bindValue($param, $value);
}

$transactionAuth->execute();
$rows = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

$total_query = "SELECT COUNT(b.bet_id) AS total_count, SUM(b.bet_amount) AS total_amount
                FROM bets b";

if (count($conditions) > 0) {
    $total_query .= " WHERE " . implode(" AND ", $conditions);
}

$total_statement = $conn->prepare($total_query);

foreach ($params as $param => $value) {
    $total_statement->bindValue($param, $value);
}

$total_statement->execute();
$total_results = $total_statement->fetch(PDO::FETCH_ASSOC);

$total_count = $total_results['total_count'] ?? 0;
$total_amount = $total_results['total_amount'] ?? 0;

$result = array(
    "status" => "success",
    "response_code" => 200,
    "message" => "Retrieved the requested records.",
    "data" => array(
        "table" => array(
            "rows" => $rows,
            "totalPages" => $total_pages,
            "pageNumber" => $page_number,
            "totalRecords" => $total,
        ),
        "statistic" => array(
            "total_count" => $total_count,
            "total_amount" => $total_amount
        ),
    )
);

header('HTTP/1.1 200 OK');
header('Content-Type: application/json; charset=utf-8');

echo json_encode($result, JSON_PRETTY_PRINT);
$conn = null;
die();

function countRecord($conn, $input)
{
    $check_user_transaction = "SELECT COUNT(b.bet_id) as totalRecords
                               FROM bets b";
    $conditions = [];
    $params = [];

    if (isset($input["reference_id"])) {
        $conditions[] = "b.reference_id = :reference_id";
        $params[':reference_id'] = $input["reference_id"];
    }
    if (isset($input["bet_status"])) {
        $conditions[] = "b.bet_status = :bet_status";
        $params[':bet_status'] = $input["bet_status"];
    }
    if (isset($input["player_id"])) {
        $conditions[] = "b.player_id = :player_id";
        $params[':player_id'] = $input["player_id"];
    }
    if (isset($input["game_id"])) {
        $conditions[] = "b.game_id = :game_id";
        $params[':game_id'] = $input["game_id"];
    }
    if (isset($input["date_from"]) && isset($input["date_to"])) {
        $conditions[] = "b.time_of_bet BETWEEN :date_from AND :date_to";
        $params[':date_from'] = $input["date_from"];
        $params[':date_to'] = $input["date_to"];
    } elseif (isset($input["date_from"])) {
        $conditions[] = "b.time_of_bet >= :date_from";
        $params[':date_from'] = $input["date_from"];
    } elseif (isset($input["date_to"])) {
        $conditions[] = "b.time_of_bet <= :date_to";
        $params[':date_to'] = $input["date_to"];
    }

    if (count($conditions) > 0) {
        $check_user_transaction .= " WHERE " . implode(" AND ", $conditions);
    }

    $transactionAuth = $conn->prepare($check_user_transaction);

    foreach ($params as $param => $value) {
        $transactionAuth->bindValue($param, $value);
    }

    $transactionAuth->execute();
    $row = $transactionAuth->fetch(PDO::FETCH_ASSOC);
    return ($row['totalRecords']) ? $row['totalRecords'] : 0;
}
?>
