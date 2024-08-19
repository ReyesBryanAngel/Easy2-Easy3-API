<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGameSessionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('game_sessions', ['signed' => false]);

        $table->addColumn('game_id', 'integer')
              ->addColumn('operator_id', 'integer', ['signed' => false])
              ->addForeignKey('operator_id', 'operators', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('player_id', 'string', ['limit' => 36])
              ->addColumn('event_id', 'integer', ['signed' => false])
              ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('board_id', 'integer', ['signed' => false])
              ->addForeignKey('board_id', 'boards', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('board_left', 'boolean', ['null' => true])
              ->addColumn('valid', 'boolean', ['null' => true])
              ->addColumn('balance_withdrawn', 'boolean', ['null' => true])
              ->addColumn('token', 'text', ['null' => true])
              ->addColumn('date_created', 'datetime', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('country_code', 'string', ['limit' => 45, 'null' => true])
              ->create();
              
    }
}
