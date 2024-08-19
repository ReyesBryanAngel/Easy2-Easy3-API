<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBoardsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('boards', ['signed' => false]);

        $table->addColumn('event_id', 'integer', ['signed' => false])
              ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addColumn('game_id', 'integer')
              ->addColumn('board_title', 'string', ['limit' => 45])
              ->addColumn('board_description', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('video_source', 'text', ['null' => true])
              ->addColumn('draw_date', 'datetime', ['null' => true])
              ->addColumn('board_status', 'enum', [
                'null' => true,
                'values' => ['pending', 'open', 'close']
              ])
              ->addColumn('board_opened', 'datetime', ['null' => true])
              ->addColumn('board_closed', 'datetime', ['null' => true])
              ->addColumn('board_created', 'datetime', ['null' => true])
              ->addColumn('board_updated', 'datetime', ['null' => true])
              ->addColumn('date_opened', 'datetime', ['null' => true])
              ->addColumn('date_created', 'datetime', ['null' => true])
              ->addColumn('date_updated', 'datetime', ['null' => true])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->addColumn('approver', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('declarator', 'string', ['limit' => 45, 'null' => true])
              ->create();
              
    }
}
