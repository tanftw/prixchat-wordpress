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
}