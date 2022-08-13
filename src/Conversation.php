<?php
namespace Heave\PrixChat;

class Conversation
{
    private static function normalize($conversation, $withs = [])
    {
        if (in_array('peers', $withs)) {
            $conversation->peers = Peer::get([
                'conversation_id' => $conversation->id,
            ]);
        }

        return $conversation;
    }

    public static function find($conversation_id, $withs = [])
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_conversations WHERE id = %d";
        $conversation = $wpdb->get_row($wpdb->prepare($query, $conversation_id));

        return self::normalize($conversation, $withs);
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
}