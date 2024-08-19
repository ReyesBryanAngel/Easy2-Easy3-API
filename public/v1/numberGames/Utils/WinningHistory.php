<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class WinningHistory {
    use numberGamesTrait;
    private $conn;

    public function __construct(PDO $conn) {
        $this->setConnection($conn);
    }

    public function mergeDateAndWonBets($wonBets, $dateOfWonBets)
    {
        $resultArray = [];
        if ($dateOfWonBets !== null) {
            $count = min(
                count($wonBets),
                count($dateOfWonBets)
            );
    
            for ($i = 0; $i < $count; $i++) {
                $resultArray[] = array_merge(
                    (array)$wonBets[$i],
                    (array) $dateOfWonBets[$i]
                );
            }
        }

        return $resultArray;
    }

    public function dateOfWonBets($wonBets)
    {
        foreach ($wonBets as $wonBet) {
            $roundCount = $wonBet['round_count'];
            $sql = "SELECT round_created FROM rounds WHERE round_count = :roundCount";
            $wonBetStatement = $this->conn->prepare($sql);
            $wonBetStatement->bindParam(':roundCount', $roundCount);
            $wonBetStatement->execute();
            $result = $wonBetStatement->fetchAll(PDO::FETCH_ASSOC);

            return $result ?? null;
        }
    }

    public function wonBets($gameId, $playerId)
    {
        $sql = "SELECT bet_id, bet, updated_at, round_count FROM bets WHERE 
            bet_status = 'WIN' AND 
            game_id = :gameId AND
            player_id = :playerId
        ";

        $statement = $this->conn->prepare($sql);
        $statement->bindParam(':gameId', $gameId);
        $statement->bindParam(':playerId', $playerId);
        $statement->execute();
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result ?? null;
    }
}