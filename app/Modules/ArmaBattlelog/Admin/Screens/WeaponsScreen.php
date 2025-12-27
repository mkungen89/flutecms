<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Fields\TD;
use Flute\Modules\ArmaBattlelog\Database\Entities\Weapon;

class WeaponsScreen extends Screen
{
    public ?string $name = 'Weapons';
    public ?string $description = 'Manage weapon definitions';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        $weapons = Weapon::query()
            ->orderBy('category', 'ASC')
            ->orderBy('name', 'ASC')
            ->fetchAll();

        return [
            'weapons' => $weapons,
        ];
    }

    public function layout(): array
    {
        return [
            Table::make('weapons', [
                TD::make('id', 'ID'),
                TD::make('internal_id', 'Internal ID'),
                TD::make('name', 'Name'),
                TD::make('', 'Category')
                    ->render(fn(Weapon $w) => $w->getCategoryName()),
                TD::make('', 'Faction')
                    ->render(fn(Weapon $w) => $w->getFactionName()),
                TD::make('magazine_size', 'Magazine'),
                TD::make('fire_rate', 'Fire Rate'),
                TD::make('is_active', 'Active')
                    ->render(fn(Weapon $w) => $w->is_active ? '✅' : '❌'),
            ]),
        ];
    }
}
