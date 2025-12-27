<?php

namespace Flute\Modules\ArmaReforgerServerManager;

use Flute\Core\Database\Entities\NavbarItem;
use Flute\Core\Database\Entities\Permission;
use Flute\Core\ModulesManager\ModuleInformation;
use Flute\Core\Support\AbstractModuleInstaller;

class Installer extends AbstractModuleInstaller
{
    public function __construct()
    {
        parent::__construct('ArmaReforgerServerManager');
    }

    /**
     * Install the module.
     */
    public function install(ModuleInformation &$module): bool
    {
        // Run database migrations
        $this->importMigrations();

        // Create permissions
        $this->createPermissions();

        // Create default configuration
        $this->createDefaultConfig();

        return true;
    }

    /**
     * Uninstall the module.
     */
    public function uninstall(ModuleInformation &$module): bool
    {
        // Permissions will be cleaned up automatically by the module manager
        // Database tables can be kept for data preservation

        return true;
    }

    /**
     * Get the navigation item for this module (optional).
     */
    public function getNavItem(): ?NavbarItem
    {
        return null;
    }

    /**
     * Create required permissions.
     */
    protected function createPermissions(): void
    {
        $permissions = [
            [
                'name' => 'admin.reforger',
                'desc' => 'Access to Arma Reforger Server Manager',
            ],
            [
                'name' => 'admin.reforger.servers',
                'desc' => 'Manage Arma Reforger servers',
            ],
            [
                'name' => 'admin.reforger.mods',
                'desc' => 'Manage Arma Reforger mods',
            ],
            [
                'name' => 'admin.reforger.settings',
                'desc' => 'Manage Arma Reforger settings',
            ],
        ];

        foreach ($permissions as $permData) {
            $existing = Permission::findOne(['name' => $permData['name']]);

            if (!$existing) {
                $permission = new Permission();
                $permission->name = $permData['name'];
                $permission->desc = $permData['desc'];
                $permission->save();
            }
        }
    }

    /**
     * Create default configuration file.
     */
    protected function createDefaultConfig(): void
    {
        $configPath = config_path('arma-reforger.php');

        if (file_exists($configPath)) {
            return;
        }

        $defaultConfig = [
            'steamcmd_path' => '/usr/games/steamcmd',
            'install_path' => storage_path('reforger-servers'),
            'auto_update' => false,
            'auto_restart' => true,
            'backup_enabled' => false,
            'backup_path' => storage_path('reforger-backups'),
            'log_retention_days' => 30,
        ];

        $content = "<?php\n\n/**\n * Arma Reforger Server Manager Configuration\n */\n\nreturn " . var_export($defaultConfig, true) . ";\n";

        file_put_contents($configPath, $content, LOCK_EX);
    }
}
