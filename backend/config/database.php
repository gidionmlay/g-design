<?php
/**
 * Database connection settings resolved from environment configuration.
 */

declare(strict_types=1);

use App\Config\Config;

function gd_database_config(): array
{
    return [
        'host'     => (string) Config::get('db_host'),
        'port'     => (string) Config::get('db_port'),
        'name'     => (string) Config::get('db_name'),
        'charset'  => (string) Config::get('db_charset', 'utf8mb4'),
        'user'     => (string) Config::get('db_user'),
        'password' => (string) Config::get('db_password'),
    ];
}
