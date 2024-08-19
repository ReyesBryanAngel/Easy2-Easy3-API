<?php
namespace v1\numberGames\Requests;

use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Positive;

class numberGamesRequest {
    function validateSession($payload) {
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
    
    function validateBets($payload) {
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
        $selectedNumbers = $payload['selectedNumbers'] ?? '';
        $luckyPick = $payload['luckyPick'] ?? null;
    
        if (isset($payload['luckyPick'])) {
            if (!$payload['luckyPick'] && empty($selectedNumbers)) {
                $errors['selectedNumbers'] = 'selectedNumbers is required when luckyPick is switch off.';
            } elseif ($payload['luckyPick'] && isset($payload['selectedNumbers'])) {
                $errors['selectedNumbers'] = 'selectedNumbers should not be present when luckyPick is true.';
            } else if (!preg_match('/^\d{1,2}-\d{1,2}$/', $selectedNumbers) && !$luckyPick) {
                $errors['selectedNumbers'] = 'selectedNumbers must be in numbers and in the format of "XX-YY".';
            }
        }
    
        if (isset($payload['selectedNumbers'])) {
            list($firstNumber, $secondNumber) = explode('-', $selectedNumbers);
            if ($firstNumber > 38 || $secondNumber > 38) {
                $errors['selectedNumbers'] = 'selectedNumbers should not be greater than 38.';
            }
        }
       
    
        return $errors;
    }
    
    function validateChooseBoard($payload) {
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
    
    function validateDeposit($payload) {
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
    
    function validateWithdraw($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'walletApiKey' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    function validateGetBoards($payload) {
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
    
    function validatePlayerId($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    function validatePlayerIdAndGameId($payload) {
        $validator = Validation::createValidator();
    
        $constraints = new Collection([
            'fields' => [
                'playerId' => [new NotBlank(), new Type('string')],
                'gameId' => [new NotBlank(), new Type('integer')],
            ]
        ]);
    
        return $this->validatePayload($validator, $payload, $constraints);
    }
    
    function validatePayload($validator, $payload, $constraints) {
        $violations = $validator->validate($payload, $constraints);
        $errors = [];
    
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
        }
    
        return $errors;
    }
    
}
