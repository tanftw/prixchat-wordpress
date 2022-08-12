<?php
namespace Heave\PrixChat;

class Peer
{
    public static function create($data)
    {
        global $wpdb;
    }

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
}