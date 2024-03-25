<?php

class OperatorModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function insertOperatorData($operatorName, $apiKey) {
        $sql = "INSERT INTO operators (operator_name, api_key) 
                VALUES (?, ?)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(1, $operatorName);
        $stmt->bindParam(2, $apiKey);

        $stmt->execute();

        return $this->pdo->lastInsertId();
    }
}

