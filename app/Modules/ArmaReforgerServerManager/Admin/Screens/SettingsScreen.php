<?php

namespace Flute\Modules\ArmaReforgerServerManager\Admin\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\ArmaReforgerServerManager\Services\SteamCMDService;

class SettingsScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.reforger';

    public array $settings = [];
    public bool $steamcmdAvailable = false;
    public ?string $steamcmdVersion = null;

    public function mount(): void
    {
        $this->name = __('arma-reforger.admin.settings.title');
        $this->description = __('arma-reforger.admin.settings.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('arma-reforger.admin.settings.title'));

        $this->loadSettings();
        $this->checkSteamCMD();
    }

    protected function loadSettings(): void
    {
        $this->settings = [
            'steamcmd_path' => config('arma-reforger.steamcmd_path', '/usr/games/steamcmd'),
            'install_path' => config('arma-reforger.install_path', storage_path('reforger-servers')),
            'auto_update' => config('arma-reforger.auto_update', false),
            'auto_restart' => config('arma-reforger.auto_restart', true),
            'backup_enabled' => config('arma-reforger.backup_enabled', false),
            'backup_path' => config('arma-reforger.backup_path', storage_path('reforger-backups')),
            'log_retention_days' => config('arma-reforger.log_retention_days', 30),
        ];
    }

    protected function checkSteamCMD(): void
    {
        $steamcmd = app(SteamCMDService::class);
        $this->steamcmdAvailable = $steamcmd->isAvailable();
        $this->steamcmdVersion = $steamcmd->getVersion();
    }

    public function commandBar(): array
    {
        return [
            Button::make(__('arma-reforger.admin.common.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveSettings'),
        ];
    }

    public function layout(): array
    {
        return [
            LayoutFactory::split([
                $this->getMainSettingsLayout(),
                $this->getSteamCMDStatusLayout(),
            ])->ratio('70/30'),

            $this->getBackupSettingsLayout(),
            $this->getInstallInstructionsLayout(),
        ];
    }

    protected function getMainSettingsLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('steamcmd_path')
                    ->type('text')
                    ->value($this->settings['steamcmd_path'])
                    ->placeholder('/usr/games/steamcmd')
            )
                ->label(__('arma-reforger.admin.settings.steamcmd_path'))
                ->small(__('arma-reforger.admin.settings.steamcmd_path_help'))
                ->required(),

            LayoutFactory::field(
                Input::make('install_path')
                    ->type('text')
                    ->value($this->settings['install_path'])
                    ->placeholder('/opt/reforger-servers')
            )
                ->label(__('arma-reforger.admin.settings.install_path'))
                ->small(__('arma-reforger.admin.settings.install_path_help'))
                ->required(),

            LayoutFactory::split([
                LayoutFactory::field(
                    Toggle::make('auto_update')
                        ->checked($this->settings['auto_update'])
                )
                    ->label(__('arma-reforger.admin.settings.auto_update'))
                    ->popover(__('arma-reforger.admin.settings.auto_update_help')),

                LayoutFactory::field(
                    Toggle::make('auto_restart')
                        ->checked($this->settings['auto_restart'])
                )
                    ->label(__('arma-reforger.admin.settings.auto_restart'))
                    ->popover(__('arma-reforger.admin.settings.auto_restart_help')),
            ]),

            LayoutFactory::field(
                Input::make('log_retention_days')
                    ->type('number')
                    ->value($this->settings['log_retention_days'])
                    ->placeholder('30')
            )
                ->label(__('arma-reforger.admin.settings.log_retention'))
                ->small(__('arma-reforger.admin.settings.log_retention_help')),
        ])->title(__('arma-reforger.admin.settings.general_settings'));
    }

    protected function getSteamCMDStatusLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::view('arma-reforger::partials.steamcmd-status', [
                'available' => $this->steamcmdAvailable,
                'version' => $this->steamcmdVersion,
            ]),

            Button::make(__('arma-reforger.admin.settings.test_steamcmd'))
                ->type($this->steamcmdAvailable ? Color::OUTLINE_SUCCESS : Color::OUTLINE_DANGER)
                ->icon($this->steamcmdAvailable ? 'ph.bold.check-circle-bold' : 'ph.bold.x-circle-bold')
                ->method('testSteamCMD')
                ->fullWidth(),
        ])->title(__('arma-reforger.admin.settings.steamcmd_status'));
    }

    protected function getBackupSettingsLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Toggle::make('backup_enabled')
                    ->checked($this->settings['backup_enabled'])
            )
                ->label(__('arma-reforger.admin.settings.backup_enabled'))
                ->popover(__('arma-reforger.admin.settings.backup_enabled_help')),

            LayoutFactory::field(
                Input::make('backup_path')
                    ->type('text')
                    ->value($this->settings['backup_path'])
                    ->placeholder('/opt/reforger-backups')
            )
                ->label(__('arma-reforger.admin.settings.backup_path'))
                ->small(__('arma-reforger.admin.settings.backup_path_help')),
        ])->title(__('arma-reforger.admin.settings.backup_settings'));
    }

    protected function getInstallInstructionsLayout()
    {
        $instructions = app(SteamCMDService::class)->getInstallInstructions();

        return LayoutFactory::block([
            LayoutFactory::view('arma-reforger::partials.install-instructions', [
                'instructions' => $instructions,
            ]),
        ])->title(__('arma-reforger.admin.settings.install_instructions'));
    }

    public function saveSettings(): void
    {
        $data = request()->input();

        $validation = $this->validate([
            'steamcmd_path' => ['required', 'string'],
            'install_path' => ['required', 'string'],
            'log_retention_days' => ['required', 'integer', 'min:1'],
        ], $data);

        if (!$validation) {
            return;
        }

        // Save to config file
        $configPath = config_path('arma-reforger.php');
        $config = [
            'steamcmd_path' => $data['steamcmd_path'],
            'install_path' => $data['install_path'],
            'auto_update' => isset($data['auto_update']) && $data['auto_update'],
            'auto_restart' => isset($data['auto_restart']) && $data['auto_restart'],
            'backup_enabled' => isset($data['backup_enabled']) && $data['backup_enabled'],
            'backup_path' => $data['backup_path'] ?? storage_path('reforger-backups'),
            'log_retention_days' => (int) $data['log_retention_days'],
        ];

        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($configPath, $content)) {
            $this->flashMessage(__('arma-reforger.admin.messages.settings_saved'), 'success');
        } else {
            $this->flashMessage(__('arma-reforger.admin.messages.settings_save_failed'), 'error');
        }
    }

    public function testSteamCMD(): void
    {
        $this->checkSteamCMD();

        if ($this->steamcmdAvailable) {
            $this->flashMessage(__('arma-reforger.admin.messages.steamcmd_working'), 'success');
        } else {
            $this->flashMessage(__('arma-reforger.admin.messages.steamcmd_not_found'), 'error');
        }
    }
}
