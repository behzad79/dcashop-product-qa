<?php

if (!defined('ABSPATH')) {
    exit;
}

class DCQA_DB
{
    public static function install()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'product_questions';

        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (

            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            product_id BIGINT UNSIGNED NOT NULL,

            parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,

            user_id BIGINT UNSIGNED NULL DEFAULT NULL,

            display_name VARCHAR(255) NULL DEFAULT NULL,

            content LONGTEXT NOT NULL,

            status ENUM('pending','approved','rejected')
                DEFAULT 'pending',

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            updated_at DATETIME NULL DEFAULT NULL,

            PRIMARY KEY (id),

            KEY product_id (product_id),

            KEY parent_id (parent_id),

            KEY status (status),

            KEY user_id (user_id)

        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($sql);
    }
}