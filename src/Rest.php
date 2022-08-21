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

    /**
     * Register routes for the plugin.
     * 
     * @todo: Check permissions for each route.
     */
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

        register_rest_route('prix-chat/v1', '/reactions', [
            'methods' => 'POST',
            'callback' => [$this, 'toggle_reaction'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/typing', [
            'methods' => 'POST',
            'callback' => [$this, 'set_typing'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_conversations($request)
    {
        $conversations = $this->chat_service->get_conversations();

        return new \WP_REST_Response($conversations, 200);
    }

    private function get_search_params($url)
    {
        if ($url[0] === 'g') {
            return [
                'hash' => $url
            ];
        }

        if ($url[0] === '@') {
            $hash_id = intval(substr($url, 1));

            $current_user_id = get_current_user_id();
            $target_id = $hash_id;

            $hash = "{$current_user_id}-{$target_id}";

            if ($current_user_id > $target_id) {
                $hash = "{$target_id}-{$current_user_id}";
            }

            return compact('hash');
        }

        return [
            'id' => $url
        ];
    }

    public function get_conversation($request)
    {
        $params = $request->get_params();

        if (isset($params['hash'])) {
            $params = $this->get_search_params($params['hash']);
        }

        $find_params = array_merge($params, [
            'withs' => ['peers']
        ]);

        $conversation = Conversation::find($find_params);

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

    public function toggle_reaction($request)
    {
        global $wpdb;

        $data = $request->get_params();

        if (!isset($data['message_id'])) {
            return new \WP_REST_Response([
                'message' => 'Message id is required',
            ], 400);
        }

        $message = Message::find([
            'id' => $data['message_id'],
        ]);

        if (!$message) {
            return new \WP_REST_Response([
                'message' => 'Message not found',
            ], 404);
        }

        $reaction = trim($data['reaction']);

        $reactions = json_decode($message->reactions, true);

        if (!isset($reactions[$reaction])) {
            $reactions[$reaction] = [];
        }

        $reactions[$reaction][] = [
            'peer_id' => get_current_user_id(),
            'reacted_at' => date('Y-m-d H:i:s'),
        ];

        $reactions = json_encode($reactions);

        $wpdb->update($wpdb->prefix . 'prix_chat_messages', [
            'reactions' => $reactions,
        ], [
            'id' => $message->id,
        ]);

        return new \WP_REST_Response([
            'status' => 'ok',
        ], 200);
    }

    public function set_typing($request)
    {
        global $wpdb;

        $data = $request->get_params();

        $conversation = Conversation::find([
            'id' => $data['conversation_id'],
        ]);

        if (!$conversation) {
            return new \WP_REST_Response([
                'message' => 'Conversation not found',
            ], 404);
        }

        $typing = $data['typing'];

        Peer::update([
            'is_typing' => (bool) $typing,
        ], [
            'conversation_id' => $conversation->id,
            'user_id' => get_current_user_id(),
        ]);

        return new \WP_REST_Response([
            'status' => 'ok',
        ], 200);
    }
}
