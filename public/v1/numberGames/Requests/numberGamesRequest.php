<?php
namespace v1\numberGames\Requests;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Positive;

class numberGamesRequest {
    public function validateSession($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'operatorId' => [new NotBlank(), new Type('integer')],
                'playerId' => [new NotBlank(), new Type('string')],
                'gameType' => [new NotBlank(), new Type('string')],
                'balance' => [new NotBlank(), new Type('integer')],
                'secretKey' => [new NotBlank()],
            ],
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validateBets($payload, $gameId) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'winTypeId' => [new NotBlank(), new Type('integer')],
                'playerId' => [new NotBlank(), new Type('string')],
                'betAmount' => [new NotBlank(), new Type('integer'), new Positive()],
                'luckyPick' => [new Type('bool')],
            ],
            'allowExtraFields' => true,
            'allowMissingFields' => false,
        ]);
    
        $errors = $this->validatePayload($validator, $payload, $constraints);
    
        if (in_array($gameId, [2, 3])) {
            $this->validateSelectedNumbers(
                $gameId, 
                $payload['luckyPick'] ?? null, 
                $payload['selectedNumbers'] ?? null, 
                $payload['winTypeId'] ?? null,
                $errors
            );
        }
    
        
        return $errors;
    }
    
    public function validateChooseBoard($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'eventId' => [new NotBlank(), new Type('integer')],
                'boardId' => [new NotBlank(), new Type('integer')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validateDeposit($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'balance' => [new NotBlank(), new Type('integer')],
                'walletApiKey' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validateWithdraw($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'walletApiKey' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validateGetBoards($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'gameId' => [new NotBlank(), new Type('integer')],
                'eventId' => [new NotBlank(), new Type('integer')],
                'playerId' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validatePlayerAndBoard($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'gameId' => [new NotBlank(), new Type('integer')],
                'eventId' => [new NotBlank(), new Type('integer')],
                'boardId' => [new NotBlank(), new Type('integer')],
                'playerId' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validatePlayerId($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validatePlayerIdAndGameId($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'gameId' => [new NotBlank(), new Type('integer')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    public function validateCurrentBoard($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'gameId' => [new NotBlank(), new Type('integer')],
                'boardId' => [new NotBlank(), new Type('integer')],
                'playerId' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    private function validatePayload($validator, $payload, $constraints) {
        $violations = $validator->validate($payload, $constraints);
        $errors = [];
    
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }
    
        return $errors;
    }
    
    private function validateSelectedNumbers($gameId, $luckyPick, $selectedNumbers, $winTypeId, &$errors) {
        
        $formats = [
            2 => '/^\d{1,2}-\d{1,2}$/',
            3 => '/^\d-\d-\d$/'
        ];
        
        $maxValues = [
            2 => 38,
            3 => 9
        ];
        
        if (!$luckyPick && empty($selectedNumbers)) {
            $errors['selectedNumbers'] = 'selectedNumbers is required when luckyPick is switched off.';
        } elseif ($luckyPick && isset($selectedNumbers)) {
            $errors['selectedNumbers'] = 'selectedNumbers should not be present when luckyPick is on.';
        } elseif (isset($selectedNumbers)) {
            $this->extendedValidationForBet($selectedNumbers, $formats[$gameId], $maxValues[$gameId], $errors);
            $this->validateRambolito($selectedNumbers, $winTypeId, $gameId, $errors);
        }
    }

    private function extendedValidationForBet($selectedNumbers, $format, $maxValue, &$errors)
    {
        if (!preg_match($format, $selectedNumbers)) {
            $errors['selectedNumbers'] = 'selectedNumbers must be in numbers and in the correct format.';
            return;
        }

        $numbers = explode('-', $selectedNumbers);
        foreach ($numbers as $number) {
            if ($number > $maxValue) {
                $errors['selectedNumbers'] = "Each number should not be greater than $maxValue.";
                return;
            }
        }
    }

    private function validateRambolito($selectedNumbers, $winTypeId, $gameId, &$errors)
    {
        $numbers = explode('-', $selectedNumbers);

        if ($winTypeId == 5) {
            if ($gameId == 2 && $numbers[0] == $numbers[1]) {
                $errors['selectedNumbers'] = 'Pair of numbers are not allowed to be equal when playing rambolito.';
            } elseif ($gameId == 3 && $numbers[0] == $numbers[1] && $numbers[1] == $numbers[2]) {
                $errors['selectedNumbers'] = 'Pair of numbers are not allowed to be equal when playing rambolito.';
            }
        }
    }
}
