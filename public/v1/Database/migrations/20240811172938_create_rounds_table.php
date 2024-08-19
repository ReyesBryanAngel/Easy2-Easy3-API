<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRoundsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('rounds', ['signed' => false]);

        $table->addColumn('event_id', 'integer', ['signed' => false])
              ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('board_id', 'integer', ['signed' => false])
              ->addForeignKey('board_id', 'boards', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('round_count', 'integer')
              ->addColumn('game_id', 'integer')
              ->addColumn('winning_result', 'text', ['null' => true])
              ->addColumn('win_type_id', 'integer', ['null' => true])
              ->addColumn('jackpot_id', 'integer', ['null' => true])
              ->addColumn('round_type', 'integer', ['null' => true])
              ->addColumn('jackpot_payout', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
             ])
             ->addColumn('jackpot_contri_amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
             ])
             ->addColumn('round_started', 'datetime', ['null' => true])
             ->addColumn('round_declared', 'datetime', ['null' => true])
             ->addColumn('round_closed', 'datetime', ['null' => true])
             ->addColumn('round_updated', 'datetime', ['null' => true])
             ->addColumn('round_created', 'datetime', ['null' => true])
             ->addColumn('round_status', 'enum', [
                'null' => true,
                'values' => ['pending', 'open', 'close']
            ])
            ->addColumn('updated_by', 'integer', ['null' => true])
            ->create();
    }
}
