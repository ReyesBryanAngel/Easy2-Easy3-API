<?php

class InternalWalletModel {
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function queryPlayerWallet($playerName)
    {
        $query = "SELECT id, player_name FROM internal_wallets WHERE player_name = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $playerName);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? null;
    }

    public function insertInternalWallet($operatorId, $playerName, $balance)
    {
        $createdAt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO internal_wallets (operator_id, player_name, balance, created_at) 
                VALUES (:operator_id, :player_name, :balance, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':operator_id', $operatorId);
        $stmt->bindParam(':player_name', $playerName);
        $stmt->bindParam(':balance', $balance);
        // $stmt->bindParam(':type_of_transaction', $typeOfTransaction);
        $stmt->bindParam(':created_at', $createdAt);

        $stmt->execute();

        return $this->pdo->lastInsertId();
    }
}
