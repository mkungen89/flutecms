<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Table;
use Flute\Admin\Platform\Fields\TD;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameSession;

class SessionsScreen extends Screen
{
    public ?string $name = 'Game Sessions';
    public ?string $description = 'View all game sessions';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        $sessions = GameSession::query()
            ->orderBy('started_at', 'DESC')
            ->limit(100)
            ->fetchAll();

        return [
            'sessions' => $sessions,
        ];
    }

    public function layout(): array
    {
        return [
            Table::make('sessions', [
                TD::make('id', 'ID')
                    ->sort(),
                TD::make('server_name', 'Server'),
                TD::make('', 'Map')
                    ->render(fn(GameSession $s) => $s->map?->name ?? 'Unknown'),
                TD::make('game_mode', 'Mode'),
                TD::make('', 'Score')
                    ->render(fn(GameSession $s) => "{$s->us_score} - {$s->ussr_score}"),
                TD::make('winner_faction', 'Winner'),
                TD::make('total_players', 'Players'),
                TD::make('total_kills', 'Kills'),
                TD::make('', 'Duration')
                    ->render(fn(GameSession $s) => $s->getFormattedDuration()),
                TD::make('status', 'Status'),
                TD::make('started_at', 'Started')
                    ->render(fn(GameSession $s) => $s->started_at->format('Y-m-d H:i')),
            ]),
        ];
    }
}
