<?php

if ( ! defined( 'WPINC' ) ) {
    die;
}

class YFS_Activator {

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'yoyaku_user_follows';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            term_taxonomy_id BIGINT(20) UNSIGNED NOT NULL,
            follow_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_term_unique (user_id, term_taxonomy_id),
            KEY idx_user_id (user_id),
            KEY idx_term_taxonomy_id (term_taxonomy_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( class_exists( 'ActionScheduler' ) && function_exists( 'as_next_scheduled_action' ) && false === as_next_scheduled_action( 'action_scheduler/actions_gc' ) ) {
            if ( function_exists( 'as_schedule_recurring_action' ) ) {
                as_schedule_recurring_action( strtotime( 'midnight tomorrow' ), DAY_IN_SECONDS, 'action_scheduler/actions_gc' );
            }
        }
    }
}