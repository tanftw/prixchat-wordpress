<?php

namespace Heave\PrixChat;

class Message
{
    public static function create($data)
    {
        global $wpdb;

        $peers = Peer::get([
            'user_id' => get_current_user_id(),
            'conversation_id' => $data['conversation_id'],
        ]);

        if (!$peers) {
            return [];
        }

        $peer = reset($peers);

        $data = [
            'type'            => $data['type'],
            'conversation_id' => $data['conversation_id'],
            'peer_id'         => $peer->id,
            'user_id'         => get_current_user_id(),
            'content'         => $data['content'],
            'created_at'      => current_time('mysql'),
            'reply_to'        => $data['reply_to'],
            'reply_to_id'     => $data['reply_to_id'],
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

    public static function get($args = [])
    {
        global $wpdb;

        if (!isset($args['conversation_id'])) {
            return [];
        }

        // Get conversation
        $conversation = Conversation::find([
            'id' => $args['conversation_id'],
            'withs' => ['peers']
        ]);

        $sqlStr = "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d";

        if (isset($args['after'])) {
            $sqlStr .= " AND id > %d";
        }

        if (isset($args['before'])) {
            $sqlStr .= " AND id < %d";
        }

        $sqlStr .= " ORDER BY id DESC LIMIT 20";

        $beforeAfter = $args['before'] ?? $args['after'] ?? 0;

        $query = $wpdb->prepare(
            $sqlStr,
            $args['conversation_id'],
            $beforeAfter
        );

        $messages = $wpdb->get_results($query);

        // Format messages for display in the chat
        $messages = array_map(function ($message) use ($conversation) {
            return self::normalize($message, $conversation);
        }, $messages);

        return $messages;
    }

    public static function normalize($message, $conversation)
    {
        $peers = $conversation->peers;

        $message->conversation = $conversation;
        $message->content = nl2br($message->content);
        $message->reactions = $message->reactions ? json_decode($message->reactions, true) : [];

        if (!empty($message->reactions)) {
            $message->reactions = array_map(function ($reaction) use ($peers) {
                return array_map(function ($peer) use ($peers) {
                    $peer['peer'] = $peers[$peer['peer_id']] ?? [];

                    return $peer;
                }, $reaction);

                return $reaction;
            }, $message->reactions);
        }

        if ($message->reply_to) {
            $message->reply_to = json_decode($message->reply_to);
        }

        $message->sender = $peers[$message->peer_id] ?? [];

        // $message->created_at = date('Y-m-d H:i:s', strtotime($message->created_at));

        return $message;
    }
}
