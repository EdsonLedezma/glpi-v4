<?php

declare(strict_types=1);

use GlpiPlugin\Esjv4\Menu;
use GlpiPlugin\Esjv4\Plugin;

define('PLUGIN_ESJV4_VERSION', '0.1.0');
define('PLUGIN_ESJV4_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_ESJV4_MAX_GLPI_VERSION', '11.0.99');

spl_autoload_register(static function (string $class): void {
    $prefix = 'GlpiPlugin\\Esjv4\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

function plugin_init_esjv4(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant'][Plugin::KEY] = true;
    $PLUGIN_HOOKS['config_page'][Plugin::KEY] = 'front/dashboard.php';

    if (class_exists(Menu::class)) {
        $PLUGIN_HOOKS['menu_toadd'][Plugin::KEY] = [
            'tools' => Menu::class,
        ];
    }
}

function plugin_version_esjv4(): array
{
    return [
        'name'           => Plugin::NAME,
        'version'        => Plugin::VERSION,
        'author'         => 'ESJ',
        'license'        => 'GPLv3+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_ESJV4_MIN_GLPI_VERSION,
                'max' => PLUGIN_ESJV4_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

function plugin_esjv4_check_prerequisites(): bool
{
    if (!defined('GLPI_VERSION')) {
        return true;
    }

    return version_compare(GLPI_VERSION, PLUGIN_ESJV4_MIN_GLPI_VERSION, '>=')
        && version_compare(GLPI_VERSION, PLUGIN_ESJV4_MAX_GLPI_VERSION, '<');
}

function plugin_esjv4_check_config(bool $verbose = false): bool
{
    return true;
}
