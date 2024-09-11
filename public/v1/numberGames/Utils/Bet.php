<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class Bet {
    use numberGamesTrait;
    private $conn;

    public function __construct($conn) {
        $this->setConnection($conn);
    }

    public function insertBets(
        $winTypeId,
        $eventId, 
        $boardId,
        $roundId,
        $roundCount,
        $selectedNumbers, 
        $betAmount, 
        $operatorId,
        $playerId,
        $luckyPick,
        $referenceId,
        $dateTimeNow,
        $gameType
    ) {
        $betStatus = "PENDING";
            $gameId = $gameType === "EASY2" ? 2 : 3; 
            $sql = "INSERT INTO bets (
                win_type_id,
                event_id,
                board_id,
                bet,
                round_id,
                round_count,
                bet_amount,
                bet_status,
                operator_id,
                player_id,
                lucky_pick,
                time_of_bet, 
                reference_id,
                game_id
            ) VALUES (
                :win_type_id,
                :event_id,
                :board_id,
                :bet,
                :round_id,
                :round_count,
                :bet_amount, 
                :bet_status, 
                :operator_id, 
                :player_id,
                :lucky_pick,
                :time_of_bet, 
                :reference_id,
                :game_id
            )";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':win_type_id', $winTypeId);
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':board_id', $boardId);
            $stmt->bindParam(':round_id', $roundId);
            $stmt->bindParam(':round_count', $roundCount);
            $stmt->bindParam(':bet', $selectedNumbers);
            $stmt->bindParam(':bet_amount', $betAmount);
            $stmt->bindParam(':bet_status', $betStatus);
            $stmt->bindParam(':operator_id', $operatorId);
            $stmt->bindParam(':player_id', $playerId);
            $stmt->bindParam(':lucky_pick', $luckyPick);
            $stmt->bindParam(':reference_id', $referenceId);
            $stmt->bindParam(':time_of_bet', $dateTimeNow);
            $stmt->bindParam(':game_id', $gameId);
            $stmt->execute();

            return $this->conn->lastInsertId();
    }

    public function insertTransaction(
        $operatorId, 
        $betAmount, 
        $playerId,
        $eventId,
        $boardId,
        $roundCount,
        $previousBalance, 
        $currentBalance, 
        $referenceId,
        $signature,
        $transactionType,
    )
    {
        $sql = "INSERT INTO transactions (
            transaction_status_id, 
            operator_id, 
            amount, 
            player_id,
            event_id,
            board_id,
            round_count,
            date_created, 
            previous_bal, 
            current_bal,
            reference_id,
            signature
        ) 
        VALUES (
            :transaction_status_id, 
            :operator_id, 
            :amount, 
            :player_id,
            :event_id,
            :board_id,
            :round_count,
            :date_created, 
            :previous_bal, 
            :current_bal,
            :reference_id,
            :signature
        )";
        $dateTimeNow = date('Y-m-d H:i:s');
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':transaction_status_id', $transactionType);
        $stmt->bindParam(':operator_id', $operatorId);
        $stmt->bindParam(':amount', $betAmount);
        $stmt->bindParam(':player_id', $playerId);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':board_id', $boardId);
        $stmt->bindParam(':round_count', $roundCount);
        $stmt->bindParam(':date_created', $dateTimeNow);
        $stmt->bindParam(':previous_bal', $previousBalance);
        $stmt->bindParam(':current_bal', $currentBalance);
        $stmt->bindParam(':reference_id', $referenceId);
        $stmt->bindParam(':signature', $signature);
        $stmt->execute();

    }

    public function luckPickGenerator2($winTypeId)
    {
        $firstNumber = rand(1, 38);
        $secondNumber = rand(1, 38);

        if ($winTypeId == 5) {
            while ($secondNumber == $firstNumber) {
                $secondNumber = rand(1, 38);
            }
        }

        return "$firstNumber-$secondNumber";
    }
    

    public function luckPickGenerator3($winTypeId)
    {
        $firstDigit = rand(1, 9);
        $secondDigit = rand(1, 9);
        $thirdDigit = rand(1, 9);

        if ($winTypeId == 5) {
            while ($secondDigit == $firstDigit) {
                $secondDigit = rand(1, 9);
            }
            while ($thirdDigit == $firstDigit || $thirdDigit == $secondDigit) {
                $thirdDigit = rand(1, 9);
            }
        }

        return "$firstDigit-$secondDigit-$thirdDigit";
    }
}
