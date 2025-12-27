<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Fields\TD;
use Flute\Modules\ArmaBattlelog\Database\Entities\BattlelogPlayer;

class PlayersScreen extends Screen
{
    public ?string $name = 'Battlelog Players';
    public ?string $description = 'Manage registered players';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        $players = BattlelogPlayer::query()
            ->orderBy('total_score', 'DESC')
            ->limit(100)
            ->fetchAll();

        return [
            'players' => $players,
        ];
    }

    public function layout(): array
    {
        return [
            Table::make('players', [
                TD::make('id', 'ID')
                    ->sort(),
                TD::make('name', 'Name')
                    ->sort(),
                TD::make('rank_name', 'Rank'),
                TD::make('total_kills', 'Kills')
                    ->sort(),
                TD::make('total_deaths', 'Deaths')
                    ->sort(),
                TD::make('', 'K/D')
                    ->render(fn(BattlelogPlayer $player) => $player->getKDRatio()),
                TD::make('total_score', 'Score')
                    ->sort(),
                TD::make('games_played', 'Games')
                    ->sort(),
                TD::make('', 'Playtime')
                    ->render(fn(BattlelogPlayer $player) => $player->getFormattedPlaytime()),
                TD::make('last_seen', 'Last Seen')
                    ->render(fn(BattlelogPlayer $player) => $player->last_seen?->format('Y-m-d H:i') ?? 'Never'),
            ]),
        ];
    }
}
