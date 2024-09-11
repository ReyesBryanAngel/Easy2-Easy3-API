<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class Oauth {
    use numberGamesTrait;
    private $conn;

    public function __construct(PDO $conn) {
        $this->setConnection($conn);
    }

    public function insertCgGameSession($playerId, $operatorId, $generatedToken, $gameId)
    {
        $sql = "INSERT INTO game_session (game_id, token, valid, balance_withdrawn, date_created, expiration_date, player_id, operator_id) 
                    VALUES (:game_id, :token, :valid, :balance_withdrawn, :date_created, :expiration_date, :player_id, :operator_id)";
        $valid = 1;
        $balanceWithdrawn = 0;
        // $gameId = $gameType === "EASY2" ? 2 : 3;
        $expiration = time() + (15 * 60);
        $expirationDate = date('Y-m-d H:i:s', $expiration); 
        $dateString = date('Y-m-d H:i:s');

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':game_id', $gameId);
        $stmt->bindParam(':token', $generatedToken);
        $stmt->bindParam(':valid', $valid);
        $stmt->bindParam(':balance_withdrawn', $balanceWithdrawn);
        $stmt->bindParam(':date_created', $dateString);
        $stmt->bindParam(':expiration_date', $expirationDate);
        $stmt->bindParam(':player_id', $playerId);
        $stmt->bindParam(':operator_id', $operatorId);
        $stmt->execute();
        
        return $generatedToken;
    }

    public function insertOrUpdateSession($playerId, $operatorId, $generatedToken, $gameId)
    {
        $sql = "SELECT token FROM game_session WHERE player_id = :player_id AND operator_id = :operator_id";
       
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':player_id', $playerId);
        $stmt->bindParam(':operator_id', $operatorId);
        $stmt->execute();
        $rows = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $expirationDate = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        if ($rows != false) {
            $sqlUpdateExpiration = "UPDATE game_session SET
                game_id = :gameId,
                expiration_date = :expiration_date, 
                token = :generated_token, 
                valid = :valid, 
                balance_withdrawn = 0 WHERE 
                token = :token
            ";
            $validUpdate = 1;
            $sthUpdate = $this->conn->prepare($sqlUpdateExpiration);
            $sthUpdate->bindParam(':gameId', $gameId);
            $sthUpdate->bindParam(':generated_token', $generatedToken);
            $sthUpdate->bindParam(':expiration_date', $expirationDate);
            $sthUpdate->bindParam(':token', $rows['token']);
            $sthUpdate->bindParam(':valid', $validUpdate);
            $sthUpdate->execute();
        } else {
            $insertCgSession = $this->insertCgGameSession($playerId, $operatorId, $generatedToken, $gameId);
        }   
        
        return $rows != false ? $rows['token'] : $insertCgSession;
    }

    public function queryLatestTransactions($playerId)
    {
        $checkUserTransaction = "SELECT * FROM transactions t
        LEFT JOIN transaction_status ts ON t.transaction_status_id = ts.transaction_status_id
        WHERE t.player_id = :player_id ORDER BY transaction_id DESC";

        $transactionAuth = $this->conn->prepare($checkUserTransaction);
        $transactionAuth->bindParam(':player_id', $playerId, PDO::PARAM_STR);
        $transactionAuth->execute();
        $userTransaction = $transactionAuth->fetchAll(PDO::FETCH_ASSOC);

        return $userTransaction ?? [];
    }

    public function querySummationTransaction($playerId)
    {
        $checUser = "SELECT * FROM summation_transaction  
        WHERE 
        `player_id` = :player_id";
        
            $checkUserAuth = $this->conn->prepare($checUser);
            $checkUserAuth->bindParam(':player_id', $playerId, PDO::PARAM_STR);
            $checkUserAuth->execute();
            $summationTransaction = $checkUserAuth->fetchAll(PDO::FETCH_ASSOC);

            return $summationTransaction ?? [];
    }

    public function tokenExtend($generatedToken, $expireAt, $payload)
    {
        $sql = "UPDATE game_session SET expiration_date = :expirationDate, token = :token WHERE player_id = :playerId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $generatedToken);
        $stmt->bindParam(':expirationDate', $expireAt);
        $stmt->bindParam(':playerId', $payload['playerId']);
        $stmt->execute();
    }

    public function insertTransactions ( 
        $operatorIdFromDb, 
        $playerId, 
        $balance,
        $referenceId,
        $signature,
        $previousBalance,
        $isBalanceWithdrawn
    )
        {
            $cashIn = 1;
            $updatedBalance = $previousBalance + $balance;
            $totalBalance = $isBalanceWithdrawn ? $balance : $updatedBalance;
            
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
                $stmt->bindParam(':transaction_status_id', $cashIn);
                $stmt->bindParam(':operator_id', $operatorIdFromDb);
                $stmt->bindParam(':player_id', $playerId);
                $stmt->bindParam(':reference_id', $referenceId);
                $stmt->bindParam(':signature', $signature);
                $stmt->bindParam(':date_created', $currentDate);
                $stmt->bindParam(':previous_bal', $balance);
                $stmt->bindParam(':current_bal', $totalBalance);
                $stmt->bindParam(':amount', $balance);
                $stmt->execute();
    }

}