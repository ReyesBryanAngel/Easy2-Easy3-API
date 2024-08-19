<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TransactionStatusSeeder extends AbstractSeed
{
    public function run(): void
    {
        $table = $this->table('transaction_statuses');

        $rows = [
            [
                'id' => 1,
                'title' => 'Cashin',
                'type' => 'ADD'
            ],
            [
                'id' => 2,
                'title' => 'Cashout',
                'type' => 'MINUS'
            ],
            [
                'id' => 3,
                'title' => 'Place Bet',
                'type' => 'MINUS'
            ],
            [
                'id' => 4,
                'title' => 'Payout',
                'type' => 'ADD'
            ]
        ];

        $table->insert($rows)->saveData();
    }
}
