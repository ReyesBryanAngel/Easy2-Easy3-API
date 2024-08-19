<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBetsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('bets', ['signed' => false]);

        $table->addColumn('game_id', 'integer')
              ->addColumn('reference_id', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('operator_id', 'integer', ['signed' => false])
              ->addForeignKey('operator_id', 'operators', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('player_id', 'string', ['limit' => 36])
              ->addColumn('event_id', 'integer', ['signed' => false])
              ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('board_id', 'integer', ['signed' => false])
              ->addForeignKey('board_id', 'boards', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('round_id', 'integer', ['signed' => false])
              ->addForeignKey('round_id', 'rounds', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('round_count', 'integer')
              ->addColumn('bet', 'string', [
                  'null' => true,
                  'limit' => 45 
              ])
              ->addColumn('bet_amount', 'decimal', [
                  'precision' => 10,
                  'scale' => 2,
                  'null' => true,
              ])
              ->addColumn('payout', 'decimal', [
                  'precision' => 10,
                  'scale' => 2,
                  'null' => true,
              ])
              ->addColumn('income', 'decimal', [
                  'precision' => 10,
                  'scale' => 2,
                  'null' => true,
              ])
              ->addColumn('jackpot_contribution', 'decimal', [
                  'precision' => 10,
                  'scale' => 2,
                  'null' => true,
              ])
              ->addColumn('win_type_id', 'integer', ['null' => true])
              ->addColumn('bet_status', 'enum', [
                  'null' => true,
                  'values' => ['PENDING', 'WIN', 'LOSE', 'REFUND']
              ])
              ->addColumn('lucky_pick', 'boolean', ['null' => true])
              ->addColumn('time_of_bet', 'datetime', ['null' => true])
              ->addColumn('updated_at', 'datetime', ['null' => true])
              ->addColumn('settlement_time', 'datetime', ['null' => true])
              ->create();
    }
}
