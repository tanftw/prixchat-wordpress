<?php
namespace Heave\PrixChat;

class Message
{
    public static function create($data)
    {
        global $wpdb;

        $data = [
            'type'              => $data['type'],
            'conversation_id'   => $data['conversation_id'],
            'sender_id'         => get_current_user_id(),
            'content'           => $data['content'],
            'created_at'        => current_time('mysql'),
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_messages', $data);

        return array_merge($data, [
            'id' => $wpdb->insert_id,
        ]);
    }

    public function get($conversation_id, $sender_id, $limit = 10)
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d AND sender_id = %d ORDER BY created_at DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($query, $conversation_id, $sender_id, $limit));
    }
}

