<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class TB_DB {
    const VERSION = '1.0.0';
    const OPTION_KEY = 'tb_db_version';

    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix . 'tb_';

        $sql_tours = "CREATE TABLE {$prefix}tours (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NULL,
            duration_minutes INT NOT NULL DEFAULT 60,
            cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            min_participants INT NOT NULL DEFAULT 1,
            max_participants INT NOT NULL DEFAULT 1,
            buffer_before INT NOT NULL DEFAULT 0,
            buffer_after INT NOT NULL DEFAULT 0,
            capacity_min INT NOT NULL DEFAULT 1,
            capacity_max INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_guides = "CREATE TABLE {$prefix}guides (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NULL,
            phone VARCHAR(100) NULL,
            bio LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        $sql_clients = "CREATE TABLE {$prefix}clients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(100) NULL,
            notes LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        $sql_tour_guides = "CREATE TABLE {$prefix}tour_guides (
            tour_id BIGINT UNSIGNED NOT NULL,
            guide_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (tour_id, guide_id),
            KEY guide_id (guide_id)
        ) $charset_collate;";

        dbDelta( $sql_tours );
        dbDelta( $sql_guides );
        dbDelta( $sql_clients );
        dbDelta( $sql_tour_guides );

        update_option( self::OPTION_KEY, self::VERSION );
    }
}