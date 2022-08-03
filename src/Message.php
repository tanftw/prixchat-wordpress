<?php
namespace Heave\PrixChat;

class Message
{
    private function create($data)
    {
        global $wpdb;

        $required = [
            'conversation_id',
            'sender_id',
            'message',
        ];

        foreach ($required as $key) {
            if (!isset($data[$key])) {
                return false;
            }
        }

        return $wpdb->insert($wpdb->prefix . 'prix_chat_messages', $data);
    }

    public function get($conversation_id, $sender_id, $limit = 10)
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d AND sender_id = %d ORDER BY created_at DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($query, $conversation_id, $sender_id, $limit));
    }
}

