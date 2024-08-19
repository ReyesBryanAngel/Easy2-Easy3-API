<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEventsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('events', ['signed' => false]);

        $table->addColumn('game_id', 'integer')
              ->addColumn('admin_id', 'integer', ['null' => true])
              ->addColumn('event_name', 'string', ['limit' => 45])
              ->addColumn('event_description', 'string', ['limit' => 200])
              ->addColumn('event_date', 'datetime', ['null' => true])
              ->addColumn('date_opened', 'datetime', ['null' => true])
              ->addColumn('event_open', 'datetime', ['null' => true])
              ->addColumn('event_closed', 'datetime', ['null' => true])
              ->addColumn('date_closed', 'datetime', ['null' => true])
              ->addColumn('date_updated', 'datetime', ['null' => true])
              ->addColumn('date_created', 'timestamp', ['null' => true])
              ->addColumn('event_status', 'enum', [
                'null' => true,
                'values' => ['pending', 'open', 'closed']
              ])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->create();
    }
}
