<?php

namespace Heave\PrixChat;

/**
 * Setup and migrate data each time db has update
 */
class Migration
{
    // Current database version
    public static $db_version = '0.2.4';

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'check_migrate']);
    }

    public function check_migrate()
    {
        $installed_version = get_option('prix_chat_db_version');

        if ($installed_version != self::$db_version) {
            self::up();
        }
    }

    /**
     * Create or change table structure when database has updated
     *
     * @return void
     */
    public static function up()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // We'll create 3 tables
        $prix_chat_conversations  = $wpdb->prefix . 'prix_chat_conversations';
        $prix_chat_peers  = $wpdb->prefix . 'prix_chat_peers';
        $prix_chat_messages  = $wpdb->prefix . 'prix_chat_messages';

        $increments      = 'BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT';
        $nullableInteger = 'BIGINT(20) UNSIGNED DEFAULT NULL';
        $dateTimeColumns = 
        "created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NULL DEFAULT NULL,
            deleted_at timestamp NULL DEFAULT NULL";
        $primaryKeyId = 'PRIMARY KEY  (id)';

        $create_prix_chat_conversations_table = "CREATE TABLE {$prix_chat_conversations} (
            id {$increments},
            type varchar(10) NOT NULL,
            hash varchar(32) NOT NULL,
            title VARCHAR(255) DEFAULT NULL,
            meta JSON DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            peers JSON DEFAULT NULL,
            status VARCHAR(10) DEFAULT NULL,
            {$dateTimeColumns},
            {$primaryKeyId},
            UNIQUE KEY hash (hash)
        ) $charset_collate;";

        $create_prix_chat_peers_table = "CREATE TABLE {$prix_chat_peers} (
            id {$increments},
            user_id {$nullableInteger},
            name varchar(60) DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            email VARCHAR(60) DEFAULT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            meta JSON DEFAULT NULL,
            conversation_id {$nullableInteger},
            last_seen TIMESTAMP NULL DEFAULT NULL,
            last_seen_on INT(11) DEFAULT NULL,
            is_typing TINYINT(1) DEFAULT 0,
            status VARCHAR(10) DEFAULT NULL,
            role VARCHAR(10) DEFAULT NULL,
            {$dateTimeColumns},
            {$primaryKeyId}
        ) $charset_collate;";

        $create_prix_chat_messages_table = "CREATE TABLE {$prix_chat_messages} (
            id {$increments},
            type VARCHAR(10) DEFAULT NULL,
            content TEXT DEFAULT NULL,
            history TEXT DEFAULT NULL,
            conversation_id {$nullableInteger},
            peer_id {$nullableInteger},
            user_id {$nullableInteger},
            parent_id {$nullableInteger},
            reactions JSON DEFAULT NULL,
            reply_to_id {$nullableInteger},
            reply_to JSON DEFAULT NULL,
            deleted_for VARCHAR(5) DEFAULT NULL,
            {$dateTimeColumns},
            {$primaryKeyId}
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta($create_prix_chat_conversations_table);
        dbDelta($create_prix_chat_peers_table);
        dbDelta($create_prix_chat_messages_table);

        update_option('prix_chat_db_version', self::$db_version);
    }

    /**
     * Remove plugin settings on uninstall
     *
     * @return void
     */
    public static function down()
    {
        global $wpdb;

        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'prix_chat_conversations');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'prix_chat_messages');
        $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'prix_chat_peers');

        delete_option('prix_chat_db_version');
        delete_option('prix_chat_settings');
    }
}

new Migration;
