<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class DepositBalance {
    use numberGamesTrait;
    private $conn;

    public function __construct($conn) {
        $this->setConnection($conn);
    }

    public function insertTransactions($playerId, $balance, $walletApiKeyPayload, $updatedAt, $referenceId, $errors)
    {
        $this->queryGameSession($playerId);
        $resultTransactions = $this->queryTransactions($playerId);
        $operatorId = $resultTransactions['operator_id'] ?? null;
        $walletApiKey = $this->queryWalletApiKeyOfOperator($operatorId);

        $signature = md5($operatorId.$playerId);
        $previousBalance = $this->getPreviousBalance($playerId);
        $balanceAfterDeposit = $previousBalance + $balance;
        $cashIn = 1;
        $currentDate = date('Y-m-d H:i:s');

        switch (true) {
            case !empty($errors):
                $this->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => $errors
                ], 422);
                break;
            case $walletApiKeyPayload !== $walletApiKey:
                $this->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'You have entered an invalid wallet api key.',
                ], 422);
                break;
            default:
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
                    $stmt->bindParam(':operator_id', $operatorId);
                    $stmt->bindParam(':player_id', $playerId);
                    $stmt->bindParam(':reference_id', $referenceId);
                    $stmt->bindParam(':signature', $signature);
                    $stmt->bindParam(':date_created', $currentDate);
                    $stmt->bindParam(':previous_bal', $previousBalance);
                    $stmt->bindParam(':current_bal', $balanceAfterDeposit);
                    $stmt->bindParam(':amount', $balance);
                    $stmt->execute();;
        
                $this->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Deposit successfully!.',
                    'balance' => $balanceAfterDeposit
                ], 200);
                break;    
        }
    }

    function updateTransactions($playerId, $balance, $walletApiKeyPayload, $errors)
    {
        $this->queryGameSession($playerId);
        $previousBalance = $this->getPreviousBalance($playerId);
        $balanceAfterDeposit = $previousBalance + $balance;  
        $resultTransactions = $this->queryTransactions($playerId);
        $latestDate = $resultTransactions['date_created'];
        $operatorId = $resultTransactions['operator_id'];
        $walletApiKey = $this->queryWalletApiKeyOfOperator($operatorId);

        switch (true) {
            case $walletApiKeyPayload !== $walletApiKey:
                $this->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'You have entered an invalid wallet api key.',
                ], 422);
                break;
            case !empty($errors):
                $this->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => $errors
                ], 422);
                break;
            default:
                $updateTransactions = "UPDATE transactions set current_bal = :balance, amount = :balance WHERE player_id = :player_id AND date_created = :date_created";
                $stmtUpdateTransac = $this->conn->prepare($updateTransactions);
                $stmtUpdateTransac->bindParam(':balance', $balance);
                $stmtUpdateTransac->bindParam(':date_created', $latestDate);
                $stmtUpdateTransac->bindParam(':player_id', $playerId);
                $stmtUpdateTransac->execute();
        
                $this->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Deposit successfully!.',
                    'balance' => $balanceAfterDeposit
                ], 200);
                break;
        }
    }

    public function queryTransactions($playerId)
    {
        $queryTransactions = "SELECT operator_id, date_created, current_bal FROM transactions WHERE player_id = :player_id ORDER BY date_created DESC LIMIT 1";
        $stmtTransactions = $this->conn->prepare($queryTransactions);
        $stmtTransactions->bindParam(':player_id', $playerId);
        $stmtTransactions->execute();
        $resultTransactions = $stmtTransactions->fetch(PDO::FETCH_ASSOC);

        return $resultTransactions ?? null;
    }

    public function queryWalletApiKeyOfOperator($operatorId)
    {
        $queryOperator = "SELECT operator_id, wallet_api_key from operators WHERE operator_id = :operator_id";
        $stmtOperators = $this->conn->prepare($queryOperator);
        $stmtOperators->bindParam(':operator_id', $operatorId);
        $stmtOperators->execute();
        $resultOperators = $stmtOperators->fetch(PDO::FETCH_ASSOC);
        $walletApiKey = $resultOperators['wallet_api_key'] ?? null;

        return $walletApiKey ?? null;
    }

    public function getPreviousBalance($playerId) {
        $sql = "SELECT current_bal FROM transactions WHERE player_id = :playerId ORDER BY date_created DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':playerId', $playerId);
        $stmt->execute();

        return $stmt->fetchColumn();
    }
}