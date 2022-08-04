<?php

namespace Heave\PrixChat;

class ChatService
{

    private function get_conversation_id_from_hash($hash)
    {
        global $wpdb;

        $hash_id = substr($hash, 1);

        if ($hash[0] === 'g') {
            return intval($hash_id);
        }

        $current_user_id = get_current_user_id();
        $target_id = $hash_id;

        $hash = "{$current_user_id}-{$target_id}";

        if ($current_user_id > $target_id) {
            $hash = "{$target_id}-{$current_user_id}";
        }

        $conversationId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}prix_chat_conversations WHERE peer_pair = %s",
                $hash
            )
        );

        return $conversationId;
    }

    public function create_message($data)
    {
        global $wpdb;

        $message = $data;
        $message['content'] = htmlspecialchars($message['content']);

        $conversationId = $this->get_conversation_id_from_hash($message['conversation_id']);

        if (!$conversationId) {
            $conversationId = $this->create_conversation($data);
        }

        $message = [
            'type' => 'text',
            'conversation_id' => $conversationId,
            'sender_id'         => get_current_user_id(),
            'content'           => $message['content'],
            'created_at'        => current_time('mysql'),
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_messages', $message);

        return $wpdb->insert_id;
    }

    public function create_conversation($data)
    {
        global $wpdb;

        $my_id = get_current_user_id();
        if ($data['conversation_id'][0] === '@') {
            $target_user_id = substr($data['conversation_id'], 1);
        }

        $peer_pair = "{$my_id}-{$target_user_id}";
        if ($my_id > $target_user_id) {
            $peer_pair = "{$target_user_id}-{$my_id}";
        }

        $data = [
            'type'          => 'dm',
            'created_at'    => current_time('mysql'),
            'peer_pair'     => $peer_pair,
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_conversations', $data);

        return $wpdb->insert_id;
    }

    public function get_conversations($args = [])
    {
        global $wpdb;

        // Get all conversations with last message
        $conversations = $wpdb->get_results(
            "SELECT 
                C.id, 
                meta, 
                peers, 
                status, 
                content, 
                sender_id, 
                title, 
                C.type as type, 
                C.avatar as avatar,
                M.created_at as last_message_at,
                M.content as last_message_content,
                M.sender_id as last_message_sender_id,
                M.type as last_message_type
            FROM 
                `wp_prix_chat_conversations` C, 
                `wp_prix_chat_messages` M 
            WHERE 
                M.id IN(
                    SELECT 
                        MAX(id) as last_id 
                    FROM 
                        `wp_prix_chat_messages` G 
                    GROUP BY conversation_id
                ) 
            AND 
                `M`.`conversation_id` = C.id
            AND C.type = 'group'"
        );

        // Users as empty conversations
        $users = get_users();
        $users = array_map(function ($user) {
            return [
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ];
        }, $users);

        // Format conversations for display in the chat
        $conversations = array_map(function ($conversation) {
            return $this->normalize_conversation($conversation);
        }, $conversations);

        $me = wp_get_current_user();

        // Add users as empty conversations
        foreach ($users as $user) {
            $conversations[] = [
                'id' => '@' . $user['id'],
                'type'  => 'dm',
                'messages' => [],
                'peers' => [
                    $user,
                    $me
                ],
                'title' => $user['name'],
                'avatar' => $user['avatar'],
            ];
        }

        return $conversations;
    }

    public function normalize_conversation($conversation)
    {
        $conversation->id = 'g' . $conversation->id;

        $conversation->messages = [
            [
                'type' => $conversation->last_message_type,
                'content' => $conversation->last_message_content,
                'sender_id' => $conversation->last_message_sender_id,
                'created_at' => $conversation->last_message_at,
            ],
        ];

        $conversation->peers = json_decode($conversation->peers);
        $conversation->meta = json_decode($conversation->meta);

        return $conversation;
    }
}
