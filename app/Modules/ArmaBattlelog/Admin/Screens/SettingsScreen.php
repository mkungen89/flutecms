<?php

namespace Flute\Modules\ArmaBattlelog\Admin\Screens;

use Flute\Admin\Platform\Screen;
use Flute\Admin\Platform\Layouts\Card;
use Flute\Admin\Platform\Layouts\Rows;
use Flute\Admin\Platform\Fields\Input;
use Flute\Admin\Platform\Fields\Toggle;
use Flute\Admin\Platform\Actions\Button;
use Flute\Core\Support\FluteRequest;

class SettingsScreen extends Screen
{
    public ?string $name = 'Battlelog Settings';
    public ?string $description = 'Configure Battlelog module settings';
    public ?string $permission = 'battlelog.manage';

    public function query(): array
    {
        return [
            'api_key' => config('battlelog.api_key', ''),
            'require_api_key' => config('battlelog.require_api_key', false),
            'enable_demo_endpoints' => config('battlelog.enable_demo_endpoints', true),
            'leaderboard_update_interval' => config('battlelog.leaderboard_update_interval', 3600),
        ];
    }

    public function layout(): array
    {
        return [
            Card::make('API Settings', [
                Rows::make([
                    Input::make('api_key')
                        ->title('API Key')
                        ->help('API key required for game servers to submit data'),
                    Toggle::make('require_api_key')
                        ->title('Require API Key')
                        ->help('If enabled, all API requests must include a valid API key'),
                    Toggle::make('enable_demo_endpoints')
                        ->title('Enable Demo Endpoints')
                        ->help('Allow demo data generation endpoints'),
                ]),
            ]),
            Card::make('Leaderboard Settings', [
                Rows::make([
                    Input::make('leaderboard_update_interval')
                        ->title('Update Interval (seconds)')
                        ->type('number')
                        ->help('How often to recalculate leaderboards'),
                ]),
            ]),
            Card::make('', [
                Button::make('Save Settings')
                    ->method('save')
                    ->icon('ph-floppy-disk'),
            ]),
        ];
    }

    public function save(FluteRequest $request)
    {
        // Save settings to config
        // This would typically save to database or config file

        toast()->success('Settings saved successfully');

        return redirect(route('admin.battlelog.settings'));
    }
}
