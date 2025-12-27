<?php

namespace Flute\Modules\ArmaReforgerServerManager\Admin\Screens;

use Exception;
use Flute\Admin\Platform\Actions\Button;
use Flute\Admin\Platform\Actions\DropDown;
use Flute\Admin\Platform\Actions\DropDownItem;
use Flute\Admin\Platform\Components\Cells\DateTime;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\TD;
use Flute\Admin\Platform\Layouts\LayoutFactory;
use Flute\Admin\Platform\Repository;
use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Support\Color;
use Flute\Modules\ArmaReforgerServerManager\Database\Entities\ReforgerMod;
use Flute\Modules\ArmaReforgerServerManager\Services\ReforgerModService;

class ModListScreen extends Screen
{
    public ?string $name = null;
    public ?string $description = null;
    public ?string $permission = 'admin.reforger';

    public $mods;

    public function mount(): void
    {
        $this->name = __('arma-reforger.admin.mods.title');
        $this->description = __('arma-reforger.admin.mods.description');

        breadcrumb()
            ->add(__('def.admin_panel'), url('/admin'))
            ->add(__('arma-reforger.admin.mods.title'));

        $this->mods = rep(ReforgerMod::class)->select();
    }

    public function layout(): array
    {
        return [
            LayoutFactory::table('mods', [
                TD::selection('id'),

                TD::make('name')
                    ->title(__('arma-reforger.admin.mods.fields.name'))
                    ->render(fn(ReforgerMod $mod) => view('arma-reforger::cells.mod-name', compact('mod')))
                    ->minWidth('200px')
                    ->cantHide(),

                TD::make('workshopId')
                    ->title(__('arma-reforger.admin.mods.fields.workshop_id'))
                    ->width('150px')
                    ->render(fn(ReforgerMod $mod) => sprintf(
                        '<a href="%s" target="_blank" class="text-primary">%s</a>',
                        $mod->getWorkshopUrl(),
                        $mod->workshopId
                    )),

                TD::make('author')
                    ->title(__('arma-reforger.admin.mods.fields.author'))
                    ->width('150px'),

                TD::make('version')
                    ->title(__('arma-reforger.admin.mods.fields.version'))
                    ->width('100px'),

                TD::make('fileSize')
                    ->title(__('arma-reforger.admin.mods.fields.size'))
                    ->render(fn(ReforgerMod $mod) => $mod->getFormattedFileSize())
                    ->width('100px'),

                TD::make('isDownloaded')
                    ->title(__('arma-reforger.admin.mods.fields.downloaded'))
                    ->render(fn(ReforgerMod $mod) => view('arma-reforger::cells.enabled', ['server' => (object)['enabled' => $mod->isDownloaded]]))
                    ->width('100px')
                    ->alignCenter(),

                TD::make('enabled')
                    ->title(__('arma-reforger.admin.mods.fields.enabled'))
                    ->render(fn(ReforgerMod $mod) => view('arma-reforger::cells.enabled', ['server' => (object)['enabled' => $mod->enabled]]))
                    ->width('100px')
                    ->alignCenter(),

                TD::make('lastUpdated')
                    ->title(__('arma-reforger.admin.mods.fields.last_updated'))
                    ->asComponent(DateTime::class)
                    ->width('150px')
                    ->sort(),

                TD::make('actions')
                    ->class('actions-col')
                    ->title(__('arma-reforger.admin.common.actions'))
                    ->width('150px')
                    ->alignCenter()
                    ->render(fn(ReforgerMod $mod) => $this->getActionsDropdown($mod)),
            ])
                ->searchable(['name', 'workshopId', 'author'])
                ->commands([
                    Button::make(__('arma-reforger.admin.mods.add'))
                        ->icon('ph.bold.plus-bold')
                        ->modal('addModModal'),

                    Button::make(__('arma-reforger.admin.mods.search_workshop'))
                        ->icon('ph.bold.magnifying-glass-bold')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->modal('searchWorkshopModal'),
                ])
                ->bulkActions([
                    Button::make(__('arma-reforger.admin.mods.refresh_selected'))
                        ->icon('ph.bold.arrows-clockwise-bold')
                        ->type(Color::OUTLINE_PRIMARY)
                        ->method('bulkRefresh'),

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
                        ->confirm(__('arma-reforger.admin.confirms.delete_mods'))
                        ->method('bulkDelete'),
                ]),
        ];
    }

    protected function getActionsDropdown(ReforgerMod $mod): string
    {
        return DropDown::make()
            ->icon('ph.regular.dots-three-outline-vertical')
            ->list([
                DropDownItem::make(__('arma-reforger.admin.mods.refresh'))
                    ->method('refreshMod', ['mod-id' => $mod->id])
                    ->icon('ph.bold.arrows-clockwise-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('arma-reforger.admin.mods.view_workshop'))
                    ->redirect($mod->getWorkshopUrl())
                    ->icon('ph.bold.arrow-square-out-bold')
                    ->type(Color::OUTLINE_PRIMARY)
                    ->size('small')
                    ->fullWidth(),

                DropDownItem::make(__('arma-reforger.admin.common.delete'))
                    ->confirm(__('arma-reforger.admin.confirms.delete_mod'))
                    ->method('delete', ['delete-id' => $mod->id])
                    ->icon('ph.bold.trash-bold')
                    ->type(Color::OUTLINE_DANGER)
                    ->size('small')
                    ->fullWidth(),
            ]);
    }

    public function addModModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('workshop_id')
                    ->type('text')
                    ->placeholder(__('arma-reforger.admin.mods.workshop_id_placeholder'))
            )
                ->label(__('arma-reforger.admin.mods.fields.workshop_id'))
                ->small(__('arma-reforger.admin.mods.workshop_id_help'))
                ->required(),
        ])
            ->title(__('arma-reforger.admin.mods.add'))
            ->applyButton(__('arma-reforger.admin.common.add'))
            ->method('addMod');
    }

    public function searchWorkshopModal(Repository $parameters)
    {
        return LayoutFactory::modal($parameters, [
            LayoutFactory::field(
                Input::make('search_query')
                    ->type('text')
                    ->placeholder(__('arma-reforger.admin.mods.search_placeholder'))
            )
                ->label(__('arma-reforger.admin.mods.search_label')),
        ])
            ->title(__('arma-reforger.admin.mods.search_workshop'))
            ->applyButton(__('arma-reforger.admin.common.search'))
            ->method('searchWorkshop');
    }

    public function addMod(): void
    {
        $workshopId = request()->input('workshop_id');

        if (empty($workshopId)) {
            $this->flashMessage(__('arma-reforger.admin.messages.workshop_id_required'), 'error');
            return;
        }

        try {
            $mod = app(ReforgerModService::class)->addMod($workshopId);
            $this->mods = rep(ReforgerMod::class)->select();
            $this->flashMessage(__('arma-reforger.admin.messages.mod_added'), 'success');
            $this->closeModal();
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function searchWorkshop(): void
    {
        $query = request()->input('search_query');

        if (empty($query)) {
            $this->flashMessage(__('arma-reforger.admin.messages.search_query_required'), 'error');
            return;
        }

        try {
            $results = app(ReforgerModService::class)->searchWorkshop($query);

            if (empty($results)) {
                $this->flashMessage(__('arma-reforger.admin.messages.no_mods_found'), 'warning');
            } else {
                $this->flashMessage(sprintf(__('arma-reforger.admin.messages.mods_found'), count($results)), 'success');
            }

            $this->closeModal();
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }
    }

    public function refreshMod(): void
    {
        $mod = ReforgerMod::findByPK(request()->get('mod-id'));

        if ($mod) {
            try {
                app(ReforgerModService::class)->refreshModInfo($mod);
                $this->flashMessage(__('arma-reforger.admin.messages.mod_refreshed'), 'success');
            } catch (Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }
        }
    }

    public function delete(): void
    {
        $mod = ReforgerMod::findByPK(request()->get('delete-id'));

        if ($mod) {
            app(ReforgerModService::class)->deleteMod($mod);
            $this->mods = rep(ReforgerMod::class)->select();
            $this->flashMessage(__('arma-reforger.admin.messages.mod_deleted'), 'success');
        }
    }

    public function bulkRefresh(): void
    {
        $ids = request()->input('selected', []);
        $service = app(ReforgerModService::class);

        foreach ($ids as $id) {
            $mod = ReforgerMod::findByPK($id);
            if ($mod) {
                $service->refreshModInfo($mod);
            }
        }
        $this->mods = rep(ReforgerMod::class)->select();
        $this->flashMessage(__('arma-reforger.admin.messages.mods_refreshed'), 'success');
    }

    public function bulkEnable(): void
    {
        $ids = request()->input('selected', []);
        foreach ($ids as $id) {
            $mod = ReforgerMod::findByPK($id);
            if ($mod) {
                $mod->enabled = true;
                $mod->save();
            }
        }
        $this->mods = rep(ReforgerMod::class)->select();
        $this->flashMessage(__('arma-reforger.admin.messages.mods_enabled'), 'success');
    }

    public function bulkDisable(): void
    {
        $ids = request()->input('selected', []);
        foreach ($ids as $id) {
            $mod = ReforgerMod::findByPK($id);
            if ($mod) {
                $mod->enabled = false;
                $mod->save();
            }
        }
        $this->mods = rep(ReforgerMod::class)->select();
        $this->flashMessage(__('arma-reforger.admin.messages.mods_disabled'), 'warning');
    }

    public function bulkDelete(): void
    {
        $ids = request()->input('selected', []);
        $service = app(ReforgerModService::class);

        foreach ($ids as $id) {
            $mod = ReforgerMod::findByPK($id);
            if ($mod) {
                $service->deleteMod($mod);
            }
        }
        $this->mods = rep(ReforgerMod::class)->select();
        $this->flashMessage(__('arma-reforger.admin.messages.mods_deleted'), 'success');
    }
}
