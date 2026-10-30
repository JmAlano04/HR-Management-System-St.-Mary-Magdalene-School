<?php

use HafizAsifAli\ZkTeco\Laravel\Models\Attendance;

return [

    /*
    |--------------------------------------------------------------------------
    | Default device
    |--------------------------------------------------------------------------
    |
    | The device used when you call ZkTeco::device() with no name, and by the
    | Artisan commands when no --device option is given.
    |
    */

    'default' => env('ZKTECO_DEVICE', 'main'),

    /*
    |--------------------------------------------------------------------------
    | Devices (direct connection)
    |--------------------------------------------------------------------------
    |
    | Devices your server connects to over the network, on port 4370.
    |
    | host          IP address of the device.
    | port          Almost always 4370.
    | transport     "tcp" for current firmware, "udp" for older panels. If a
    |               device accepts the connection but never answers, try udp.
    | comm_key      The numeric COMM key set under Menu > Comm > Security, or
    |               0 when none is set. This is not the admin password.
    | timeout       Seconds to wait for a reply. Raise it for large logs.
    | timezone      The timezone the device clock is set to. Devices store no
    |               offset, so a wrong value shifts every punch.
    | name_encoding Codepage for names, e.g. "Windows-1256" for Arabic firmware.
    |
    */

    'devices' => [

        'main' => [
            'host' => env('ZKTECO_HOST', '192.168.10.201'),
            'port' => (int) env('ZKTECO_PORT', 4370),
            'transport' => env('ZKTECO_TRANSPORT', 'tcp'),
            'comm_key' => (int) env('ZKTECO_COMM_KEY', 0),
            'timeout' => (float) env('ZKTECO_TIMEOUT', 10),
            'timezone' => env('ZKTECO_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
            'name_encoding' => env('ZKTECO_NAME_ENCODING', 'UTF-8'),
        ],

        // 'warehouse' => [
        //     'host' => '10.0.20.15',
        //     'transport' => 'udp',
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Storing attendance
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan zkteco:sync`, the SyncAttendance job and the push
    | server. Punches are deduplicated, so polling the same device repeatedly
    | is safe.
    |
    */

    'storage' => [
        // Set to false to receive events only and store punches yourself.
        'enabled' => env('ZKTECO_STORE_ATTENDANCE', true),

        // Model used for stored punches. Extend the package model to customise.
        'model' => Attendance::class,

        'table' => 'zkteco_attendances',

        // Rows per insert when saving a large download.
        'chunk_size' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Push server (ADMS / iclock)
    |--------------------------------------------------------------------------
    |
    | For devices set to push data to your server instead of being polled. On
    | the device, set the Cloud Server / ADMS address to this app's host and
    | port. The device requests /iclock/cdata, so keep the prefix "iclock"
    | unless the firmware lets you change it.
    |
    */

    'push' => [
        'enabled' => env('ZKTECO_PUSH_ENABLED', false),

        'prefix' => env('ZKTECO_PUSH_PREFIX', 'iclock'),

        // Keep this list free of auth/session middleware: devices send no
        // cookies or CSRF tokens.
        'middleware' => [],

        // New serial numbers wait for `php artisan zkteco:push-devices approve`
        // unless this is true. Leave it off for anything reachable from outside.
        'auto_approve' => env('ZKTECO_PUSH_AUTO_APPROVE', false),

        'realtime' => true,
        'poll_delay' => 30,
        'error_delay' => 60,
        'transfer_interval' => 1,
        'commands_per_poll' => 20,
        'timezone' => env('ZKTECO_PUSH_TIMEZONE', env('APP_TIMEZONE', 'UTC')),
        'name_encoding' => env('ZKTECO_NAME_ENCODING', 'UTF-8'),
    ],

];
