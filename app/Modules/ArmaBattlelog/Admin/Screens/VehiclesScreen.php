<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Fields\TD;
use Flute\Modules\ArmaBattlelog\Database\Entities\Vehicle;

class VehiclesScreen extends Screen
{
    public ?string $name = 'Vehicles';
    public ?string $description = 'Manage vehicle definitions';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        $vehicles = Vehicle::query()
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->fetchAll();

        return [
            'vehicles' => $vehicles,
        ];
    }

    public function layout(): array
    {
        return [
            Table::make('vehicles', [
                TD::make('id', 'ID'),
                TD::make('internal_id', 'Internal ID'),
                TD::make('name', 'Name'),
                TD::make('', 'Category')
                    ->render(fn(Vehicle $v) => $v->getCategoryName()),
                TD::make('faction', 'Faction'),
                TD::make('seats', 'Seats'),
                TD::make('has_weapons', 'Armed')
                    ->render(fn(Vehicle $v) => $v->has_weapons ? '✅' : '❌'),
                TD::make('is_active', 'Active')
                    ->render(fn(Vehicle $v) => $v->is_active ? '✅' : '❌'),
            ]),
        ];
    }
}
