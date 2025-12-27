<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Fields\TD;
use Flute\Modules\ArmaBattlelog\Database\Entities\Achievement;

class AchievementsScreen extends Screen
{
    public ?string $name = 'Achievements';
    public ?string $description = 'Manage player achievements';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        $achievements = Achievement::query()
            ->orderBy('category', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->fetchAll();

        return [
            'achievements' => $achievements,
        ];
    }

    public function layout(): array
    {
        return [
            Table::make('achievements', [
                TD::make('id', 'ID'),
                TD::make('code', 'Code'),
                TD::make('name', 'Name'),
                TD::make('', 'Category')
                    ->render(fn(Achievement $a) => $a->getCategoryName()),
                TD::make('rarity', 'Rarity'),
                TD::make('requirement_type', 'Requirement'),
                TD::make('requirement_value', 'Value'),
                TD::make('points', 'Points'),
                TD::make('is_active', 'Active')
                    ->render(fn(Achievement $a) => $a->is_active ? '✅' : '❌'),
            ]),
        ];
    }
}
