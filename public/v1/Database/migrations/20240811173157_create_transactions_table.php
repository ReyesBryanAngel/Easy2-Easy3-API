<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTransactionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('transactions', ['signed' => false]);

        $table->addColumn('transaction_status_id', 'integer', ['signed' => false]);
        $table->addForeignKey('transaction_status_id', 'transaction_statuses', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('operator_id', 'integer', ['signed' => false])
              ->addForeignKey('operator_id', 'operators', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('player_id', 'string', ['limit' => 36])
              ->addColumn('event_id', 'integer', ['signed' => false])
              ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('board_id', 'integer', ['signed' => false])
              ->addForeignKey('board_id', 'boards', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('round_count', 'integer')
              ->addColumn('amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true
             ])
             ->addColumn('reference_id', 'string', ['limit' => 45, 'null' => true])
             ->addColumn('description', 'string', ['limit' => 100, 'null' => true])
             ->addColumn('signature', 'text', ['null' => true])
             ->addColumn('date_created','datetime', ['null' => true])
             ->addColumn('date_updated','datetime', ['null' => true])
             ->addColumn('previous_bal', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true
             ])
             ->addColumn('current_bal', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true
             ])->create();
    }
}
