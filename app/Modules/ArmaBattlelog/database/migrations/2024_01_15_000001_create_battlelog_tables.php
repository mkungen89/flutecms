<?php

use Cycle\Migrations\Migration;

class CreateBattlelogTables extends Migration
{
    public function up(): void
    {
        // Maps table
        $this->database()->table('battlelog_maps')->create()
            ->addColumn('id', 'primary')
            ->addColumn('internal_id', 'string', ['length' => 128])
            ->addColumn('name', 'string', ['length' => 128])
            ->addColumn('description', 'text', ['nullable' => true])
            ->addColumn('image_url', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('game_mode', 'string', ['length' => 50, 'default' => 'conflict'])
            ->addColumn('size_km', 'integer', ['default' => 0])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['internal_id'], ['unique' => true])
            ->save();

        // Weapons table
        $this->database()->table('battlelog_weapons')->create()
            ->addColumn('id', 'primary')
            ->addColumn('internal_id', 'string', ['length' => 128])
            ->addColumn('name', 'string', ['length' => 128])
            ->addColumn('category', 'string', ['length' => 50])
            ->addColumn('faction', 'string', ['length' => 20, 'default' => 'neutral'])
            ->addColumn('description', 'text', ['nullable' => true])
            ->addColumn('icon_url', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('base_damage', 'integer', ['default' => 0])
            ->addColumn('fire_rate', 'integer', ['default' => 0])
            ->addColumn('magazine_size', 'integer', ['default' => 0])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['internal_id'], ['unique' => true])
            ->save();

        // Vehicles table
        $this->database()->table('battlelog_vehicles')->create()
            ->addColumn('id', 'primary')
            ->addColumn('internal_id', 'string', ['length' => 128])
            ->addColumn('name', 'string', ['length' => 128])
            ->addColumn('category', 'string', ['length' => 50])
            ->addColumn('faction', 'string', ['length' => 20, 'default' => 'neutral'])
            ->addColumn('description', 'text', ['nullable' => true])
            ->addColumn('icon_url', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('seats', 'integer', ['default' => 1])
            ->addColumn('has_weapons', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['internal_id'], ['unique' => true])
            ->save();

        // Achievements table
        $this->database()->table('battlelog_achievements')->create()
            ->addColumn('id', 'primary')
            ->addColumn('code', 'string', ['length' => 64])
            ->addColumn('name', 'string', ['length' => 128])
            ->addColumn('description', 'text', ['nullable' => true])
            ->addColumn('category', 'string', ['length' => 50, 'default' => 'general'])
            ->addColumn('rarity', 'string', ['length' => 20, 'default' => 'common'])
            ->addColumn('icon_url', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('requirement_type', 'string', ['length' => 50])
            ->addColumn('requirement_value', 'integer', ['default' => 1])
            ->addColumn('points', 'integer', ['default' => 10])
            ->addColumn('is_hidden', 'boolean', ['default' => false])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('sort_order', 'integer', ['default' => 0])
            ->addColumn('created_at', 'datetime')
            ->addIndex(['code'], ['unique' => true])
            ->save();

        // Players table
        $this->database()->table('battlelog_players')->create()
            ->addColumn('id', 'primary')
            ->addColumn('user_id', 'integer', ['nullable' => true])
            ->addColumn('platform_id', 'string', ['length' => 64])
            ->addColumn('platform', 'string', ['length' => 20, 'default' => 'steam'])
            ->addColumn('name', 'string', ['length' => 255])
            ->addColumn('avatar_url', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('total_playtime', 'integer', ['default' => 0])
            ->addColumn('total_kills', 'integer', ['default' => 0])
            ->addColumn('total_deaths', 'integer', ['default' => 0])
            ->addColumn('total_assists', 'integer', ['default' => 0])
            ->addColumn('total_headshots', 'integer', ['default' => 0])
            ->addColumn('shots_fired', 'integer', ['default' => 0])
            ->addColumn('shots_hit', 'integer', ['default' => 0])
            ->addColumn('longest_kill', 'float', ['default' => 0])
            ->addColumn('best_killstreak', 'integer', ['default' => 0])
            ->addColumn('total_score', 'integer', ['default' => 0])
            ->addColumn('wins', 'integer', ['default' => 0])
            ->addColumn('losses', 'integer', ['default' => 0])
            ->addColumn('games_played', 'integer', ['default' => 0])
            ->addColumn('objectives_captured', 'integer', ['default' => 0])
            ->addColumn('objectives_defended', 'integer', ['default' => 0])
            ->addColumn('revives', 'integer', ['default' => 0])
            ->addColumn('heals', 'integer', ['default' => 0])
            ->addColumn('repairs', 'integer', ['default' => 0])
            ->addColumn('vehicle_kills', 'integer', ['default' => 0])
            ->addColumn('vehicles_destroyed', 'integer', ['default' => 0])
            ->addColumn('roadkills', 'integer', ['default' => 0])
            ->addColumn('rank_points', 'integer', ['default' => 1000])
            ->addColumn('rank_name', 'string', ['length' => 50, 'default' => 'Recruit'])
            ->addColumn('first_seen', 'datetime', ['nullable' => true])
            ->addColumn('last_seen', 'datetime', ['nullable' => true])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['platform_id'], ['unique' => true])
            ->addIndex(['user_id'])
            ->save();

        // Game sessions table
        $this->database()->table('battlelog_game_sessions')->create()
            ->addColumn('id', 'primary')
            ->addColumn('server_id', 'string', ['length' => 64])
            ->addColumn('server_name', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('map_id', 'integer', ['nullable' => true])
            ->addColumn('scenario_id', 'string', ['length' => 255, 'nullable' => true])
            ->addColumn('game_mode', 'string', ['length' => 50, 'default' => 'conflict'])
            ->addColumn('started_at', 'datetime')
            ->addColumn('ended_at', 'datetime', ['nullable' => true])
            ->addColumn('winner_faction', 'string', ['length' => 20, 'nullable' => true])
            ->addColumn('us_score', 'integer', ['default' => 0])
            ->addColumn('ussr_score', 'integer', ['default' => 0])
            ->addColumn('max_players', 'integer', ['default' => 0])
            ->addColumn('total_players', 'integer', ['default' => 0])
            ->addColumn('total_kills', 'integer', ['default' => 0])
            ->addColumn('status', 'string', ['length' => 20, 'default' => 'active'])
            ->addColumn('session_data', 'text', ['nullable' => true])
            ->addIndex(['server_id'])
            ->addIndex(['started_at'])
            ->save();

        // Player sessions table
        $this->database()->table('battlelog_player_sessions')->create()
            ->addColumn('id', 'primary')
            ->addColumn('session_id', 'integer')
            ->addColumn('player_id', 'integer')
            ->addColumn('faction', 'string', ['length' => 20])
            ->addColumn('joined_at', 'datetime')
            ->addColumn('left_at', 'datetime', ['nullable' => true])
            ->addColumn('kills', 'integer', ['default' => 0])
            ->addColumn('deaths', 'integer', ['default' => 0])
            ->addColumn('assists', 'integer', ['default' => 0])
            ->addColumn('score', 'integer', ['default' => 0])
            ->addColumn('headshots', 'integer', ['default' => 0])
            ->addColumn('objectives_captured', 'integer', ['default' => 0])
            ->addColumn('objectives_defended', 'integer', ['default' => 0])
            ->addColumn('revives', 'integer', ['default' => 0])
            ->addColumn('heals', 'integer', ['default' => 0])
            ->addColumn('vehicle_kills', 'integer', ['default' => 0])
            ->addColumn('longest_kill', 'float', ['default' => 0])
            ->addColumn('best_killstreak', 'integer', ['default' => 0])
            ->addColumn('is_winner', 'boolean', ['nullable' => true])
            ->addColumn('is_mvp', 'boolean', ['default' => false])
            ->addColumn('stats_json', 'text', ['nullable' => true])
            ->addIndex(['session_id'])
            ->addIndex(['player_id'])
            ->save();

        // Kill events table
        $this->database()->table('battlelog_kill_events')->create()
            ->addColumn('id', 'primary')
            ->addColumn('session_id', 'integer')
            ->addColumn('killer_id', 'integer', ['nullable' => true])
            ->addColumn('victim_id', 'integer')
            ->addColumn('weapon_id', 'integer', ['nullable' => true])
            ->addColumn('vehicle_id', 'integer', ['nullable' => true])
            ->addColumn('distance', 'float', ['default' => 0])
            ->addColumn('is_headshot', 'boolean', ['default' => false])
            ->addColumn('is_teamkill', 'boolean', ['default' => false])
            ->addColumn('is_suicide', 'boolean', ['default' => false])
            ->addColumn('is_roadkill', 'boolean', ['default' => false])
            ->addColumn('killer_position', 'string', ['length' => 100, 'nullable' => true])
            ->addColumn('victim_position', 'string', ['length' => 100, 'nullable' => true])
            ->addColumn('killer_faction', 'string', ['length' => 20, 'nullable' => true])
            ->addColumn('victim_faction', 'string', ['length' => 20, 'nullable' => true])
            ->addColumn('timestamp', 'datetime')
            ->addIndex(['session_id'])
            ->addIndex(['killer_id'])
            ->addIndex(['victim_id'])
            ->addIndex(['timestamp'])
            ->save();

        // Player weapon stats table
        $this->database()->table('battlelog_player_weapon_stats')->create()
            ->addColumn('id', 'primary')
            ->addColumn('player_id', 'integer')
            ->addColumn('weapon_id', 'integer')
            ->addColumn('kills', 'integer', ['default' => 0])
            ->addColumn('deaths', 'integer', ['default' => 0])
            ->addColumn('headshots', 'integer', ['default' => 0])
            ->addColumn('shots_fired', 'integer', ['default' => 0])
            ->addColumn('shots_hit', 'integer', ['default' => 0])
            ->addColumn('longest_kill', 'float', ['default' => 0])
            ->addColumn('time_used', 'integer', ['default' => 0])
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['player_id', 'weapon_id'], ['unique' => true])
            ->save();

        // Player vehicle stats table
        $this->database()->table('battlelog_player_vehicle_stats')->create()
            ->addColumn('id', 'primary')
            ->addColumn('player_id', 'integer')
            ->addColumn('vehicle_id', 'integer')
            ->addColumn('kills', 'integer', ['default' => 0])
            ->addColumn('deaths', 'integer', ['default' => 0])
            ->addColumn('destroyed', 'integer', ['default' => 0])
            ->addColumn('roadkills', 'integer', ['default' => 0])
            ->addColumn('time_used', 'integer', ['default' => 0])
            ->addColumn('distance_traveled', 'float', ['default' => 0])
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['player_id', 'vehicle_id'], ['unique' => true])
            ->save();

        // Player achievements table
        $this->database()->table('battlelog_player_achievements')->create()
            ->addColumn('id', 'primary')
            ->addColumn('player_id', 'integer')
            ->addColumn('achievement_id', 'integer')
            ->addColumn('progress', 'integer', ['default' => 0])
            ->addColumn('is_unlocked', 'boolean', ['default' => false])
            ->addColumn('unlocked_at', 'datetime', ['nullable' => true])
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['player_id', 'achievement_id'], ['unique' => true])
            ->save();

        // Leaderboards table
        $this->database()->table('battlelog_leaderboards')->create()
            ->addColumn('id', 'primary')
            ->addColumn('player_id', 'integer')
            ->addColumn('category', 'string', ['length' => 50])
            ->addColumn('period', 'string', ['length' => 20, 'default' => 'all_time'])
            ->addColumn('score', 'float', ['default' => 0])
            ->addColumn('rank', 'integer', ['default' => 0])
            ->addColumn('previous_rank', 'integer', ['nullable' => true])
            ->addColumn('updated_at', 'datetime')
            ->addIndex(['category', 'period', 'rank'])
            ->addIndex(['player_id', 'category', 'period'], ['unique' => true])
            ->save();
    }

    public function down(): void
    {
        $this->database()->table('battlelog_leaderboards')->drop();
        $this->database()->table('battlelog_player_achievements')->drop();
        $this->database()->table('battlelog_player_vehicle_stats')->drop();
        $this->database()->table('battlelog_player_weapon_stats')->drop();
        $this->database()->table('battlelog_kill_events')->drop();
        $this->database()->table('battlelog_player_sessions')->drop();
        $this->database()->table('battlelog_game_sessions')->drop();
        $this->database()->table('battlelog_players')->drop();
        $this->database()->table('battlelog_achievements')->drop();
        $this->database()->table('battlelog_vehicles')->drop();
        $this->database()->table('battlelog_weapons')->drop();
        $this->database()->table('battlelog_maps')->drop();
    }
}
