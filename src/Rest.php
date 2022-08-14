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
            'callback' => [$this, 'get_conversation'],
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

    private function urlToHash($url)
    {
        if ($url[0] === 'g') {
            return $url;
        }

        if ($url[0] === '@') {
            $hash_id = intval(substr($url, 1));
        }

        $current_user_id = get_current_user_id();
        $target_id = $hash_id;

        $hash = "{$current_user_id}-{$target_id}";

        if ($current_user_id > $target_id) {
            $hash = "{$target_id}-{$current_user_id}";
        }

        return $hash;
    }

    public function get_conversation($request)
    {
        $params = $request->get_params();

        if (isset($params['hash'])) {
            $hash = $this->urlToHash($params['hash']);
            $params['hash'] = $hash;
        }

        $conversation = Conversation::find($params);

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