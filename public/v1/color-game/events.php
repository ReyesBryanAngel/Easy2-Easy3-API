<?php

require('../../../config/config-colorgame.php');   
header('Content-Type: application/json; charset=utf-8'); 
header('Content-Type: application/json');  

$input = @json_decode(@file_get_contents('php://input'), true);

if ($input == null) {
    // FAILED
    $result = array(
        "status" => 'failed',
        "message" => "Incorrect Parameters!"
    );  

    http_response_code(400);
    $code = 400;
} else {
    $sql_login = "SELECT * FROM operators  
                  WHERE 
                  `operator_id` = :operatorID";
    $sthAuth = $conn->prepare($sql_login);
    $sthAuth->bindParam(':operatorID', $input['operatorID']);
    $sthAuth->execute();
    $rows = $sthAuth->fetch(PDO::FETCH_ASSOC);

    if ($rows == null) {
        // FAILED
        $result = array(
            "status" => 'failed',
            "message" => "Operator does not exist!"
        );  

        http_response_code(400);
        $code = 400;
    } else {
        $jwt_token = $input['token'];
        $secret_key = $rows['game_api_key'];

        $verify = verifyJWT($jwt_token, $secret_key);

        if ($verify) {
            $check_user = "SELECT * FROM summation_transaction  
                           WHERE 
                           `player_id` = :player_id";

            $checkUserAuth = $conn->prepare($check_user);
            $checkUserAuth->bindParam(':player_id', $verify["playerUsername"], PDO::PARAM_STR);
            $checkUserAuth->execute();
            $rows = $checkUserAuth->fetch(PDO::FETCH_ASSOC);

            if ($rows['current_balance'] > 0) {
                $sql = "SELECT * FROM events
                        WHERE 
                        `event_status` = :event_status";

                $statement = $conn->prepare($sql);
                $statement->bindValue(':event_status', 'open');
                $statement->execute();
                $rows = $statement->fetch(PDO::FETCH_ASSOC); 

                if (!empty(@$rows)) {
                    $sql_boards = "SELECT board_id, board_title, board_description, video_source, board_status 
                                   FROM boards
                                   WHERE 
                                   `event_id` = :event_id AND `board_status` = :board_status
                                   ORDER BY board_id DESC";

                    $boardStat = $conn->prepare($sql_boards);
                    $boardStat->bindValue(':event_id', $rows['event_id']);
                    $boardStat->bindValue(':board_status', 'open');
                    $boardStat->execute();
                    $rows_boards = $boardStat->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($rows_boards as &$board) {
                        $sql_check_round = "SELECT COUNT(round_id) as round 
                                            FROM rounds
                                            WHERE 
                                            `board_id` = :board_id AND 
                                            `round_status` NOT IN ('finish')
                                            ORDER BY round_id DESC";

                        $stat_check = $conn->prepare($sql_check_round);
                        $stat_check->bindValue(':board_id', $board['board_id']);
                        $stat_check->execute();
                        $check_round = $stat_check->fetch(PDO::FETCH_ASSOC);
                        $board['rounds_available'] = ($check_round['round'] > 0) ? "yes" : "no";

                        // Fetch all rounds for color percentage calculation
                        $sql_all_rounds = "SELECT * FROM rounds
                                           WHERE 
                                           `board_id` = :board_id";

                        $allRoundsStat = $conn->prepare($sql_all_rounds);
                        $allRoundsStat->bindValue(':board_id', $board['board_id']);
                        $allRoundsStat->execute();
                        $all_rounds = $allRoundsStat->fetchAll(PDO::FETCH_ASSOC);

                        // Ensure $all_rounds is always an array
                        if (!$all_rounds) {
                            $all_rounds = [];
                        }

                        // Calculate color percentages
                        $colors = ['red', 'blue', 'yellow', 'white', 'green', 'pink'];
                        $color_count = array_fill_keys($colors, 0);
                        $total_rounds = count($all_rounds);

                        foreach ($all_rounds as $round) {
                            $winning_results = json_decode($round['winning_result']);
                            if (is_array($winning_results)) {
                                foreach ($winning_results as $color) {
                                    $color = strtolower($color);
                                    if (isset($color_count[$color])) {
                                        $color_count[$color]++;
                                    }
                                }
                            }
                        }

                        $color_percentage = [];
                        foreach ($color_count as $color => $count) {
                            $color_percentage[$color] = $total_rounds > 0 ? ($count / $total_rounds) * 100 : 0;
                        }

                        // Ensure percentages add up to 100%
                        $total_percentage = array_sum($color_percentage);
                        if ($total_percentage > 0) {
                            foreach ($color_percentage as &$percentage) {
                                $percentage = number_format(($percentage / $total_percentage) * 100);
                            }
                        }

                        $board['color_percentage'] = $color_percentage;

                        // Fetch latest 10 rounds for display
                        $sql_latest_rounds = "SELECT round_count,winning_result FROM rounds
                                              WHERE 
                                              `board_id` = :board_id
                                              and
                                              `round_status` = :round_status
                                              ORDER BY round_id DESC
                                              LIMIT 10";
                        $round_status = "finish";
                        $latestRoundsStat = $conn->prepare($sql_latest_rounds);
                        $latestRoundsStat->bindValue(':board_id', $board['board_id']);
                        $latestRoundsStat->bindValue(':round_status', $round_status);
                        $latestRoundsStat->execute();
                        $latest_rounds = $latestRoundsStat->fetchAll(PDO::FETCH_ASSOC);

                        $board['rounds'] = $latest_rounds;

                        // Fetch player count grouped by player_id
                        $sql_players = "SELECT COUNT(DISTINCT player_id) as player_count FROM bets
                                        WHERE 
                                        `board_id` = :board_id";

                        $playersStat = $conn->prepare($sql_players);
                        $playersStat->bindValue(':board_id', $board['board_id']);
                        $playersStat->execute();
                        $player_count = $playersStat->fetch(PDO::FETCH_ASSOC);
                        $board['player_count'] = $player_count['player_count'];
                    }

                    $result = array(
                        "status" => "success",
                        "response_code" => 200,
                        "message" => "Events",
                        "data" => array(
                            "token" => $jwt_token,
                            "event_details" => $rows,
                            "boards" => $rows_boards
                        )
                    );  
                    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
                
                    http_response_code(200);
                    $code = 200;
                } else {
                    // NO AVAILABLE EVENTS
                    $result = array(
                        "status" => 'failed',
                        "message" => "NO AVAILABLE EVENTS!"
                    );  
                    http_response_code(400);
                    $code = 400;
                }
            } else {
                $result = array(
                    "status" => 'failed',
                    "message" => "Insufficient funds",
                    "data" => array("balance" => $rows['current_balance'])
                );  
    
                http_response_code(400);
                $code = 400;
            }
        } else {
            // FAILED
            $result = array(
                "status" => 'failed',
                "message" => "Wrong Operator / Expired token"
            );  

            http_response_code(400);
            $code = 400;
        }
    }
}

function verifyJWT($jwt_token, $secret_key) {
    // Split token into parts
    $parts = explode('.', $jwt_token);
    if (count($parts) !== 3) {
        return null; // Return null if token format is invalid
    }

    // Decode base64 payload
    $payload = json_decode(base64_decode($parts[1]), true);

    // Recreate signature
    $signature = base64_decode($parts[2]);
    $expected_signature = hash_hmac('sha256', "$parts[0].$parts[1]", $secret_key, true);

    // Compare signature with expected signature
    if (hash_equals($signature, $expected_signature) && $payload['exp'] >= time()) {
        return $payload; // Return payload data if token is valid
    } else {
        return null; // Return null if token is invalid or expired
    }
} // Function to verify JWT token

$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'); 		  
header($protocol . " $code " . 'OK'); 
echo json_encode($result); 

// CLOSE DATABASE CONNECTION
$conn = null;  
?>
