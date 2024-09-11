<?php
header('Content-Type: application/json; charset=utf-8');
require('../../../../config/config-colorgame.php');

$input = json_decode(file_get_contents('php://input'), true);
$page_number = max((int)($input["pageNumber"] ?? 1), 1);
$perPage = max((int)($input["perPage"] ?? 4), 1);
$offset = ($page_number - 1) * $perPage;

$total = countRecord($conn, $input);
$total_pages = max(ceil($total / $perPage), 1);

if ($page_number > $total_pages) {
    echo json_encode([
        "status" => "success",
        "response_code" => 200,
        "message" => "The maximum page allowed is only " . $total_pages,
        "data" => []
    ], JSON_PRETTY_PRINT);
    $conn = null;
    die();
}

$query_conditions = [];
$query_params = [];

if (!empty($input["player_id"])) {
    $query_conditions[] = "t.player_id = :player_id";
    $query_params[':player_id'] = $input["player_id"];
}

if (!empty($input["date_from"]) && !empty($input["date_to"])) {
    $query_conditions[] = "t.date_created BETWEEN :date_from AND :date_to";
    $query_params[':date_from'] = $input["date_from"];
    $query_params[':date_to'] = $input["date_to"];
} elseif (!empty($input["date_from"])) {
    $query_conditions[] = "t.date_created >= :date_from";
    $query_params[':date_from'] = $input["date_from"];
} elseif (!empty($input["date_to"])) {
    $query_conditions[] = "t.date_created <= :date_to";
    $query_params[':date_to'] = $input["date_to"];
}

if (!empty($input["transaction_status_id"])) {
    $query_conditions[] = "t.transaction_status_id = :transaction_status_id";
    $query_params[':transaction_status_id'] = $input["transaction_status_id"];
}

if (!empty($input["operator_id"])) {
    $query_conditions[] = "t.operator_id = :operator_id";
    $query_params[':operator_id'] = $input["operator_id"];
}

if (!empty($input["reference_id"])) {
    $query_conditions[] = "t.reference_id = :reference_id";
    $query_params[':reference_id'] = $input["reference_id"];
}

$where_clause = $query_conditions ? ' WHERE ' . implode(' AND ', $query_conditions) : '';

$check_user_transaction = "
    SELECT 
        t.transaction_id, ts.title as transaction_status, o.company_name as operator_name, t.amount, 
        t.reference_id, t.player_id, t.date_created, t.previous_bal, t.current_bal
    FROM transactions t
    LEFT JOIN transaction_status ts ON ts.transaction_status_id = t.transaction_status_id
    LEFT JOIN operators o ON o.operator_id = t.operator_id
    $where_clause
    ORDER BY t.transaction_id DESC
    LIMIT :limit OFFSET :offset";

$transactionAuth = $conn->prepare($check_user_transaction);
foreach ($query_params as $param => $value) {
    $transactionAuth->bindValue($param, $value);
}
$transactionAuth->bindValue(':limit', $perPage, PDO::PARAM_INT);
$transactionAuth->bindValue(':offset', $offset, PDO::PARAM_INT);

$transactionAuth->execute();
$rows = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

$total_query = "
    SELECT
        SUM(CASE WHEN t.transaction_status_id = 1 THEN t.amount ELSE 0 END) AS total_cashin,
        SUM(CASE WHEN t.transaction_status_id = 2 THEN t.amount ELSE 0 END) AS total_cashout,
        SUM(CASE WHEN t.transaction_status_id = 3 THEN t.amount ELSE 0 END) AS total_bet,
        SUM(CASE WHEN t.transaction_status_id = 4 THEN t.amount ELSE 0 END) AS total_payout,
        COUNT(CASE WHEN t.transaction_status_id = 1 THEN 1 END) AS count_cashin,
        COUNT(CASE WHEN t.transaction_status_id = 2 THEN 1 END) AS count_cashout,
        COUNT(CASE WHEN t.transaction_status_id = 3 THEN 1 END) AS count_bet,
        COUNT(CASE WHEN t.transaction_status_id = 4 THEN 1 END) AS count_payout
    FROM transactions t
    LEFT JOIN transaction_status ts ON ts.transaction_status_id = t.transaction_status_id
    LEFT JOIN operators o ON o.operator_id = t.operator_id
    $where_clause";

$total_statement = $conn->prepare($total_query);
foreach ($query_params as $param => $value) {
    $total_statement->bindValue($param, $value);
}
$total_statement->execute();
$total_results = $total_statement->fetch(PDO::FETCH_ASSOC);

$total_cashin = $total_results['total_cashin'] ?? 0;
$total_cashout = $total_results['total_cashout'] ?? 0;
$total_bet = $total_results['total_bet'] ?? 0;
$total_payout = $total_results['total_payout'] ?? 0;

$count_cashin = $total_results['count_cashin'] ?? 0;
$count_cashout = $total_results['count_cashout'] ?? 0;
$count_bet = $total_results['count_bet'] ?? 0;
$count_payout = $total_results['count_payout'] ?? 0;

echo json_encode([
    "status" => "success",
    "response_code" => 200,
    "message" => "Retrieved the requested records.",
    "data" => [
        "table" => [
            "rows" => $rows,
            "totalPages" => $total_pages,
            "pageNumber" => $page_number,
            "totalRecords" => $total,
        ],
        "statistic" => [
            "total_cashin" => $total_cashin,
            "total_cashout" => $total_cashout,
            "total_bet" => $total_bet,
            "total_payout" => $total_payout,
            "count_cashin" => $count_cashin,
            "count_cashout" => $count_cashout,
            "count_bet" => $count_bet,
            "count_payout" => $count_payout
        ],
    ]
], JSON_PRETTY_PRINT);

$conn = null;
die();

function countRecord($conn, $input) {
    $query_conditions = [];
    $query_params = [];

    if (!empty($input["player_id"])) {
        $query_conditions[] = "t.player_id = :player_id";
        $query_params[':player_id'] = $input["player_id"];
    }

    if (!empty($input["date_from"]) && !empty($input["date_to"])) {
        $query_conditions[] = "t.date_created BETWEEN :date_from AND :date_to";
        $query_params[':date_from'] = $input["date_from"];
        $query_params[':date_to'] = $input["date_to"];
    } elseif (!empty($input["date_from"])) {
        $query_conditions[] = "t.date_created >= :date_from";
        $query_params[':date_from'] = $input["date_from"];
    } elseif (!empty($input["date_to"])) {
        $query_conditions[] = "t.date_created <= :date_to";
        $query_params[':date_to'] = $input["date_to"];
    }

    if (!empty($input["transaction_status_id"])) {
        $query_conditions[] = "t.transaction_status_id = :transaction_status_id";
        $query_params[':transaction_status_id'] = $input["transaction_status_id"];
    }

    if (!empty($input["operator_id"])) {
        $query_conditions[] = "t.operator_id = :operator_id";
        $query_params[':operator_id'] = $input["operator_id"];
    }

    if (!empty($input["reference_id"])) {
        $query_conditions[] = "t.reference_id = :reference_id";
        $query_params[':reference_id'] = $input["reference_id"];
    }

    $where_clause = $query_conditions ? ' WHERE ' . implode(' AND ', $query_conditions) : '';

    $check_user_transaction = "
        SELECT COUNT(t.transaction_id) as totalRecords
        FROM transactions t
        LEFT JOIN transaction_status ts ON ts.transaction_status_id = t.transaction_status_id
        LEFT JOIN operators o ON o.operator_id = t.operator_id
        $where_clause";

    $transactionAuth = $conn->prepare($check_user_transaction);
    foreach ($query_params as $param => $value) {
        $transactionAuth->bindValue($param, $value);
    }
    $transactionAuth->execute();
    $row = $transactionAuth->fetch(PDO::FETCH_ASSOC);
    return $row['totalRecords'] ?? 0;
}
?>
