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
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/conversations', [
            'methods' => 'POST',
            'callback' => [$this, 'create_conversation'],
            'permission_callback' => '__return_true',
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