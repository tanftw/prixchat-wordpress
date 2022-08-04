<?php
namespace Heave\PrixChat;

class ChatService
{
    public function create_message($data)
    {
        global $wpdb;
        
        $data['content'] = htmlspecialchars($data['content']);

        // Create new conversation if it doesn't exist
        if (!isset($data['conversation_id']) || !is_numeric($data['conversation_id']) || $data['conversation_id'] == 0) {
            $data['conversation_id'] = $this->create_conversation($data);
        }

        $data = [
            'type' => 'text',
            'conversation_id' => $data['conversation_id'],
            'sender_id'         => get_current_user_id(),
            'content'           => $data['content'],
            'created_at'        => current_time('mysql'),
        ];

        $wpdb->insert($wpdb->prefix . 'prix_chat_messages', $data);

        return $wpdb->insert_id;
    }

    public function create_conversation($data)
    {
        global $wpdb;

        $data = [
            'peer_id' => $data['peer_id'],
            'sender_id' => $data['sender_id'],
            'created_at' => current_time('mysql'),
        ];
        
        return $wpdb->insert($wpdb->prefix . 'prix_chat_conversations', $data);
    }

    public function get_conversations($args = [])
    {
        global $wpdb;

        // Get all conversations with last message
        $conversations = $wpdb->get_results($wpdb->prepare(
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
                `M`.`conversation_id` = C.id", 
            $args['after'] ?? 0
        ));

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