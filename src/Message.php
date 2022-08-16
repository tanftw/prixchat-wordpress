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
            'reply_to'          => $data['reply_to'],
            'reply_to_id'       => $data['reply_to_id'],
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_messages', $data);

        return array_merge($data, [
            'id' => $wpdb->insert_id,
        ]);
    }

    public static function find($args = []) 
    {
        global $wpdb;

        if (isset($args['id'])) {
            $args['id'] = intval($args['id']);

            $sql = "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE id = %d";
            $sql = $wpdb->prepare($sql, $args['id']);
            $message = $wpdb->get_row($sql);

            return $message;
        }
    }
}

