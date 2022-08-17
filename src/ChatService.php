<?php

namespace Heave\PrixChat;

class ChatService
{
    public function create_message($data)
    {
        $message = $data['message'];
        $message['content'] = esc_html(trim($message['content']));

        if (!$message['conversation_id']) {
            $message['conversation_id'] = $this->create_conversation($data['hash']);
        }

        if ($message['reply_to']) {
            $message['reply_to_id'] = $message['reply_to']['id'];
            $message['reply_to']['content'] = esc_html(trim($message['reply_to']['content']));
            $message['reply_to'] = json_encode($message['reply_to']);
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
        $peers = $conversation->peers;

        $message->conversation = $conversation;
        $message->content = nl2br($message->content);
        $message->reactions = $message->reactions ? json_decode($message->reactions, true) : [];

        if (!empty($message->reactions)) {
            $message->reactions = array_map(function ($reaction) use ($peers) {
                return array_map(function ($peer) use ($peers) {
                    $peer['peer'] = $peers[$peer['peer_id']];

                    return $peer;
                }, $reaction);

                return $reaction;
            }, $message->reactions);
        }

        if ($message->reply_to) {
            $message->reply_to = json_decode($message->reply_to);
        }

        $message->sender = $peers[$message->sender_id] ?? [];
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
        $conversation['peers'] = json_encode([
            $my_id => $me,
            $to_id => $to
        ]);

        // If we don't have avatar, use $to's avatar
        if (!$conversation['avatar']) {
            $conversation['avatar'] = $to['avatar'];
        }

        if (!$conversation['title']) {
            $conversation['title'] = $to['name'];
        }

        Conversation::update($conversation);

        return $conversation['id'];
    }

    public function get_conversations($args = [])
    {
        global $wpdb;

        $me = wp_get_current_user();
        $current_user_conversations_ids = Peer::get_conversation_ids();
        // Convert $current_user_conversations_ids to a comma separated string
        $current_user_conversations_ids = implode(',', $current_user_conversations_ids);

        // Get all conversations with last message
        $conversations = $wpdb->get_results(
            $wpdb->prepare("SELECT 
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
            AND C.id IN (%1s)
            ", $current_user_conversations_ids)
        );

        $exclude = [];

        // Format conversations for display in the chat
        foreach ($conversations as $id => $conversation) {
            $peers = json_decode($conversation->peers, true);

            $last_message_sender = [];

            $recipient = [];
            if (is_array($peers) && count($peers) > 0) {
                foreach ($peers as $user_id => $peer) {
                    if ($user_id == $conversation->last_message_sender_id) {
                        $last_message_sender = $peer;
                    }
                    if ($user_id != $me->ID) {
                        $recipient = $peer;
                    }

                    if ($conversation->type == 'dm') {
                        $conversation->id = '@' . $user_id;
                        $exclude[] = $user_id;
                    }
                }
            }

            if (empty($recipient)) {
                $recipient = $last_message_sender;
            }

            $conversation->messages = [
                [
                    'type'          => $conversation->last_message_type,
                    'content'       => $conversation->last_message_content,
                    'sender_id'     => $conversation->last_message_sender_id,
                    'sender'        => $last_message_sender,
                    'created_at'    => $conversation->last_message_at,
                ],
            ];

            $conversation->peers = $peers;
            $conversation->meta = json_decode($conversation->meta);
            $conversation->recipient = $recipient;
            $conversations[$id] = $conversation;
        }

        // Users as empty conversations
        $users = get_users([
            'exclude' => $exclude,
        ]);

        foreach ($users as $index => $user) {
            $users[$index] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ];
        }

        // Add users as empty conversations
        foreach ($users as $user) {
            $conversations[] = [
                'id' => '@' . $user['id'],
                'type'  => 'dm',
                'messages' => [],
                'title' => $user['name'],
                'avatar' => $user['avatar'],
            ];
        }

        return $conversations;
    }
}
