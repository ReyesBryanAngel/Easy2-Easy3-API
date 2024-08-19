<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class PlayerCurrentBets {
    use numberGamesTrait;
    private $conn;

    public function __construct($conn) {
        $this->setConnection($conn);
    }

    public function betsInCurrentRound($playerId, $gameId, $eventId, $boardId, $currentRound)
    {
        $query = "SELECT
        bet_id,
        bet,
        round_count,
        bet_amount,
        lucky_pick,
        win_type_id,
        event_id,
        board_id FROM bets WHERE game_id = :gameId AND 
        event_id = :eventId AND 
        board_id = :boardId AND
        round_count = :round_count AND
        player_id = :playerId
        ";
        $statement = $this->conn->prepare($query);
        $statement->bindValue(':playerId', $playerId);
        $statement->bindParam(':gameId', $gameId);
        $statement->bindParam(':eventId', $eventId);
        $statement->bindParam(':boardId', $boardId);
        $statement->bindParam(':round_count', $currentRound);
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResult = [];
            foreach ($result as $key => $value) {
                if ($key === 'bet') {
                    $formattedResult['selectedNumbers'] = $value;
                } else {
                    $formattedResult[$this->snakeToCamelCase($key)] = $value;
                }
            }
            $formattedResults[] = $formattedResult;
        }

        return $formattedResults;
    }
}
