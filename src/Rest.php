<?php
namespace Heave\PrixChat;

class Rest
{
    private $chat_service;
    private $broadcast_service;

    public function __construct()
    {
        $this->chat_service = new ChatService();
        $this->broadcast_service = new BroadcastService();

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('prix-chat/v1', '/conversations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conversations'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations', [
            'methods' => 'POST',
            'callback' => [$this, 'create_conversation'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conversation'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_messages'],
        ]);

        register_rest_route('prix-chat/v1', '/messages', [
            'methods' => 'POST',
            'callback' => [$this, 'create_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/delete', [
            'methods' => 'POST',
            'callback' => [$this, 'delete_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/edit', [
            'methods' => 'POST',
            'callback' => [$this, 'edit_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/read', [
            'methods' => 'POST',
            'callback' => [$this, 'read_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/unread', [
            'methods' => 'POST',
            'callback' => [$this, 'unread_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/star', [
            'methods' => 'POST',
            'callback' => [$this, 'star_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/unstar', [
            'methods' => 'POST',
            'callback' => [$this, 'unstar_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/pin', [
            'methods' => 'POST',
            'callback' => [$this, 'pin_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/unpin', [
            'methods' => 'POST',
            'callback' => [$this, 'unpin_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/trash', [
            'methods' => 'POST',
            'callback' => [$this, 'trash_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/untrash', [
            'methods' => 'POST',
            'callback' => [$this, 'untrash_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/messages/(?P<message_id>\d+)/delete', [
            'methods' => 'POST',
            'callback' => [$this, 'delete_message'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/peers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_peers'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/peers', [
            'methods' => 'POST',
            'callback' => [$this, 'add_peer'],
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/peers/(?P<peer_id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'remove_peer'],
        ]);
    }

    public function get_conversations($request)
    {
        $conversations = $this->chat_service->get_conversations();

        return new \WP_REST_Response($conversations, 200);
    }

    public function create_message($request)
    {
        $message = $request->get_params();
        
        $id = $this->chat_service->create_message($message);

        return new \WP_REST_Response([
            'id' => $id,
        ], 200);
    }
}