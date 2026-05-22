<?php

namespace GlpiPlugin\Mailaprove;

use CommonDBTM;
use CommonGLPI;
use Html;
use Session;

class Config extends CommonDBTM
{
    public static $table = 'glpi_plugin_mailaprove_configs';
    public static $rightname = 'config';

    /**
     * Get current plugin configuration.
     *
     * @return array Configuration values
     */
    public static function getConfig(): array
    {
        global $DB;

        $defaults = [
            'token_expiration_hours'    => 72,
            'used_token_retention_days' => 30,
            'audit_retention_days'      => 180,
            'enable_validation'         => 1,
            'enable_solution'           => 1,
            'enable_satisfaction'       => 1,
            'template_validation'       => '',
            'template_solution'         => '',
            'template_satisfaction'     => '',
        ];

        if (!$DB->tableExists(self::$table)) {
            return $defaults;
        }

        self::ensureSchema();

        $iterator = $DB->request([
            'FROM'  => self::$table,
            'WHERE' => ['id' => 1],
            'LIMIT' => 1,
        ]);

        if (count($iterator) === 0) {
            return $defaults;
        }

        return array_merge($defaults, (array)$iterator->current());
    }

    /**
     * Update plugin configuration.
     *
     * @param array $values Key-value pairs to update
     *
     * @return bool
     */
    public static function updateConfig(array $values): bool
    {
        global $DB;

        $config = new self();

        $allowed = [
            'token_expiration_hours',
            'used_token_retention_days',
            'audit_retention_days',
            'enable_validation',
            'enable_solution',
            'enable_satisfaction',
            'template_validation',
            'template_solution',
            'template_satisfaction',
        ];

        $input = ['id' => 1];
        foreach ($allowed as $key) {
            if (isset($values[$key])) {
                $input[$key] = $values[$key];
            }
        }

        if (!$DB->tableExists(self::$table)) {
            return false;
        }

        self::ensureSchema();

        $exists = $DB->request([
            'FROM'  => self::$table,
            'WHERE' => ['id' => 1],
            'LIMIT' => 1,
        ])->current();

        if (!$exists) {
            return (bool) $config->add($input);
        }

        return $config->update($input);
    }

    public static function getTypeName($nb = 0): string
    {
        return __('Configuração da aprovação por e-mail', 'mailaprove');
    }

    public static function getIcon(): string
    {
        return 'fas fa-envelope-open-text';
    }

    public static function getMenuContent()
    {
        $menu = [
            'title' => self::getTypeName(),
            'page'  => '/plugins/mailaprove/front/config.form.php',
            'icon'  => self::getIcon(),
        ];
        return $menu;
    }

    public function rawSearchOptions(): array
    {
        return [];
    }

    public static function ensureSchema(): void
    {
        global $DB;

        if (!$DB->tableExists(self::$table)) {
            return;
        }

        foreach ([
            'used_token_retention_days' => 'INT UNSIGNED NOT NULL DEFAULT 30',
            'audit_retention_days'      => 'INT UNSIGNED NOT NULL DEFAULT 180',
            'template_validation'       => 'LONGTEXT DEFAULT NULL',
            'template_solution'         => 'LONGTEXT DEFAULT NULL',
            'template_satisfaction'     => 'LONGTEXT DEFAULT NULL',
        ] as $column => $definition) {
            if (!$DB->fieldExists(self::$table, $column)) {
                $DB->doQuery("ALTER TABLE `" . self::$table . "` ADD COLUMN `{$column}` {$definition}");
            }
        }
    }
}
