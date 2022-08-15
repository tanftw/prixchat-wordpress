<?php
namespace Heave\PrixChat;

class Conversation
{
    private static function normalize($conversation, $withs = [])
    {
        if (!empty($conversation->peers)) {
            $conversation->peers = json_decode($conversation->peers, true);
        }

        $conversation->meta = json_decode($conversation->meta, true);

        return $conversation;
    }

    public static function find($args) {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_conversations WHERE 1 = 1";

        $prepare = [];

        if (isset($args['id'])) {
            $query .= " AND id = %d";
            $prepare[] = $args['id'];
        }
        
        if (isset($args['hash'])) {
            $query .= " AND hash = %s";
            $prepare[] = $args['hash'];
        }

        $conversation = $wpdb->get_row($wpdb->prepare($query, $prepare));

        if (!$conversation) {
            return [];
        }

        return self::normalize($conversation, $args['withs'] ?? []);
    }

    public static function create($data)
    {
        global $wpdb;

        $data = array_merge($data, [
            'created_at' => current_time('mysql'),
        ]);

        $wpdb->insert($wpdb->prefix . 'prix_chat_conversations', $data);

        return array_merge($data, [
            'id' => $wpdb->insert_id,
        ]);
    }

    public static function update($data)
    {
        global $wpdb;
        
        return $wpdb->update($wpdb->prefix . 'prix_chat_conversations', $data, [
            'id' => $data['id'],
        ]);
    }
}