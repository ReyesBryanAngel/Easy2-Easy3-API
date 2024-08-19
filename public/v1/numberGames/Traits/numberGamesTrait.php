<?php
namespace v1\numberGames\Traits;

use PDO;

trait numberGamesTrait {
    private $conn;

    public function setConnection(PDO $conn) {
        $this->conn = $conn;
    }

    public function queryOperators($operatorId)
    {
        $query = "SELECT * FROM operators WHERE operator_id = :value";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':value', $operatorId);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Operator does not exist.',
            ], 400); 
        }
        
        return $result ?? null;
    }

    public function tracePlayerOperator($playerId)
    {
        $query = "SELECT 
            game_session.*, 
            operators.straight_win_rate, 
            operators.rambolito_win_rate,
            operators.bet_minlimit,
            operators.bet_maxlimit
        FROM 
            game_session 
        LEFT JOIN 
            (SELECT operator_id, straight_win_rate, rambolito_win_rate, bet_minlimit, bet_maxlimit FROM operators) AS operators ON 
            game_session.operator_id = operators.operator_id WHERE
            game_session.player_id = :playerId
        "; 

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':playerId', $playerId);
        $statement->execute();

        $operatorResult = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$operatorResult) {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player is not found.',
            ], 400); 
        }

        return $operatorResult;
    }

    public function queryGameSession($playerId)
    {
        $query = "SELECT * FROM game_session WHERE player_id = :playerId";
        $statement =$this->conn->prepare($query);
        $statement->bindParam(':playerId', $playerId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result;
    }

    function querySessionViaToken($token, $oauth)
    {
        $query = "SELECT operator_id, game_id, player_id, expiration_date FROM game_session WHERE token = :token";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':token', $token);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if(!$result) {
            return $oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Token does not exist.'
            ], 400);
        }

        return $result ?? null;
    }

    public function queryEvent($gameId, $eventIdFromBoard)
    {
        $query = "SELECT * FROM events WHERE event_id = :eventIdFromBoard AND game_id = :gameId";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':gameId', $gameId);
        $statement->bindParam(':eventIdFromBoard', $eventIdFromBoard);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? false;
    }

    public function queryOpenBoards($gameId, $boardId, $eventId)
    {
        $query = "SELECT * FROM boards WHERE 
            board_id = :boardId AND 
            event_id = :eventId AND 
            game_id = :gameId AND
            board_status = 'open'
        ";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':gameId', $gameId);
        $statement->bindParam(':boardId', $boardId);
        $statement->bindParam(':eventId', $eventId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Board is closed',
            ], 400); 
        }

        return $result;
    }

    public function queryCurrentRound($gameId, $eventId, $boardId)
    {
        $query = "SELECT * FROM rounds WHERE 
            game_id = :gameId AND 
            event_id = :eventId AND
            board_id = :boardId AND
            round_status = 'open'
        ";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':gameId', $gameId);
        $statement->bindParam(':eventId', $eventId);
        $statement->bindParam(':boardId', $boardId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Round cannot be identified in this board',
            ], 400); 
        }

        return $result;
    }

    public function getTransactions($playerId)
    {
        $query = "SELECT transaction_status_id, amount, current_bal from transactions WHERE player_id = :player_id 
        ORDER BY date_created DESC";

        $statement = $this->conn->prepare($query);
        $statement->bindParam(':player_id', $playerId);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $results ?? [];
    }

    public function generateUniqueReferenceId($length)
    {
        $maxAttempts = 10;
        $attempts = 0;

        do {
            $ref = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
            $attempts++;
        } while ($this->checkReferenceIdExists($ref) && $attempts < $maxAttempts);

        return $ref;
    }

    public function checkReferenceIdExists($code)
    {
        $sql_check_ref = "SELECT COUNT(*) FROM transactions WHERE `reference_id` = :reference_id";

        $sthAuth = $this->conn->prepare($sql_check_ref);
        $sthAuth->bindParam(':reference_id', $code);
        $sthAuth->execute();
        $count = $sthAuth->fetchColumn();

        return $count > 0;
    }

    public function dd($variable) {
        echo json_encode($variable, JSON_PRETTY_PRINT);
        die();
    }
    
    public function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    public function snakeToCamelCase($str)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
    }

    public function queryEvents($payload, $oauth)
    {
        $query = "SELECT * FROM events WHERE game_id = :game_id AND event_status = 'open' ORDER BY event_date ASC";
        $statement = $this->conn->prepare($query);
    
        $statement->bindParam(':game_id', $payload['gameId']);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        
        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResult = [];
            foreach ($result as $key => $value) {
                $formattedResult[$oauth->snakeToCamelCase($key)] = $value;
            }
            $formattedResults[] = $formattedResult;
        }

        return $formattedResults;
    }
}
