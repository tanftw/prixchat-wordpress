<?php

namespace Heave\PrixChat;

class Peer
{
    private static function normalize($peer)
    {
        return $peer;
    }

    public static function find($peer_id, $withs = [])
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_peers WHERE id = %d";
        $peer = $wpdb->get_row($wpdb->prepare($query, $peer_id));

        return self::normalize($peer);
    }

    public static function get($args = [])
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->prefix}prix_chat_peers WHERE 1 = 1";
        $prepare = [];

        if (isset($args['conversation_id'])) {
            $query .= " AND conversation_id = %d";
            $prepare[] = $args['conversation_id'];
        }
        
        if (isset($args['user_id'])) {
            $query .= " AND user_id = %d";
            $prepare[] = $args['user_id'];
        }

        $query = $wpdb->get_results($wpdb->prepare($query, $prepare), OBJECT_K);

        return array_map(function ($peer) {
            return self::normalize($peer);
        }, $query);
    }

    public static function get_conversation_ids()
    {
        global $wpdb;

        $query = "SELECT DISTINCT conversation_id FROM {$wpdb->prefix}prix_chat_peers WHERE user_id = %d";
        $conversation_ids = $wpdb->get_col($wpdb->prepare($query, get_current_user_id()));

        return $conversation_ids;
    }

    /**
     * Add a peer to a conversation.
     * 
     */
    public static function create($data)
    {
        global $wpdb;

        $user = get_user_by('id', $data['user_id']);

        $data = [
            'conversation_id' => $data['conversation_id'],
            'user_id'         => $data['user_id'],
            'name'            => $user->display_name,
            'avatar'          => get_avatar_url($data['user_id']),
            'email'           => $user->user_email,
            'created_at'      => current_time('mysql'),
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_peers', $data);

        return array_merge($data, [
            'id' => $wpdb->insert_id,
        ]);
    }

    public static function update($data)
    {
        global $wpdb;

        if (!isset($data['id'])) {
            return false;
        }

        $wpdb->update($wpdb->prefix . 'prix_chat_peers', $data, compact('id'));
    }
}
