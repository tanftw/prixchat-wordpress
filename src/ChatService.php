<?php

namespace Heave\PrixChat;

class ChatService
{
    private function get_conversation_id_from_hash($hash)
    {
        global $wpdb;

        $conversationId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}prix_chat_conversations WHERE hash = %s",
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

    public function get_messages($args = [])
    {
        global $wpdb;

        if (!isset($args['conversation_id'])) {
            return [];
        }

        // Get conversation
        $conversation = Conversation::find($args['conversation_id'], $withs = [
            'peers'
        ]);

        $sqlStr = "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d";

        if (isset($args['after'])) {
            $sqlStr .= " AND id > %d";
        }

        if (isset($args['before'])) {
            $sqlStr .= " AND id < %d";
        }

        $sqlStr .= " ORDER BY id DESC LIMIT 15";

        $beforeAfter = $args['before'] ?? $args['after'] ?? 0;

        $query = $wpdb->prepare(
            $sqlStr,
            $args['conversation_id'],
            $beforeAfter
        );

        $messages = $wpdb->get_results($query);

        // Format messages for display in the chat
        array_map(function ($message) use ($conversation) {
            return $this->format_message($message, $conversation);
        }, $messages);

        return $messages;
    }

    public function format_message($message, $conversation)
    {
        $message->conversation = $conversation;
        $message->content = nl2br($message->content);
        $message->reactions = [];
       
        $message->sender = $conversation->peers[$message->sender_id];
        // $message->created_at = date('Y-m-d H:i:s', strtotime($message->created_at));

        return $message;
    }

    public function create_conversation($data)
    {
        global $wpdb;

        $my_id = get_current_user_id();
        if ($data['conversation_id'][0] === '@') {
            $target_user_id = substr($data['conversation_id'], 1);
        }

        $hash = "{$my_id}-{$target_user_id}";
        if ($my_id > $target_user_id) {
            $hash = "{$target_user_id}-{$my_id}";
        }

        $data = [
            'type'          => 'dm',
            'created_at'    => current_time('mysql'),
            'hash'     => $hash,
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
                hash,
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

        $peers = json_decode($conversation->peers);
        
        $sender = [];
        
        if (is_array($peers) && count($peers) > 0) {
            $sender = array_filter($peers, function ($peer) use ($conversation) {
                return $peer->id === $conversation->sender_id;
            })[0];
        }

        $conversation->messages = [
            [
                'type'          => $conversation->last_message_type,
                'content'       => $conversation->last_message_content,
                'sender_id'     => $conversation->last_message_sender_id,
                'sender'        => $sender,
                'created_at'    => $conversation->last_message_at,
            ],
        ];

        $conversation->peers = $peers;
        $conversation->meta = json_decode($conversation->meta);

        return $conversation;
    }

    public function get_conversation_by_hash($hash)
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}prix_chat_conversations WHERE hash = %s",
            $hash
        );

        $conversation = $wpdb->get_row($query);
        $conversation->peers = json_decode($conversation->peers);
        $conversation->meta = json_decode($conversation->meta);

        return $conversation;
    }
}
