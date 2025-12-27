<?php

namespace Flute\Modules\ArmaBattlelog;

use Flute\Core\Support\AbstractModuleInstaller;
use Flute\Modules\ArmaBattlelog\Database\Entities\Achievement;
use Flute\Modules\ArmaBattlelog\Database\Entities\GameMap;
use Flute\Modules\ArmaBattlelog\Database\Entities\Vehicle;
use Flute\Modules\ArmaBattlelog\Database\Entities\Weapon;

class Installer extends AbstractModuleInstaller
{
    public function install(\Flute\Core\Modules\ModuleInformation $moduleInfo): bool
    {
        $this->runMigrations();
        $this->seedDefaultData();

        return true;
    }

    public function uninstall(\Flute\Core\Modules\ModuleInformation $moduleInfo): bool
    {
        // Optionally drop tables on uninstall
        // $this->rollbackMigrations();

        return true;
    }

    /**
     * Seed default weapons, vehicles, maps, and achievements
     */
    protected function seedDefaultData(): void
    {
        $this->seedWeapons();
        $this->seedVehicles();
        $this->seedMaps();
        $this->seedAchievements();
    }

    protected function seedWeapons(): void
    {
        $weapons = [
            // US Army Weapons
            ['internal_id' => 'M16A2', 'name' => 'M16A2', 'category' => 'assault_rifle', 'faction' => 'us', 'magazine_size' => 30, 'fire_rate' => 800],
            ['internal_id' => 'M4A1', 'name' => 'M4A1', 'category' => 'assault_rifle', 'faction' => 'us', 'magazine_size' => 30, 'fire_rate' => 750],
            ['internal_id' => 'M249', 'name' => 'M249 SAW', 'category' => 'lmg', 'faction' => 'us', 'magazine_size' => 200, 'fire_rate' => 750],
            ['internal_id' => 'M60', 'name' => 'M60', 'category' => 'lmg', 'faction' => 'us', 'magazine_size' => 100, 'fire_rate' => 550],
            ['internal_id' => 'M21', 'name' => 'M21 SWS', 'category' => 'sniper', 'faction' => 'us', 'magazine_size' => 20, 'fire_rate' => 60],
            ['internal_id' => 'M9', 'name' => 'M9 Beretta', 'category' => 'pistol', 'faction' => 'us', 'magazine_size' => 15, 'fire_rate' => 100],
            ['internal_id' => 'M72LAW', 'name' => 'M72 LAW', 'category' => 'launcher', 'faction' => 'us', 'magazine_size' => 1, 'fire_rate' => 5],
            ['internal_id' => 'M67', 'name' => 'M67 Frag Grenade', 'category' => 'grenade', 'faction' => 'us', 'magazine_size' => 1, 'fire_rate' => 0],

            // Soviet Weapons
            ['internal_id' => 'AK74', 'name' => 'AK-74', 'category' => 'assault_rifle', 'faction' => 'ussr', 'magazine_size' => 30, 'fire_rate' => 650],
            ['internal_id' => 'AKS74U', 'name' => 'AKS-74U', 'category' => 'smg', 'faction' => 'ussr', 'magazine_size' => 30, 'fire_rate' => 700],
            ['internal_id' => 'PKM', 'name' => 'PKM', 'category' => 'lmg', 'faction' => 'ussr', 'magazine_size' => 100, 'fire_rate' => 650],
            ['internal_id' => 'SVD', 'name' => 'SVD Dragunov', 'category' => 'sniper', 'faction' => 'ussr', 'magazine_size' => 10, 'fire_rate' => 50],
            ['internal_id' => 'PM', 'name' => 'Makarov PM', 'category' => 'pistol', 'faction' => 'ussr', 'magazine_size' => 8, 'fire_rate' => 100],
            ['internal_id' => 'RPG7', 'name' => 'RPG-7', 'category' => 'launcher', 'faction' => 'ussr', 'magazine_size' => 1, 'fire_rate' => 5],
            ['internal_id' => 'RGD5', 'name' => 'RGD-5 Grenade', 'category' => 'grenade', 'faction' => 'ussr', 'magazine_size' => 1, 'fire_rate' => 0],
        ];

        foreach ($weapons as $data) {
            $weapon = Weapon::findOne(['internal_id' => $data['internal_id']]);
            if (!$weapon) {
                $weapon = new Weapon();
                foreach ($data as $key => $value) {
                    $weapon->$key = $value;
                }
                $weapon->created_at = new \DateTimeImmutable();
                $weapon->save();
            }
        }
    }

    protected function seedVehicles(): void
    {
        $vehicles = [
            // US Vehicles
            ['internal_id' => 'M998_HMMWV', 'name' => 'HMMWV', 'category' => 'transport', 'faction' => 'us', 'seats' => 4, 'has_weapons' => false],
            ['internal_id' => 'M998_HMMWV_MG', 'name' => 'HMMWV (M2)', 'category' => 'transport', 'faction' => 'us', 'seats' => 4, 'has_weapons' => true],
            ['internal_id' => 'M2A3_Bradley', 'name' => 'M2A3 Bradley', 'category' => 'ifv', 'faction' => 'us', 'seats' => 9, 'has_weapons' => true],
            ['internal_id' => 'M1A1_Abrams', 'name' => 'M1A1 Abrams', 'category' => 'tank', 'faction' => 'us', 'seats' => 4, 'has_weapons' => true],
            ['internal_id' => 'UH60_Blackhawk', 'name' => 'UH-60 Black Hawk', 'category' => 'helicopter', 'faction' => 'us', 'seats' => 14, 'has_weapons' => false],

            // Soviet Vehicles
            ['internal_id' => 'UAZ469', 'name' => 'UAZ-469', 'category' => 'transport', 'faction' => 'ussr', 'seats' => 4, 'has_weapons' => false],
            ['internal_id' => 'UAZ469_MG', 'name' => 'UAZ-469 (DShK)', 'category' => 'transport', 'faction' => 'ussr', 'seats' => 4, 'has_weapons' => true],
            ['internal_id' => 'BTR70', 'name' => 'BTR-70', 'category' => 'apc', 'faction' => 'ussr', 'seats' => 9, 'has_weapons' => true],
            ['internal_id' => 'BMP2', 'name' => 'BMP-2', 'category' => 'ifv', 'faction' => 'ussr', 'seats' => 10, 'has_weapons' => true],
            ['internal_id' => 'T72', 'name' => 'T-72', 'category' => 'tank', 'faction' => 'ussr', 'seats' => 3, 'has_weapons' => true],
            ['internal_id' => 'Mi8', 'name' => 'Mi-8', 'category' => 'helicopter', 'faction' => 'ussr', 'seats' => 24, 'has_weapons' => false],
        ];

        foreach ($vehicles as $data) {
            $vehicle = Vehicle::findOne(['internal_id' => $data['internal_id']]);
            if (!$vehicle) {
                $vehicle = new Vehicle();
                foreach ($data as $key => $value) {
                    $vehicle->$key = $value;
                }
                $vehicle->created_at = new \DateTimeImmutable();
                $vehicle->save();
            }
        }
    }

    protected function seedMaps(): void
    {
        $maps = [
            ['internal_id' => 'everon', 'name' => 'Everon', 'game_mode' => 'conflict', 'size_km' => 51, 'description' => 'A 51 km² island in the Baltic Sea'],
            ['internal_id' => 'arland', 'name' => 'Arland', 'game_mode' => 'conflict', 'size_km' => 52, 'description' => 'A 52 km² terrain set in the French countryside'],
            ['internal_id' => 'everon_combat_ops', 'name' => 'Everon (Combat Ops)', 'game_mode' => 'combat_ops', 'size_km' => 51],
            ['internal_id' => 'arland_combat_ops', 'name' => 'Arland (Combat Ops)', 'game_mode' => 'combat_ops', 'size_km' => 52],
        ];

        foreach ($maps as $data) {
            $map = GameMap::findOne(['internal_id' => $data['internal_id']]);
            if (!$map) {
                $map = new GameMap();
                foreach ($data as $key => $value) {
                    $map->$key = $value;
                }
                $map->created_at = new \DateTimeImmutable();
                $map->save();
            }
        }
    }

    protected function seedAchievements(): void
    {
        $achievements = [
            // Combat achievements
            ['code' => 'first_blood', 'name' => 'First Blood', 'description' => 'Get your first kill', 'category' => 'combat', 'rarity' => 'common', 'requirement_type' => 'kills', 'requirement_value' => 1, 'points' => 10],
            ['code' => 'centurion', 'name' => 'Centurion', 'description' => 'Get 100 kills', 'category' => 'combat', 'rarity' => 'uncommon', 'requirement_type' => 'kills', 'requirement_value' => 100, 'points' => 50],
            ['code' => 'killing_machine', 'name' => 'Killing Machine', 'description' => 'Get 1000 kills', 'category' => 'combat', 'rarity' => 'rare', 'requirement_type' => 'kills', 'requirement_value' => 1000, 'points' => 200],
            ['code' => 'legend', 'name' => 'Legend', 'description' => 'Get 10000 kills', 'category' => 'combat', 'rarity' => 'legendary', 'requirement_type' => 'kills', 'requirement_value' => 10000, 'points' => 1000],

            // Headshot achievements
            ['code' => 'marksman', 'name' => 'Marksman', 'description' => 'Get 10 headshots', 'category' => 'combat', 'rarity' => 'common', 'requirement_type' => 'headshots', 'requirement_value' => 10, 'points' => 20],
            ['code' => 'sharpshooter', 'name' => 'Sharpshooter', 'description' => 'Get 100 headshots', 'category' => 'combat', 'rarity' => 'uncommon', 'requirement_type' => 'headshots', 'requirement_value' => 100, 'points' => 100],
            ['code' => 'sniper_elite', 'name' => 'Sniper Elite', 'description' => 'Get 500 headshots', 'category' => 'combat', 'rarity' => 'rare', 'requirement_type' => 'headshots', 'requirement_value' => 500, 'points' => 300],

            // Long range achievements
            ['code' => 'long_shot', 'name' => 'Long Shot', 'description' => 'Get a kill from 300+ meters', 'category' => 'combat', 'rarity' => 'uncommon', 'requirement_type' => 'longest_kill', 'requirement_value' => 300, 'points' => 50],
            ['code' => 'extreme_range', 'name' => 'Extreme Range', 'description' => 'Get a kill from 500+ meters', 'category' => 'combat', 'rarity' => 'rare', 'requirement_type' => 'longest_kill', 'requirement_value' => 500, 'points' => 150],
            ['code' => 'record_breaker', 'name' => 'Record Breaker', 'description' => 'Get a kill from 1000+ meters', 'category' => 'combat', 'rarity' => 'legendary', 'requirement_type' => 'longest_kill', 'requirement_value' => 1000, 'points' => 500],

            // Vehicle achievements
            ['code' => 'road_rage', 'name' => 'Road Rage', 'description' => 'Get 10 roadkills', 'category' => 'vehicle', 'rarity' => 'uncommon', 'requirement_type' => 'roadkills', 'requirement_value' => 10, 'points' => 50],
            ['code' => 'tank_destroyer', 'name' => 'Tank Destroyer', 'description' => 'Destroy 50 vehicles', 'category' => 'vehicle', 'rarity' => 'rare', 'requirement_type' => 'vehicles_destroyed', 'requirement_value' => 50, 'points' => 200],
            ['code' => 'vehicle_master', 'name' => 'Vehicle Master', 'description' => 'Get 500 kills in vehicles', 'category' => 'vehicle', 'rarity' => 'epic', 'requirement_type' => 'vehicle_kills', 'requirement_value' => 500, 'points' => 400],

            // Teamplay achievements
            ['code' => 'combat_medic', 'name' => 'Combat Medic', 'description' => 'Revive 50 teammates', 'category' => 'teamplay', 'rarity' => 'uncommon', 'requirement_type' => 'revives', 'requirement_value' => 50, 'points' => 100],
            ['code' => 'angel_of_mercy', 'name' => 'Angel of Mercy', 'description' => 'Revive 500 teammates', 'category' => 'teamplay', 'rarity' => 'epic', 'requirement_type' => 'revives', 'requirement_value' => 500, 'points' => 400],

            // Objective achievements
            ['code' => 'flag_raiser', 'name' => 'Flag Raiser', 'description' => 'Capture 10 objectives', 'category' => 'objective', 'rarity' => 'common', 'requirement_type' => 'objectives_captured', 'requirement_value' => 10, 'points' => 30],
            ['code' => 'objective_master', 'name' => 'Objective Master', 'description' => 'Capture 100 objectives', 'category' => 'objective', 'rarity' => 'rare', 'requirement_type' => 'objectives_captured', 'requirement_value' => 100, 'points' => 200],

            // Win achievements
            ['code' => 'winner', 'name' => 'Winner', 'description' => 'Win 10 matches', 'category' => 'general', 'rarity' => 'common', 'requirement_type' => 'wins', 'requirement_value' => 10, 'points' => 30],
            ['code' => 'champion', 'name' => 'Champion', 'description' => 'Win 100 matches', 'category' => 'general', 'rarity' => 'rare', 'requirement_type' => 'wins', 'requirement_value' => 100, 'points' => 200],

            // Playtime achievements
            ['code' => 'dedicated', 'name' => 'Dedicated', 'description' => 'Play for 10 hours', 'category' => 'general', 'rarity' => 'common', 'requirement_type' => 'playtime', 'requirement_value' => 36000, 'points' => 20],
            ['code' => 'veteran', 'name' => 'Veteran', 'description' => 'Play for 100 hours', 'category' => 'general', 'rarity' => 'uncommon', 'requirement_type' => 'playtime', 'requirement_value' => 360000, 'points' => 100],
            ['code' => 'no_life', 'name' => 'No Life', 'description' => 'Play for 1000 hours', 'category' => 'general', 'rarity' => 'legendary', 'requirement_type' => 'playtime', 'requirement_value' => 3600000, 'points' => 1000],
        ];

        foreach ($achievements as $data) {
            $achievement = Achievement::findOne(['code' => $data['code']]);
            if (!$achievement) {
                $achievement = new Achievement();
                foreach ($data as $key => $value) {
                    $achievement->$key = $value;
                }
                $achievement->created_at = new \DateTimeImmutable();
                $achievement->save();
            }
        }
    }
}
