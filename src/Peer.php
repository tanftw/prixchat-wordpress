<?php

namespace Heave\PrixChat;

class Peer
{
    private static function normalize($peer)
    {
        if (!empty($peer->meta)) {
            $peer->meta = json_decode($peer->meta, true);
        }

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

        if (isset($args['in_conversation_id'])) {
            $query .= " AND conversation_id IN (%1s)";
            $prepare[] = $args['in_conversation_id'];
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

        if (isset($data['user_id'])) {
            $user_id = intval($data['user_id']);
            $user = get_user_by('id', $user_id);
            
            if (!$user) {
                return new \WP_Error('invalid_user_id', 'Invalid user ID');
            }

            $data['name'] = $user->display_name;
            $data['avatar'] = get_avatar_url($user_id);
        }

        $data['created_at'] = wp_date('Y-m-d H:i:s');

        $wpdb->insert($wpdb->prefix . 'prix_chat_peers', $data);

        return array_merge($data, [
            'id' => $wpdb->insert_id,
        ]);
    }

    public static function update($data)
    {
        global $wpdb;

        $wpdb->update($wpdb->prefix . 'prix_chat_peers', $data[0], $data[1]);
    }

    public static function get_unread_count($user_id)
    {
        global $wpdb;

        $query = "SELECT 
                        P.conversation_id, 
                        count(*) as total_unread 
                 FROM 
                    `wp_prix_chat_messages` M, 
                    `wp_prix_chat_peers` P 
                WHERE P.user_id = %d 
                AND M.conversation_id = P.conversation_id 
                AND P.last_seen < M.created_at 
                GROUP BY P.conversation_id";

        $unread_count = $wpdb->get_results($wpdb->prepare($query, $user_id), OBJECT_K);

        return $unread_count;
    }

    public static function set_last_seen($conversation_id)
    {
        global $wpdb;

        $user_id = get_current_user_id();

        $now = wp_date('Y-m-d H:i:s');

        $wpdb->update($wpdb->prefix . 'prix_chat_peers', [
            'last_seen' => $now,
        ], [
            'conversation_id' => $conversation_id,
            'user_id' => $user_id,
        ]);

        // Update online status of user
        // update_user_meta(get_current_user_id(), 'last_seen', $now);
    }
}
