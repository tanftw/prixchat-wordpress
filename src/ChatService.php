<?php

namespace Heave\PrixChat;

class ChatService
{
    public function create_message($data)
    {
        $message = $data['message'];
        $message['content'] = esc_html(trim($message['content']));

        if (!$message['conversation_id']) {
            $message['conversation_id'] = $this->create_conversation($data['url']);
        }

        if ($message['reply_to']) {
            $message['reply_to_id']             = $message['reply_to']['id'];
            $message['reply_to']['content']     = esc_html(trim($message['reply_to']['content']));
            $message['reply_to'] = json_encode($message['reply_to']);
        }

        // Set typing to false for the current user
        Peer::update([
            'is_typing' => false,
        ], [
            'conversation_id'   => $message['conversation_id'],
            'user_id'           => get_current_user_id(),
        ]);

        $message = Message::create($message);

        return $message['id'];
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
            'conversation_id'   => $conversation['id'],
            'is_typing'         => false,
            'last_seen'         => wp_date('Y-m-d H:i:s'),
        ]);

        $to = Peer::create([
            'user_id'           => $to_id,
            'conversation_id'   => $conversation['id'],
            'is_typing'         => false,
        ]);

        return $conversation['id'];
    }

    public function get_conversations($args = [])
    {
        global $wpdb;

        $me = wp_get_current_user();
        $conversations = [];
        $exclude = [];

        $current_user_conversations_ids = Peer::get_conversation_ids();

        // Convert $current_user_conversations_ids to a comma separated string
        $current_user_conversations_ids = implode(',', $current_user_conversations_ids);

        if (!empty($current_user_conversations_ids)) {
            // Get all conversations with last message
            $conversations = $wpdb->get_results(
                $wpdb->prepare("SELECT 
                C.id, 
                meta, 
                peers, 
                status,
                hash,
                content, 
                peer_id, 
                title, 
                C.type as type, 
                C.avatar as avatar,
                M.created_at as last_message_at,
                M.content as last_message_content,
                M.peer_id as last_message_peer_id,
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
            ORDER BY last_message_at DESC", $current_user_conversations_ids)
            );

            // Get Peer data for each conversation
            $peers = Peer::get([
                'in_conversation_id' => $current_user_conversations_ids,
            ]);

            // Format $peers, key by conversation_id
            $peers_by_conversation_id = [];

            foreach ($peers as $peer) {
                if (!isset($peers_by_conversation_id[$peer->conversation_id])) {
                    $peers_by_conversation_id[$peer->conversation_id] = [];
                }

                $peers_by_conversation_id[$peer->conversation_id][] = $peer;
            }

            $unread_count = Peer::get_unread_count($me->ID);

            // Format conversations for display in the chat
            foreach ($conversations as $id => $conversation) {
                $peers = $peers_by_conversation_id[$conversation->id];

                $last_message_sender = [];

                $recipient = [];

                if (is_array($peers) && count($peers) > 0) {
                    foreach ($peers as $peer) {
                        if ($peer->id == $conversation->last_message_peer_id) {
                            $last_message_sender = $peer;
                        }

                        if ($peer->user_id != $me->ID) {
                            $recipient = $peer;
                        }

                        if ($conversation->type == 'dm') {
                            $exclude[] = $peer->user_id;
                        }
                    }

                    if (empty($recipient)) {
                        $recipient = $peer;
                    }
                }

                $conversation->messages = [
                    [
                        'type'          => $conversation->last_message_type,
                        'content'       => substr($conversation->last_message_content, 0, 25),
                        'peer_id'       => $conversation->last_message_peer_id,
                        'sender'        => $last_message_sender,
                        'created_at'    => $conversation->last_message_at,
                    ],
                ];

                $conversation->url          = $conversation->id;
                $conversation->peers        = $peers;
                $conversation->meta         = json_decode($conversation->meta);
                $conversation->recipient    = $recipient;
                $conversation->unread_count = $unread_count[$conversation->id] ?? 0;
                $conversation->avatar       = $conversation->avatar ?? $recipient->avatar;
                $conversation->title        = $conversation->title ?? $recipient->name;

                // Limit the size of $conversation->title
                $conversation->title        = substr($conversation->title, 0, 20);
                $conversations[$id]         = $conversation;
            }
        }

        if (($key = array_search($me->ID, $exclude)) !== false) {
            unset($exclude[$key]);
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
                'url'       => '@' . $user['id'],
                'type'      => 'dm',
                'messages'  => [],
                'title'     => $user['name'],
                'avatar'    => $user['avatar'],
                'recipient' => $user,
            ];
        }

        return $conversations;
    }
}
