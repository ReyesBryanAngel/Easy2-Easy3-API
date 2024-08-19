<?php
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class NumberGamesTest extends TestCase
{
    protected $client;

    protected function setUp(): void
    {
        $this->client = new Client(['base_uri' => 'http://localhost:8000']);
    }

    function dd($variable) {
        echo json_encode($variable, JSON_PRETTY_PRINT);
        die();
    }

    public function testOauthSuccess()
    {
        $oauth = $this->createOauth();
        $oauthResponse = $oauth['response'];
        
        
        $this->assertEquals(200, $oauthResponse['code']);
        $this->assertArrayHasKey('status', $oauthResponse);
        $this->assertArrayHasKey('message', $oauthResponse);
        $this->assertArrayHasKey('token', $oauthResponse);
    }

    public function testStartGameSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $oauthResponse = $oauth['response'];
        $gameType = $oauthPayload['gameType'];
        $token = $oauthResponse['token'];

        $response = $this->client->post("/api/v1/start-game?gameType=$gameType&key=$token");
        $decodeResponse = json_decode($response->getBody(), true);
        
        $this->assertEquals(200, $decodeResponse['code']);
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('sessionDetails', $decodeResponse);
        $this->assertArrayHasKey('exitUrl', $decodeResponse['sessionDetails']);
        $this->assertArrayHasKey('playerBalance', $decodeResponse['sessionDetails']);
    }

    public function testRefreshSsessionSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];
        
        $payload = [
            "playerId" => $playerId
        ];

        $response = $this->client->post('/api/v1/refresh-session', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'json' => $payload
        ]);
        
        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('token', $decodeResponse);
    }

    public function testGetBoardsSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $payload = [
            "gameId" => 2,
            "eventId" => 8,
            "playerId" => $playerId
        ];

        $response = $this->client->post('/api/v1/get-boards', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'json' => $payload
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('data', $decodeResponse);
    }

    public function testChooseBoardSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $payload = [
            "eventId" => 8,
            "boardId" => 13,
            "playerId" => $playerId
        ];

        $response = $this->client->post('/api/v1/choose-board', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'json' => $payload
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
    }

    public function testGetEventsSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $payload = [
            "gameId" => 2,
            "playerId" => $playerId
        ];

        $response = $this->client->post('/api/v1/get-events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
            'json' => $payload
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('data', $decodeResponse);
    }

    public function testGetRoundSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $response = $this->client->get("/api/v1/get-round?playerId=$playerId", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ],
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('round', $decodeResponse);
    }

    public function testGetTransactionsSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $response = $this->client->get("/api/v1/get-transactions?playerId=$playerId", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('data', $decodeResponse);
    }

    public function testBoardValidatorSuccess()
    {
        $oauth = $this->createOauth();
        $oauthPayload = $oauth['payload'];
        $playerId = $oauthPayload['playerId'];
        $oauthResponse = $oauth['response'];
        $token = $oauthResponse['token'];

        $response = $this->client->get("/api/v1/get-transactions?playerId=$playerId", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token
            ]
        ]);

        $decodeResponse = json_decode($response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('status', $decodeResponse);
        $this->assertArrayHasKey('code', $decodeResponse);
        $this->assertArrayHasKey('message', $decodeResponse);
        $this->assertArrayHasKey('data', $decodeResponse);
    }


    //FAILED TEST
    public function testOauthInvalidOperator()
    {
        $mockOauthPayload = [
            "operatorId" => 2,
            "playerId" => "mock player",
            "gameType" => "EASY2",
            "balance" => 1000,
            "secretKey" => "e10adc3949ba59abbe56e057f20f883e"
        ];

        $response = $this->client->post('/api/v1/oauth', [
            'json' => $mockOauthPayload,
            'http_errors' => false
        ]);
        
        $responseData = json_decode($response->getBody(), true);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
    }

    private function createOauth()
    {
        $mockOauthPayload = [
            "operatorId" => 1,
            "playerId" => "mock player",
            "gameType" => "EASY2",
            "balance" => 1000,
            "secretKey" => "e10adc3949ba59abbe56e057f20f883e"
        ];

        $response = $this->client->post('/api/v1/oauth', [
            'json' => $mockOauthPayload
        ]);
        
        $decodeResponse = json_decode($response->getBody(), true);

        return array(
            'payload' => $mockOauthPayload,
            'response' => $decodeResponse
        );
    }
}