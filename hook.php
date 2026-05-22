<?php

function plugin_mailaprove_install(): bool
{
    global $DB;

    $migration = new Migration('1.0.0');

    // Create tokens table
    if (!$DB->tableExists('glpi_plugin_mailaprove_tokens')) {
        $query = "CREATE TABLE `glpi_plugin_mailaprove_tokens` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of the token',
            `action_type` VARCHAR(30) NOT NULL COMMENT 'validation_approve, validation_reject, solution_approve, solution_reject, satisfaction',
            `tickets_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `items_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID of the target item (TicketValidation, TicketSatisfaction, etc.)',
            `users_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'User authorized to perform the action',
            `is_used` TINYINT(1) NOT NULL DEFAULT 0,
            `use_reason` VARCHAR(32) DEFAULT NULL,
            `use_ip` VARCHAR(64) DEFAULT NULL,
            `use_user_agent` VARCHAR(255) DEFAULT NULL,
            `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_expiration` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `date_used` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token_hash` (`token_hash`),
            KEY `tickets_id` (`tickets_id`),
            KEY `users_id` (`users_id`),
            KEY `is_used` (`is_used`),
            KEY `date_expiration` (`date_expiration`),
            KEY `date_used` (`date_used`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $migration->addPostQuery($query);
    }

    foreach ([
        'use_reason'     => 'VARCHAR(32) DEFAULT NULL',
        'use_ip'         => 'VARCHAR(64) DEFAULT NULL',
        'use_user_agent' => 'VARCHAR(255) DEFAULT NULL',
        'date_used'      => 'TIMESTAMP NULL DEFAULT NULL',
    ] as $column => $definition) {
        if ($DB->tableExists('glpi_plugin_mailaprove_tokens') && !$DB->fieldExists('glpi_plugin_mailaprove_tokens', $column)) {
            $migration->addPostQuery("ALTER TABLE `glpi_plugin_mailaprove_tokens` ADD COLUMN `{$column}` {$definition}");
        }
    }

    // Create config table
    if (!$DB->tableExists('glpi_plugin_mailaprove_configs')) {
        $query = "CREATE TABLE `glpi_plugin_mailaprove_configs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `token_expiration_hours` INT UNSIGNED NOT NULL DEFAULT 72,
            `used_token_retention_days` INT UNSIGNED NOT NULL DEFAULT 30,
            `audit_retention_days` INT UNSIGNED NOT NULL DEFAULT 180,
            `enable_validation` TINYINT(1) NOT NULL DEFAULT 1,
            `enable_solution` TINYINT(1) NOT NULL DEFAULT 1,
            `enable_satisfaction` TINYINT(1) NOT NULL DEFAULT 1,
            `template_validation` LONGTEXT DEFAULT NULL,
            `template_solution` LONGTEXT DEFAULT NULL,
            `template_satisfaction` LONGTEXT DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $migration->addPostQuery($query);

        // Insert default config
        $migration->addPostQuery("INSERT INTO `glpi_plugin_mailaprove_configs` (`id`, `token_expiration_hours`, `used_token_retention_days`, `audit_retention_days`, `enable_validation`, `enable_solution`, `enable_satisfaction`) VALUES (1, 72, 30, 180, 1, 1, 1)");
    }

    foreach ([
        'used_token_retention_days' => 'INT UNSIGNED NOT NULL DEFAULT 30',
        'audit_retention_days'      => 'INT UNSIGNED NOT NULL DEFAULT 180',
        'template_validation'       => 'LONGTEXT DEFAULT NULL',
        'template_solution'         => 'LONGTEXT DEFAULT NULL',
        'template_satisfaction'     => 'LONGTEXT DEFAULT NULL',
    ] as $column => $definition) {
        if ($DB->tableExists('glpi_plugin_mailaprove_configs') && !$DB->fieldExists('glpi_plugin_mailaprove_configs', $column)) {
            $migration->addPostQuery("ALTER TABLE `glpi_plugin_mailaprove_configs` ADD COLUMN `{$column}` {$definition}");
        }
    }

    if (!$DB->tableExists('glpi_plugin_mailaprove_auditlogs')) {
        $query = "CREATE TABLE `glpi_plugin_mailaprove_auditlogs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event` VARCHAR(64) NOT NULL,
            `status` VARCHAR(32) NOT NULL DEFAULT 'info',
            `token_id` INT UNSIGNED DEFAULT NULL,
            `action_type` VARCHAR(30) DEFAULT NULL,
            `tickets_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `items_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `users_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `ip` VARCHAR(64) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `payload` LONGTEXT DEFAULT NULL,
            `date_creation` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `event` (`event`),
            KEY `status` (`status`),
            KEY `token_id` (`token_id`),
            KEY `tickets_id` (`tickets_id`),
            KEY `date_creation` (`date_creation`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC";
        $migration->addPostQuery($query);
    }

    // Register cron task for cleaning expired tokens
    CronTask::register(
        'GlpiPlugin\\Mailaprove\\Token',
        'CleanExpiredTokens',
        DAY_TIMESTAMP,
        [
            'comment'   => 'Clean expired approval tokens',
            'mode'      => CronTask::MODE_EXTERNAL,
            'state'     => CronTask::STATE_WAITING,
            'hourmin'   => 0,
            'hourmax'   => 24,
        ]
    );

    $migration->executeMigration();

    return true;
}

function plugin_mailaprove_uninstall(): bool
{
    global $DB;

    $migration = new Migration('1.0.0');

    $migration->addPostQuery('DROP TABLE IF EXISTS `glpi_plugin_mailaprove_tokens`');
    $migration->addPostQuery('DROP TABLE IF EXISTS `glpi_plugin_mailaprove_auditlogs`');
    $migration->addPostQuery('DROP TABLE IF EXISTS `glpi_plugin_mailaprove_configs`');

    $migration->executeMigration();

    // The cron itemtype is namespaced, so remove it explicitly.
    $DB->delete('glpi_crontasks', [
        'itemtype' => GlpiPlugin\Mailaprove\Token::class,
        'name'     => 'CleanExpiredTokens',
    ]);

    return true;
}
