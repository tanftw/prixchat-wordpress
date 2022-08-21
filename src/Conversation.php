<?php

namespace Heave\PrixChat;

class Conversation
{
    private static function normalize($conversation, $args)
    {
        $my_id = get_current_user_id();

        if (isset($args['withs']) && is_array($args['withs']) && in_array('peers', $args['withs'])) {
            $peers = Peer::get([
                'conversation_id' => $conversation->id,
            ]);

            $conversation->peers = $peers;
            
            // @todo: Check recipient if it's a DM conversation
            $recipient = [];
            foreach ($conversation->peers as $id => $peer) {
                if ($peer->id !== $my_id) {
                    $recipient = $peer;
                }
            }

            if (empty($recipient)) {
                $recipient = $peer;
            }

            $conversation->recipient = $recipient;
        }

        $conversation->meta = json_decode($conversation->meta, true);
        
        return $conversation;
    }

    public static function find($args)
    {
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

        return self::normalize($conversation, $args);
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

    public static function get($args = [])
    {
        global $wpdb;

        $prepare  = [];
        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_conversations WHERE 1 = 1";

        if (isset($args['in'])) {
            $query .= " AND id IN (%1s)";
            $prepare[] = $args['in'];
        }

        $conversations = $wpdb->get_results($wpdb->prepare($query, $prepare));
        
        return array_map(function ($conversation) {
            return self::normalize($conversation, []);
        }, $conversations);
    }
}
