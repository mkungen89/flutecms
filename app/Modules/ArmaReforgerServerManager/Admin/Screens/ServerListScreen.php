<?php

namespace Flute\Modules\ArmaReforgerServerManager\Admin\Screens;

use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\DateTime;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerServer;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerServerService;

class ServerListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.reforger';

    public $servers;

    public function mount(): void
    {
        $this->name = __('arma-reforger.admin.servers.title');
        $this->description = __('arma-reforger.admin.servers.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('arma-reforger.admin.servers.title'));

        $this->servers = rep(ReforgerServer::class)->select();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('servers', [
                TD::selection('id'),

                TD::make('name')
                    ->title(__('arma-reforger.admin.servers.fields.name'))
                    ->render(fn(ReforgerServer $server) => view('arma-reforger::cells.server-name', compact('server')))
                    ->minWidth('200px')
                    ->cantHide(),

                TD::make('status')
                    ->title(__('arma-reforger.admin.servers.fields.status'))
                    ->render(fn(ReforgerServer $server) => view('arma-reforger::cells.status', compact('server')))
                    ->width('120px'),

                TD::make('connection')
                    ->title(__('arma-reforger.admin.servers.fields.connection'))
                    ->render(fn(ReforgerServer $server) => sprintf(
                        '%s:%d',
                        $server->publicAddress ?: $server->bindAddress,
                        $server->publicPort ?: $server->bindPort
                    ))
                    ->width('180px'),

                TD::make('maxPlayers')
                    ->title(__('arma-reforger.admin.servers.fields.max_players'))
                    ->width('100px')
                    ->alignCenter(),

                TD::make('enabled')
                    ->title(__('arma-reforger.admin.servers.fields.enabled'))
                    ->render(fn(ReforgerServer $server) => view('arma-reforger::cells.enabled', compact('server')))
                    ->width('100px')
                    ->alignCenter(),

                TD::make('createdAt')
                    ->title(__('arma-reforger.admin.servers.fields.created_at'))
                    ->asComponent(DateTime::class)
                    ->width('150px')
                    ->sort(),

                TD::make('actions')
                    ->class('actions-col')
                    ->title(__('arma-reforger.admin.common.actions'))
                    ->width('180px')
                    ->alignCenter()
                    ->render(fn(ReforgerServer $server) => $this->getActionsDropdown($server)),
            ])
                ->searchable(['name', 'serverName', 'bindAddress'])
                ->commands([
                    Button::make(__('arma-reforger.admin.servers.add'))
                        ->icon('ph.bold.plus-bold')
                        ->redirect(url('/admin/reforger/servers/add')),
                ])
                ->bulkActions([
                    Button::make(__('arma-reforger.admin.common.enable_selected'))
                        ->icon('ph.bold.play-bold')
                        ->type(Color::OUTLINE_SUCCESS)
                        ->method('bulkEnable'),

                    Button::make(__('arma-reforger.admin.common.disable_selected'))
                        ->icon('ph.bold.power-bold')
                        ->type(Color::OUTLINE_WARNING)
                        ->method('bulkDisable'),

                    Button::make(__('arma-reforger.admin.common.delete_selected'))
                        ->icon('ph.bold.trash-bold')
                        ->type(Color::OUTLINE_DANGER)
                        ->confirm(__('arma-reforger.admin.confirms.delete_selected'))
                        ->method('bulkDelete'),
                ]),
        ];
    }

    protected function getActionsDropdown(ReforgerServer $server): string
    {
        $items = [];

        // Start/Stop buttons
        if ($server->status === 'running') {
            $items[] = DropDownItem::make(__('arma-reforger.admin.servers.stop'))
                ->method('stopServer', ['server-id' => $server->id])
                ->icon('ph.bold.stop-bold')
                ->type(Color::OUTLINE_WARNING)
                ->size('small')
                ->fullWidth();
        } else {
            $items[] = DropDownItem::make(__('arma-reforger.admin.servers.start'))
                ->method('startServer', ['server-id' => $server->id])
                ->icon('ph.bold.play-bold')
                ->type(Color::OUTLINE_SUCCESS)
                ->size('small')
                ->fullWidth();
        }

        $items[] = DropDownItem::make(__('arma-reforger.admin.common.edit'))
            ->redirect(url('/admin/reforger/servers/' . $server->id . '/edit'))
            ->icon('ph.bold.pencil-bold')
            ->type(Color::OUTLINE_PRIMARY)
            ->size('small')
            ->fullWidth();

        $items[] = DropDownItem::make(__('arma-reforger.admin.common.delete'))
            ->confirm(__('arma-reforger.admin.confirms.delete_server'))
            ->method('delete', ['delete-id' => $server->id])
            ->icon('ph.bold.trash-bold')
            ->type(Color::OUTLINE_DANGER)
            ->size('small')
            ->fullWidth();

        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list($items);
    }

    public function startServer(): void
    {
        $server = ReforgerServer::findByPK(request()->get('server-id'));

        if ($server) {
            try {
                app(ReforgerServerService::class)->startServer($server);
                $this->flashMessage(__('arma-reforger.admin.messages.server_started'), 'success');
            } catch (\Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        }
    }

    public function stopServer(): void
    {
        $server = ReforgerServer::findByPK(request()->get('server-id'));

        if ($server) {
            try {
                app(ReforgerServerService::class)->stopServer($server);
                $this->flashMessage(__('arma-reforger.admin.messages.server_stopped'), 'success');
            } catch (\Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        }
    }

    public function delete(): void
    {
        $server = ReforgerServer::findByPK(request()->get('delete-id'));

        if ($server) {
            app(ReforgerServerService::class)->deleteServer($server);
            $this->flashMessage(__('arma-reforger.admin.messages.server_deleted'), 'success');
        }
    }

    public function bulkEnable(): void
    {
        $ids = request()->input('selected', []);
        foreach ($ids as $id) {
            $server = ReforgerServer::findByPK($id);
            if ($server) {
                $server->enabled = true;
                $server->save();
            }
        }
        $this->flashMessage(__('arma-reforger.admin.messages.servers_enabled'), 'success');
    }

    public function bulkDisable(): void
    {
        $ids = request()->input('selected', []);
        foreach ($ids as $id) {
            $server = ReforgerServer::findByPK($id);
            if ($server) {
                $server->enabled = false;
                $server->save();
            }
        }
        $this->flashMessage(__('arma-reforger.admin.messages.servers_disabled'), 'warning');
    }

    public function bulkDelete(): void
    {
        $ids = request()->input('selected', []);
        $service = app(ReforgerServerService::class);

        foreach ($ids as $id) {
            $server = ReforgerServer::findByPK($id);
            if ($server) {
                $service->deleteServer($server);
            }
        }
        $this->flashMessage(__('arma-reforger.admin.messages.servers_deleted'), 'success');
    }
}
