<?php

use Cycle\Migrations\Migration;

/**
 * Migration for creating Arma Reforger Server Manager tables.
 */
class CreateReforgerTablesMigration extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Create reforger_servers table
        $this->database()
            ->table('reforger_servers')
            ->addColumn('id', 'primary')
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('install_path', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('bind_address', 'string', ['length' => 255, 'default' => '0.0.0.0'])
            ->addColumn('bind_port', 'integer', ['default' => 2001])
            ->addColumn('public_address', 'string', ['length' => 255, 'default' => ''])
            ->addColumn('public_port', 'integer', ['default' => 2001])
            ->addColumn('a2s_port', 'integer', ['default' => 0])
            ->addColumn('steam_query_port', 'integer', ['default' => 0])
            ->addColumn('admin_password', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('server_password', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('server_name', 'string', ['length' => 255, 'default' => 'Arma Reforger Server'])
            ->addColumn('scenario_id', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('max_players', 'integer', ['default' => 64])
            ->addColumn('visible', 'boolean', ['default' => true])
            ->addColumn('cross_platform', 'boolean', ['default' => false])
            ->addColumn('battle_eye', 'boolean', ['default' => true])
            ->addColumn('third_person_view', 'boolean', ['default' => false])
            ->addColumn('von_disabled', 'boolean', ['default' => false])
            ->addColumn('status', 'string', ['length' => 50, 'default' => 'stopped'])
            ->addColumn('pid', 'integer', ['nullable' => true])
            ->addColumn('config_json', 'text', ['nullable' => true])
            ->addColumn('installed_version', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('last_started', 'datetime', ['nullable' => true])
            ->addColumn('last_stopped', 'datetime', ['nullable' => true])
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        // Create reforger_mods table
        $this->database()
            ->table('reforger_mods')
            ->addColumn('id', 'primary')
            ->addColumn('workshop_id', 'string', ['length' => 255])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('description', 'text', ['nullable' => true])
            ->addColumn('author', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('version', 'string', ['length' => 50, 'nullable' => true])
            ->addColumn('image_url', 'string', ['length' => 500, 'nullable' => true])
            ->addColumn('workshop_url', 'string', ['length' => 500, 'nullable' => true])
            ->addColumn('file_size', 'bigInteger', ['nullable' => true])
            ->addColumn('last_updated', 'datetime', ['nullable' => true])
            ->addColumn('is_downloaded', 'boolean', ['default' => false])
            ->addColumn('local_path', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['workshop_id'], ['unique' => true])
            ->create();

        // Create reforger_server_mods pivot table
        $this->database()
            ->table('reforger_server_mods')
            ->addColumn('id', 'primary')
            ->addColumn('server_id', 'integer')
            ->addColumn('mod_id', 'integer')
            ->addColumn('load_order', 'integer', ['default' => 0])
            ->addColumn('enabled', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['nullable' => true])
            ->addIndex(['server_id', 'mod_id'], ['unique' => true])
            ->addForeignKey(['server_id'], 'reforger_servers', ['id'], [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->addForeignKey(['mod_id'], 'reforger_mods', ['id'], [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        $this->database()->table('reforger_server_mods')->drop();
        $this->database()->table('reforger_mods')->drop();
        $this->database()->table('reforger_servers')->drop();
    }
}
