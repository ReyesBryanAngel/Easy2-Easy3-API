<?php

class Easy2Model
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getMaxRound()
    {
        $query = "SELECT MAX(round) AS max_round FROM easy2_bets";
        $statement = $this->pdo->query($query);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result['max_round'];
    }

    public function queryStartedGame($startedGameId)
    {
        
        $query = "SELECT id, date_close, game_session_id FROM started_games WHERE id = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $startedGameId);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ?? null;
    }

    public function validateEasy2BetPayload($payload)
    {
        $validationRules = require 'validations/easy2bet_rules.php';
        $errors = [];

        foreach ($validationRules as $field => $rules) {
            if (!isset($payload[$field])) {
                continue;
            }

            $fieldRules = explode('|', $rules);
            foreach ($fieldRules as $rule) {
                switch ($rule) {
                    case 'integer':
                        if (gettype($payload[$field]) !== 'integer') {
                            $errors[$field][] = 'The ' . $field . ' must be an integer.';
                        }
                        break;
                    case 'min:10':
                        if ($payload[$field] < 10) {
                            $errors[$field][] = 'The ' . $field . ' must be greater than 10.';
                        }
                        break;
                    case 'max:6':
                        if ($payload[$field] > 6) {
                            $errors[$field][] = 'The ' . $field . ' must be less than 6.';
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($payload[$field])) {
                            $errors[$field][] = 'The ' . $field . ' must be a boolean value.';
                        }
                        break;
                    case 'selected_numbers_format':
                        if (!preg_match('/^\d{1,2}-\d{1,2}$/', $payload[$field])) {
                            $errors[$field][] = 'The ' . $field . ' must be in numbers and in the format of "XX-YY".';
                        } else {
                            list($firstNumber, $secondNumber) = explode('-', $payload[$field]);
                            if ($firstNumber > 31 || $secondNumber > 31) {
                                $errors[$field][] = 'The ' . $field . ' should not be greater than 31.';
                            }
                        }
                        break;
                    case 'consecutive_draws_required_with_advance_draws':
                        if (in_array('advance_draws', $payload) && !isset($payload['consecutive_draws'])) {
                            $errors['consecutive_draws'][] = 'The consecutive_draws field is required when advance_draws is on.';
                        }
                        break;
                    default:
                        break;
                }
                
            }
        }

        return $errors;
    }
    
    public function insertEasy2Bet (
        $startedGameId,
        $round,
        $betAmount, 
        $selectedNumbers, 
        $rambolito, 
        $advanceDraws,
        $consecutiveDraws,
        $luckyPick
    ) {
         $sql = "INSERT INTO easy2_bets (started_game_id, round, bet_amount, selected_numbers, rambolito, advance_draws, consecutive_draws, lucky_pick) 
         VALUES (:started_game_id, :round, :bet_amount, :selected_numbers, :rambolito, :advance_draws, :consecutive_draws, :lucky_pick)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':started_game_id', $startedGameId);
        $stmt->bindParam(':round', $round);
        $stmt->bindParam(':bet_amount', $betAmount);
        $stmt->bindParam(':selected_numbers', $selectedNumbers);
        $stmt->bindParam(':rambolito', $rambolito);
        $stmt->bindParam(':advance_draws', $advanceDraws);
        $stmt->bindParam(':consecutive_draws', $consecutiveDraws);
        $stmt->bindParam(':lucky_pick', $luckyPick);

        $stmt->execute();
    }
}
