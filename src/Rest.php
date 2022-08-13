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

        register_rest_route('prix-chat/v1', '/conversation', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conversation_by_hash'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/conversations', [
            'methods' => 'POST',
            'callback' => [$this, 'create_conversation'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conversation'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/messages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_messages'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/messages', [
            'methods' => 'POST',
            'callback' => [$this, 'create_message'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_conversations($request)
    {
        $conversations = $this->chat_service->get_conversations();

        return new \WP_REST_Response($conversations, 200);
    }

    public function get_conversation_by_hash($request)
    {
        $conversation = $this->chat_service->get_conversation_by_hash($request->get_param('hash'));

        return new \WP_REST_Response($conversation, 200);
    }

    public function create_message($request)
    {
        $data = $request->get_params();
        
        $id = $this->chat_service->create_message($data);

        return new \WP_REST_Response([
            'id' => $id,
        ], 200);
    }

    public function get_messages($request)
    {
        $messages = $this->chat_service->get_messages([
            'before' => $request->get_param('before'),
            'conversation_id' => $request->get_param('conversation_id'),
        ]);

        $messages = array_reverse($messages);

        return new \WP_REST_Response($messages, 200);
    }
}