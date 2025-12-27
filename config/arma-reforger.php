<?php

/**
 * Arma Reforger Server Manager Configuration
 *
 * This file contains the default configuration for the Arma Reforger
 * Server Manager module. Modify these values as needed.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | SteamCMD Path
    |--------------------------------------------------------------------------
    |
    | The full path to the SteamCMD executable. This is required for
    | downloading and updating server files.
    |
    */
    'steamcmd_path' => env('REFORGER_STEAMCMD_PATH', '/usr/games/steamcmd'),

    /*
    |--------------------------------------------------------------------------
    | Default Installation Path
    |--------------------------------------------------------------------------
    |
    | The base directory where server installations will be stored.
    | Each server will have its own subdirectory.
    |
    */
    'install_path' => env('REFORGER_INSTALL_PATH', storage_path('reforger-servers')),

    /*
    |--------------------------------------------------------------------------
    | Auto Update
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically check for and install
    | server updates during the scheduled maintenance window.
    |
    */
    'auto_update' => env('REFORGER_AUTO_UPDATE', false),

    /*
    |--------------------------------------------------------------------------
    | Auto Restart
    |--------------------------------------------------------------------------
    |
    | When enabled, servers will be automatically restarted after updates
    | or if they crash unexpectedly.
    |
    */
    'auto_restart' => env('REFORGER_AUTO_RESTART', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic backups before updates and other operations.
    |
    */
    'backup_enabled' => env('REFORGER_BACKUP_ENABLED', false),
    'backup_path' => env('REFORGER_BACKUP_PATH', storage_path('reforger-backups')),

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep server log files before automatic cleanup.
    |
    */
    'log_retention_days' => env('REFORGER_LOG_RETENTION', 30),

    /*
    |--------------------------------------------------------------------------
    | Workshop API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Bohemia Workshop API used for mod management.
    |
    */
    'workshop_api_url' => 'https://reforger.armaplatform.com/api/v1',
];
