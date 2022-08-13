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

        $sql = "SELECT * FROM {$wpdb->prefix}prix_chat_peers WHERE conversation_id = %d";

        $query = $wpdb->get_results($wpdb->prepare($sql, $args['conversation_id']), OBJECT_K);

        return array_map(function ($peer) {
            return self::normalize($peer);
        }, $query);
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
}
