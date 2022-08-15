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
        $message = $data['message'];
        $message['content'] = htmlspecialchars($message['content']);

        if (!$message['conversation_id']) {
            $message['conversation_id'] = $this->create_conversation($data['hash']);
        }

        $message = Message::create($message);

        return $message['id'];
    }

    public function get_messages($args = [])
    {
        global $wpdb;

        if (!isset($args['conversation_id'])) {
            return [];
        }

        // Get conversation
        $conversation = Conversation::find([
            'id' => $args['conversation_id']
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
        $messages = array_map(function ($message) use ($conversation) {
            return $this->format_message($message, $conversation);
        }, $messages);

        return $messages;
    }

    public function format_message($message, $conversation)
    {
        $message->conversation = $conversation;
        $message->content = nl2br($message->content);
        $message->reactions = [];

        $peers = $conversation->peers;

        foreach ($peers as $peer) {
            if ($peer->user_id == $message->sender_id) {
                $message->sender = $peer;
            }
        }
        // $message->created_at = date('Y-m-d H:i:s', strtotime($message->created_at));

        return $message;
    }

    public function create_conversation($hash)
    {
        $my_id = get_current_user_id();

        if ($hash[0] === '@') {
            $to_id = substr($hash, 1);
        }

        $hash = "{$my_id}-{$to_id}";
        if ($my_id > $to_id) {
            $hash = "{$to_id}-{$my_id}";
        }

        $data = [
            'type'          => 'dm',
            'hash'          => $hash,
        ];

        $conversation = Conversation::create($data);

        // Add peers to relationship table
        $me = Peer::create([
            'user_id'           => $my_id,
            'conversation_id'   => $conversation['id']
        ]);

        $to = Peer::create([
            'user_id'           => $to_id,
            'conversation_id'   => $conversation['id']
        ]);

        // Cache peers for future use
        $conversation['peers'] = json_encode([$me, $to]);
        Conversation::update($conversation);

        return $conversation['id'];
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
                `M`.`conversation_id` = C.id"
        );

        $me = wp_get_current_user();
        $exclude = [];

        // Format conversations for display in the chat
        foreach ($conversations as $id => $conversation) {
            $conversation->id = 'g' . $conversation->id;

            $peers = json_decode($conversation->peers);

            $sender = [];

            if (is_array($peers) && count($peers) > 0) {
                foreach ($peers as $peer) {
                    if ($peer->id != $conversation->sender_id) {
                        $sender = $peer;
                    }

                    if ($peer->user_id !== $me->ID) {
                        $conversation->title = $peer->name;

                        if ($conversation->type === 'dm') {
                            $conversation->id = '@' . $peer->user_id;
                            $exclude[] = $peer->user_id;
                        }
                    }
                }
            }

            if (!$conversation->avatar && $sender->avatar) {
                $conversation->avatar = $sender->avatar;
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

            $conversations[$id] = $conversation;
        }

        // Users as empty conversations
        $users = get_users([
            'exclude' => $exclude,
        ]);

        $users = array_map(function ($user) {
            return [
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ];
        }, $users);


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
}
