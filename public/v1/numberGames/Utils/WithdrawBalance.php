<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class WithdrawBalance {
    use numberGamesTrait;
    private $conn;

    public function __construct($conn) {
        $this->setConnection($conn);
    }

    public function getOperatorOfPLayerWithdrawing($operatorIdOfPlayerWithdrawing)
    {
        $query = "SELECT * from operators WHERE operator_id = :operator_id";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(':operator_id', $operatorIdOfPlayerWithdrawing);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? null;
    }

    public function endSession($playerId)
    {
        $sqlSession = "UPDATE game_session SET balance_withdrawn = true, valid = false, expiration_date = null WHERE player_id = :playerId";
        $stmtSession = $this->conn->prepare($sqlSession);
        $stmtSession->bindParam(':playerId', $playerId);
        $stmtSession->execute();
    }

    public function insertTransactions ( 
        $operatorId, 
        $playerId, 
        $latestBalance,
        $referenceId,
        $signature,
    )
    {
        $cashOut = 2;
        $currentBalance = 0;
        $currentDate = date('Y-m-d H:i:s');
        $sql = "INSERT INTO transactions ( 
            transaction_status_id, 
            operator_id, 
            player_id, 
            reference_id, 
            signature, 
            date_created, 
            previous_bal, 
            current_bal,
            amount
        ) 
            VALUES (
                :transaction_status_id, 
                :operator_id, 
                :player_id, 
                :reference_id, 
                :signature, 
                :date_created, 
                :previous_bal,
                :current_bal,
                :amount
            )";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':transaction_status_id', $cashOut);
            $stmt->bindParam(':operator_id', $operatorId);
            $stmt->bindParam(':player_id', $playerId);
            $stmt->bindParam(':reference_id', $referenceId);
            $stmt->bindParam(':signature', $signature);
            $stmt->bindParam(':date_created', $currentDate);
            $stmt->bindParam(':previous_bal', $latestBalance);
            $stmt->bindParam(':current_bal', $currentBalance);
            $stmt->bindParam(':amount', $currentBalance);
            $stmt->execute();
    }
}