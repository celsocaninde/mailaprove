<?php

define('PLUGIN_MAILAPROVE_VERSION', '1.0.5');
define('PLUGIN_MAILAPROVE_MIN_GLPI', '11.0.0');
define('PLUGIN_MAILAPROVE_MAX_GLPI', '11.99.99');

use Glpi\Plugin\Hooks;
use GlpiPlugin\Mailaprove\NotificationHandler;

function plugin_version_mailaprove(): array
{
    return [
        'name'           => 'Approval By Mail',
        'version'        => PLUGIN_MAILAPROVE_VERSION,
        'author'         => 'Celso Caninde',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/celsocaninde/mailaprove',
        'minGlpiVersion' => PLUGIN_MAILAPROVE_MIN_GLPI,
        'maxGlpiVersion' => PLUGIN_MAILAPROVE_MAX_GLPI,
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_MAILAPROVE_MIN_GLPI,
                'max' => PLUGIN_MAILAPROVE_MAX_GLPI,
            ],
        ],
    ];
}

function plugin_init_mailaprove(): void
{
    global $PLUGIN_HOOKS;

    // CSRF compliance (required in GLPI 11)
    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['mailaprove'] = true;

    // Register notification data hook - injects custom tags
    $PLUGIN_HOOKS[Hooks::ITEM_GET_DATA]['mailaprove'] = [
        'NotificationTargetTicket' => [NotificationHandler::class, 'handleNotificationData'],
    ];

    // Public mail links must be reachable without an authenticated GLPI session.
    // In GLPI 11, stateless plugin paths bypass session, firewall and CSRF checks.
    if (
        class_exists(\Glpi\Http\SessionManager::class)
        && method_exists(\Glpi\Http\SessionManager::class, 'registerPluginStatelessPath')
    ) {
        \Glpi\Http\SessionManager::registerPluginStatelessPath(
            'mailaprove',
            '#^/front/(approve|reject|solution_approve|solution_reject|satisfaction)\.php$#'
        );
    }

    // Admin menu for configuration
    if (Plugin::isPluginActive('mailaprove')) {
        $PLUGIN_HOOKS['config_page']['mailaprove'] = 'front/config.form.php';

        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS['menu_toadd']['mailaprove'] = [
                'config' => 'GlpiPlugin\\Mailaprove\\Config',
            ];
        }
    }
}

function plugin_mailaprove_check_prerequisites(): bool
{
    if (!method_exists('Plugin', 'checkGlpiVersion')) {
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
        if (version_compare($version, PLUGIN_MAILAPROVE_MIN_GLPI, '<')) {
            echo 'This plugin requires GLPI >= ' . PLUGIN_MAILAPROVE_MIN_GLPI;
            return false;
        }
    }
    return true;
}

function plugin_mailaprove_check_config($verbose = false): bool
{
    return true;
}
