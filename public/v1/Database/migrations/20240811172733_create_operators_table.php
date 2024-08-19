<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOperatorsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('operators', ['signed' => false]);

        $table->addColumn('admin_id', 'integer', ['null' => true])
              ->addColumn('company_name', 'string', ['limit' => 45])
              ->addColumn('company_code', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('description', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('bet_minlimit', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('bet_maxlimit', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('straight_win_rate', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('rambolito_win_rate', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('game_api_key', 'text', ['null' => true])
              ->addColumn('wallet_api_key', 'text', ['null' => true])
              ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('date_created', 'timestamp', ['null' => true])
              ->addColumn('date_updated', 'datetime', ['null' => true])
              ->addColumn('fund', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('exit_url', 'string', ['limit' => 45, 'null' => true])
              ->addColumn('updated_by', 'integer', ['null' => true])
              ->create();
    }
}
