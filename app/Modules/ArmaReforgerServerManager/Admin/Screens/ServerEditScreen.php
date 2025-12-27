<?php

namespace Flute\Modules\ArmaReforgerServerManager\Admin\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Select;
use Flute\Admin\Platform\Fields\Tab;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Fields\TextArea;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerMod;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServerMod;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerModService;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerServerService;

class ServerEditScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.reforger';

    public ?ReforgerServer $server = null;
    public ?int $serverId = null;
    public bool $isEditMode = false;
    public $serverMods = [];

    protected ReforgerServerService $serverService;

    public function mount(): void
    {
        $this->serverService = app(ReforgerServerService::class);
        $this->serverId = (int) request()->input('id');

        if ($this->serverId) {
            $this->initServer();
            $this->isEditMode = true;
        } else {
            $this->name = __('arma-reforger.admin.servers.create');
            $this->description = __('arma-reforger.admin.servers.create_description');
        }

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('arma-reforger.admin.servers.title'), url('/admin/reforger/servers'))
            ->add($this->serverId ? $this->server->name : __('arma-reforger.admin.servers.create'));
    }

    protected function initServer(): void
    {
        $this->server = ReforgerServer::findByPK($this->serverId);

        if (!$this->server) {
            $this->flashMessage(__('arma-reforger.admin.messages.server_not_found'), 'error');
            $this->redirectTo('/admin/reforger/servers', 300);
            return;
        }

        $this->serverMods = app(ReforgerModService::class)->getServerMods($this->server);
        $this->name = __('arma-reforger.admin.servers.edit') . ': ' . $this->server->name;
    }

    public function commandBar(): array
    {
        $buttons = [
            Button::make(__('arma-reforger.admin.common.cancel'))
                ->type(Color::OUTLINE_PRIMARY)
                ->redirect('/admin/reforger/servers'),
        ];

        if (user()->can('admin.reforger')) {
            $buttons[] = Button::make(__('arma-reforger.admin.common.save'))
                ->type(Color::PRIMARY)
                ->icon('ph.bold.floppy-disk-bold')
                ->method('saveServer');
        }

        return $buttons;
    }

    public function layout(): array
    {
        $tabs = [];

        $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.general'))
            ->icon('ph.bold.gear-bold')
            ->layouts([$this->generalTabLayout()])
            ->active(true);

        $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.network'))
            ->icon('ph.bold.wifi-high-bold')
            ->layouts([$this->networkTabLayout()]);

        $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.game'))
            ->icon('ph.bold.game-controller-bold')
            ->layouts([$this->gameTabLayout()]);

        if ($this->serverId) {
            $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.mods'))
                ->icon('ph.bold.puzzle-piece-bold')
                ->layouts([$this->modsTabLayout()])
                ->badge(count($this->serverMods));

            $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.installation'))
                ->icon('ph.bold.download-bold')
                ->layouts([$this->installationTabLayout()]);

            $tabs[] = Tab::make(__('arma-reforger.admin.servers.tabs.advanced'))
                ->icon('ph.bold.code-bold')
                ->layouts([$this->advancedTabLayout()]);
        }

        return [
            LayoutFactory::tabs($tabs)
                ->slug('server-edit')
                ->pills(),
        ];
    }

    protected function generalTabLayout()
    {
        return $this->serverId
            ? LayoutFactory::split([
                $this->getGeneralFields(),
                $this->getStatusSidebar(),
            ])->ratio('70/30')
            : $this->getGeneralFields();
    }

    protected function getGeneralFields()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('name')
                    ->type('text')
                    ->value($this->server?->name ?? '')
                    ->placeholder(__('arma-reforger.admin.servers.fields.name_placeholder'))
            )
                ->label(__('arma-reforger.admin.servers.fields.name'))
                ->required(),

            LayoutFactory::field(
                Input::make('serverName')
                    ->type('text')
                    ->value($this->server?->serverName ?? 'Arma Reforger Server')
                    ->placeholder(__('arma-reforger.admin.servers.fields.server_name_placeholder'))
            )
                ->label(__('arma-reforger.admin.servers.fields.server_name'))
                ->small(__('arma-reforger.admin.servers.fields.server_name_help'))
                ->required(),

            LayoutFactory::field(
                Select::make('scenarioId')
                    ->options($this->serverService->getAvailableScenarios())
                    ->value($this->server?->scenarioId)
                    ->placeholder(__('arma-reforger.admin.servers.fields.scenario_placeholder'))
                    ->allowEmpty()
            )
                ->label(__('arma-reforger.admin.servers.fields.scenario'))
                ->small(__('arma-reforger.admin.servers.fields.scenario_help')),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('adminPassword')
                        ->type('password')
                        ->value($this->server?->adminPassword ?? '')
                        ->placeholder('********')
                )
                    ->label(__('arma-reforger.admin.servers.fields.admin_password'))
                    ->small(__('arma-reforger.admin.servers.fields.admin_password_help')),

                LayoutFactory::field(
                    Input::make('serverPassword')
                        ->type('password')
                        ->value($this->server?->serverPassword ?? '')
                        ->placeholder('********')
                )
                    ->label(__('arma-reforger.admin.servers.fields.server_password'))
                    ->small(__('arma-reforger.admin.servers.fields.server_password_help')),
            ]),

            LayoutFactory::field(
                Toggle::make('enabled')
                    ->checked($this->server?->enabled ?? true)
            )
                ->label(__('arma-reforger.admin.servers.fields.enabled'))
                ->popover(__('arma-reforger.admin.servers.fields.enabled_help')),
        ])->title(__('arma-reforger.admin.servers.sections.general'));
    }

    protected function getStatusSidebar()
    {
        return LayoutFactory::rows([
            LayoutFactory::view('arma-reforger::partials.server-status', [
                'server' => $this->server,
                'status' => $this->serverService->getServerStatus($this->server),
            ]),

            Button::make(__('arma-reforger.admin.common.delete'))
                ->type(Color::OUTLINE_DANGER)
                ->icon('ph.bold.trash-bold')
                ->method('deleteServer')
                ->confirm(__('arma-reforger.admin.confirms.delete_server'))
                ->fullWidth()
                ->setVisible($this->serverId),
        ])
            ->title(__('arma-reforger.admin.servers.sections.status'))
            ->setVisible($this->serverId);
    }

    protected function networkTabLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('bindAddress')
                        ->type('text')
                        ->value($this->server?->bindAddress ?? '0.0.0.0')
                        ->placeholder('0.0.0.0')
                )
                    ->label(__('arma-reforger.admin.servers.fields.bind_address'))
                    ->small(__('arma-reforger.admin.servers.fields.bind_address_help')),

                LayoutFactory::field(
                    Input::make('bindPort')
                        ->type('number')
                        ->value($this->server?->bindPort ?? 2001)
                        ->placeholder('2001')
                )
                    ->label(__('arma-reforger.admin.servers.fields.bind_port'))
                    ->required(),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('publicAddress')
                        ->type('text')
                        ->value($this->server?->publicAddress ?? '')
                        ->placeholder(__('arma-reforger.admin.servers.fields.public_address_placeholder'))
                )
                    ->label(__('arma-reforger.admin.servers.fields.public_address'))
                    ->small(__('arma-reforger.admin.servers.fields.public_address_help')),

                LayoutFactory::field(
                    Input::make('publicPort')
                        ->type('number')
                        ->value($this->server?->publicPort ?? 2001)
                        ->placeholder('2001')
                )
                    ->label(__('arma-reforger.admin.servers.fields.public_port')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Input::make('a2sPort')
                        ->type('number')
                        ->value($this->server?->a2sPort ?? 0)
                        ->placeholder('17777')
                )
                    ->label(__('arma-reforger.admin.servers.fields.a2s_port'))
                    ->small(__('arma-reforger.admin.servers.fields.a2s_port_help')),

                LayoutFactory::field(
                    Input::make('steamQueryPort')
                        ->type('number')
                        ->value($this->server?->steamQueryPort ?? 0)
                        ->placeholder('17778')
                )
                    ->label(__('arma-reforger.admin.servers.fields.steam_query_port'))
                    ->small(__('arma-reforger.admin.servers.fields.steam_query_port_help')),
            ]),
        ])->title(__('arma-reforger.admin.servers.sections.network'));
    }

    protected function gameTabLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                Input::make('maxPlayers')
                    ->type('number')
                    ->value($this->server?->maxPlayers ?? 64)
                    ->placeholder('64')
            )
                ->label(__('arma-reforger.admin.servers.fields.max_players'))
                ->required(),

            LayoutFactory::split([
                LayoutFactory::field(
                    Toggle::make('visible')
                        ->checked($this->server?->visible ?? true)
                )
                    ->label(__('arma-reforger.admin.servers.fields.visible'))
                    ->popover(__('arma-reforger.admin.servers.fields.visible_help')),

                LayoutFactory::field(
                    Toggle::make('crossPlatform')
                        ->checked($this->server?->crossPlatform ?? false)
                )
                    ->label(__('arma-reforger.admin.servers.fields.cross_platform'))
                    ->popover(__('arma-reforger.admin.servers.fields.cross_platform_help')),
            ]),

            LayoutFactory::split([
                LayoutFactory::field(
                    Toggle::make('battleEye')
                        ->checked($this->server?->battleEye ?? true)
                )
                    ->label(__('arma-reforger.admin.servers.fields.battleye'))
                    ->popover(__('arma-reforger.admin.servers.fields.battleye_help')),

                LayoutFactory::field(
                    Toggle::make('thirdPersonView')
                        ->checked($this->server?->thirdPersonView ?? false)
                )
                    ->label(__('arma-reforger.admin.servers.fields.third_person'))
                    ->popover(__('arma-reforger.admin.servers.fields.third_person_help')),
            ]),

            LayoutFactory::field(
                Toggle::make('vonDisabled')
                    ->checked($this->server?->vonDisabled ?? false)
            )
                ->label(__('arma-reforger.admin.servers.fields.von_disabled'))
                ->popover(__('arma-reforger.admin.servers.fields.von_disabled_help')),
        ])->title(__('arma-reforger.admin.servers.sections.game'));
    }

    protected function modsTabLayout()
    {
        return LayoutFactory::table('serverMods', [
            TD::make('loadOrder')
                ->title(__('arma-reforger.admin.mods.fields.load_order'))
                ->width('80px')
                ->alignCenter(),

            TD::make('mod.name')
                ->title(__('arma-reforger.admin.mods.fields.name'))
                ->render(fn(ReforgerServerMod $sm) => $sm->mod->name)
                ->minWidth('200px'),

            TD::make('mod.workshopId')
                ->title(__('arma-reforger.admin.mods.fields.workshop_id'))
                ->render(fn(ReforgerServerMod $sm) => $sm->mod->workshopId)
                ->width('150px'),

            TD::make('enabled')
                ->title(__('arma-reforger.admin.mods.fields.enabled'))
                ->render(fn(ReforgerServerMod $sm) => view('arma-reforger::cells.enabled', ['server' => (object)['enabled' => $sm->enabled]]))
                ->width('100px')
                ->alignCenter(),

            TD::make('actions')
                ->title(__('arma-reforger.admin.common.actions'))
                ->width('120px')
                ->render(fn(ReforgerServerMod $sm) => DropDown::make()
                    ->icon('ph.regular.dots-three-outline-vertical')
                    ->list([
                        DropDownItem::make($sm->enabled ? __('arma-reforger.admin.mods.disable') : __('arma-reforger.admin.mods.enable'))
                            ->method('toggleMod', ['mod-id' => $sm->mod->id])
                            ->icon($sm->enabled ? 'ph.bold.power-bold' : 'ph.bold.play-bold')
                            ->type($sm->enabled ? Color::OUTLINE_WARNING : Color::OUTLINE_SUCCESS)
                            ->size('small')
                            ->fullWidth(),

                        DropDownItem::make(__('arma-reforger.admin.common.remove'))
                            ->method('removeMod', ['mod-id' => $sm->mod->id])
                            ->icon('ph.bold.x-bold')
                            ->type(Color::OUTLINE_DANGER)
                            ->size('small')
                            ->fullWidth(),
                    ])
                ),
        ])
            ->searchable(['mod.name', 'mod.workshopId'])
            ->commands([
                Button::make(__('arma-reforger.admin.mods.add_to_server'))
                    ->icon('ph.bold.plus-bold')
                    ->modal('addModModal')
                    ->type(Color::OUTLINE_PRIMARY),
            ]);
    }

    protected function installationTabLayout()
    {
        $status = $this->serverService->getServerStatus($this->server);

        return LayoutFactory::block([
            LayoutFactory::view('arma-reforger::partials.installation-status', [
                'server' => $this->server,
                'status' => $status,
            ]),

            LayoutFactory::field(
                Input::make('installPath')
                    ->type('text')
                    ->value($this->server?->installPath ?? '')
                    ->placeholder('/opt/reforger-servers/server_1')
            )
                ->label(__('arma-reforger.admin.servers.fields.install_path'))
                ->small(__('arma-reforger.admin.servers.fields.install_path_help')),

            LayoutFactory::rows([
                Button::make(__('arma-reforger.admin.servers.install_files'))
                    ->type($status['installed'] ? Color::OUTLINE_PRIMARY : Color::PRIMARY)
                    ->icon('ph.bold.download-bold')
                    ->method('installServer')
                    ->confirm(__('arma-reforger.admin.confirms.install_server')),

                Button::make(__('arma-reforger.admin.servers.validate_files'))
                    ->type(Color::OUTLINE_PRIMARY)
                    ->icon('ph.bold.check-circle-bold')
                    ->method('validateServer')
                    ->setVisible($status['installed']),
            ]),
        ])->title(__('arma-reforger.admin.servers.sections.installation'));
    }

    protected function advancedTabLayout()
    {
        return LayoutFactory::block([
            LayoutFactory::field(
                TextArea::make('configJson')
                    ->value($this->server?->configJson ?? '')
                    ->rows(15)
                    ->placeholder('{"game": {"additionalSettings": {}}}')
            )
                ->label(__('arma-reforger.admin.servers.fields.config_json'))
                ->small(__('arma-reforger.admin.servers.fields.config_json_help')),
        ])->title(__('arma-reforger.admin.servers.sections.advanced'));
    }

    public function addModModal(Repository $parameters)
    {
        $availableMods = ReforgerMod::findAll();

        $modOptions = [];
        foreach ($availableMods as $mod) {
            $modOptions[$mod->id] = $mod->name . ' (' . $mod->workshopId . ')';
        }

        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Select::make('mod_id')
                    ->options($modOptions)
                    ->allowEmpty()
                    ->placeholder(__('arma-reforger.admin.mods.select_mod'))
            )
                ->label(__('arma-reforger.admin.mods.fields.mod'))
                ->required(),

            LayoutFactory::field(
                Input::make('load_order')
                    ->type('number')
                    ->value(count($this->serverMods))
                    ->placeholder('0')
            )
                ->label(__('arma-reforger.admin.mods.fields.load_order')),
        ])
            ->title(__('arma-reforger.admin.mods.add_to_server'))
            ->applyButton(__('arma-reforger.admin.common.add'))
            ->method('addMod');
    }

    public function saveServer(): void
    {
        $data = request()->input();

        $validation = $this->validate([
            'name' => ['required', 'string', 'max-str-len:255'],
            'serverName' => ['required', 'string', 'max-str-len:255'],
            'bindPort' => ['required', 'integer', 'min:1', 'max:65535'],
            'maxPlayers' => ['required', 'integer', 'min:1', 'max:256'],
        ], $data);

        if (!$validation) {
            return;
        }

        try {
            if ($this->server) {
                $this->serverService->updateServer($this->server, $data);
                $this->flashMessage(__('arma-reforger.admin.messages.server_updated'), 'success');
            } else {
                $server = $this->serverService->createServer($data);
                $this->flashMessage(__('arma-reforger.admin.messages.server_created'), 'success');
                $this->redirect('/admin/reforger/servers/' . $server->id . '/edit');
            }
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function deleteServer(): void
    {
        if ($this->server) {
            $this->serverService->deleteServer($this->server);
            $this->flashMessage(__('arma-reforger.admin.messages.server_deleted'), 'success');
            $this->redirectTo('/admin/reforger/servers');
        }
    }

    public function installServer(): void
    {
        if ($this->server) {
            try {
                $result = $this->serverService->installServerFiles($this->server);
                if ($result['success']) {
                    $this->flashMessage(__('arma-reforger.admin.messages.installation_started'), 'success');
                } else {
                    $this->flashMessage(__('arma-reforger.admin.messages.installation_failed'), 'error');
                }
            } catch (Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        }
    }

    public function validateServer(): void
    {
        if ($this->server) {
            try {
                $result = $this->serverService->validateServerFiles($this->server);
                if ($result['success']) {
                    $this->flashMessage(__('arma-reforger.admin.messages.validation_complete'), 'success');
                } else {
                    $this->flashMessage(__('arma-reforger.admin.messages.validation_failed'), 'error');
                }
            } catch (Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        }
    }

    public function addMod(): void
    {
        $modId = request()->input('mod_id');
        $loadOrder = (int) request()->input('load_order', 0);

        $mod = ReforgerMod::findByPK($modId);
        if ($mod && $this->server) {
            app(ReforgerModService::class)->addModToServer($this->server, $mod, $loadOrder);
            $this->serverMods = app(ReforgerModService::class)->getServerMods($this->server);
            $this->flashMessage(__('arma-reforger.admin.messages.mod_added'), 'success');
            $this->closeModal();
        }
    }

    public function toggleMod(): void
    {
        $modId = request()->input('mod-id');
        $mod = ReforgerMod::findByPK($modId);

        if ($mod && $this->server) {
            app(ReforgerModService::class)->toggleServerMod($this->server, $mod);
            $this->serverMods = app(ReforgerModService::class)->getServerMods($this->server);
            $this->flashMessage(__('arma-reforger.admin.messages.mod_toggled'), 'success');
        }
    }

    public function removeMod(): void
    {
        $modId = request()->input('mod-id');
        $mod = ReforgerMod::findByPK($modId);

        if ($mod && $this->server) {
            app(ReforgerModService::class)->removeModFromServer($this->server, $mod);
            $this->serverMods = app(ReforgerModService::class)->getServerMods($this->server);
            $this->flashMessage(__('arma-reforger.admin.messages.mod_removed'), 'success');
        }
    }
}
