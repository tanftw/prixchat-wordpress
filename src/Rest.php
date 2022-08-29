<?php

namespace Heave\PrixChat;

class Rest
{
    private $chat_service;

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

        register_rest_route('prix-chat/v1', '/conversations', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_conversation'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_conversation'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('prix-chat/v1', '/conversations/(?P<conversation_id>\d+)/peers', [
            'methods' => 'POST',
            'callback' => [$this, 'add_peers'],
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

        register_rest_route('prix-chat/v1', '/messages', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_message'],
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

        if (isset($params['url'])) {
            $params = $this->get_search_params($params['url']);
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

        $message = $this->chat_service->create_message($data);

        return new \WP_REST_Response($message, 200);
    }

    public function get_messages($request)
    {
        $messages = Message::get([
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
                'message' => __('Message id is required', 'prix-chat'),
            ], 400);
        }

        $message = Message::find([
            'id' => $data['message_id'],
        ]);

        if (!$message) {
            return new \WP_REST_Response([
                'message' => __('Message not found', 'prix-chat'),
            ], 404);
        }

        $peer = Peer::get([
            'user_id' => get_current_user_id(),
            'conversation_id' => $message->conversation_id,
        ]);

        $peer = reset($peer);

        $reaction = trim($data['reaction']);

        $reactions = json_decode($message->reactions, true);

        if (!isset($reactions[$reaction])) {
            $reactions[$reaction] = [];
        }

        $reactions[$reaction][] = [
            'peer_id' => $peer->id,
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
                'message' => __('Conversation not found', 'prix-chat'),
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

    public function delete_message($request)
    {
        global $wpdb;

        $data = $request->get_params();

        if (!isset($data['id'])) {
            return new \WP_REST_Response([
                'message' => __('Message id is required', 'prix-chat'),
            ], 400);
        }

        $delete_for_every_one = $data['delete_for_everyone'] ?? false;

        $user_id = get_current_user_id();

        $message = Message::find([
            'id' => $data['id'],
        ]);

        if (!$message) {
            return new \WP_REST_Response([
                'message' => __('Message not found', 'prix-chat'),
            ], 404);
        }

        if ($message->user_id != $user_id) {
            return new \WP_REST_Response([
                'message' => __('You can not delete this message', 'prix-chat'),
            ], 403);
        }

        $where = [
            'id' => $data['id'],
        ];

        if ($delete_for_every_one == true) {
            $wpdb->delete($wpdb->prefix . 'prix_chat_messages', $where);
        } else {
            $wpdb->update($wpdb->prefix . 'prix_chat_messages', [
                'deleted_at' => wp_date('Y-m-d H:i:s'),
                'deleted_for' => $message->peer_id,
            ], $where);
        }

        return new \WP_REST_Response([
            'status' => 'ok',
        ], 200);
    }

    /**
     * Instead of delete from database, we just let peer leave the conversation
     */
    public function delete_conversation($request)
    {
        global $wpdb;
        $data = $request->get_params();
        $user_id = get_current_user_id();


        $peer = Peer::get([
            'user_id' => $user_id,
            'conversation_id' => $data['id'],
        ]);

        if (!$peer) {
            return new \WP_REST_Response([
                'message' => __('You are not a member of this conversation', 'prix-chat'),
            ], 403);
        }

        $peer = reset($peer);

        $wpdb->update($wpdb->prefix . 'prix_chat_peers', [
            'deleted_at' => wp_date('Y-m-d H:i:s'),
        ], [
            'id' => $peer->id,
        ]);

        return new \WP_REST_Response([
            'status' => 'ok',
        ], 200);
    }

    public function create_conversation($request)
    {
        $data = $request->get_params();
        $files = $request->get_file_params();

        if (!isset($data['title'])) {
            return new \WP_REST_Response([
                'message' => __('Title is required', 'prix-chat'),
            ], 400);
        }

        $conversation = [
            'type' => 'group',
            'title' => $data['title'],
        ];

        if (!empty($files) && !empty($files['avatar']['tmp_name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $mimes = array(
                'bmp'  => 'image/bmp',
                'gif'  => 'image/gif',
                'jpe'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg'  => 'image/jpeg',
                'png'  => 'image/png',
                'tif'  => 'image/tiff',
                'tiff' => 'image/tiff'
            );

            $overrides = array(
                'mimes'     => $mimes,
                'test_form' => false
            );

            $uploaded = wp_handle_upload($files['avatar'], $overrides);
            // Uploaded format
            // {file: File Path, url: URL, type: "image/jpeg"}
            $conversation['avatar'] = $uploaded['url'];
        }

        $conversation = Conversation::create($conversation);

        // Add current user to the conversation as the first user
        Peer::create([
            'conversation_id' => $conversation['id'],
            'user_id' => get_current_user_id(),
        ]);

        return new \WP_REST_Response($conversation, 201);
    }

    public function add_peers($request)
    {
        global $wpdb;

        $conversation_id = $request->get_param('conversation_id');
        $users = $request->get_param('users');

        if (!$conversation_id) {
            return new \WP_REST_Response([
                'message' => __('Conversation id is required', 'prix-chat'),
            ], 400);
        }

        if (!$users) {
            return new \WP_REST_Response([
                'message' => __('Users is required', 'prix-chat'),
            ], 400);
        }

        $peers = array_map(function ($user) use ($conversation_id) {
            return [
                'user_id'   => $user['id'],
                'name'      => $user['name'],
                'email'     => $user['email'],
                'conversation_id' => $conversation_id,
                'is_typing' => false,
                'avatar' => $user['avatar'],
            ];
        }, $users);

        $query = "INSERT INTO {$wpdb->prefix}prix_chat_peers (user_id, name, email, conversation_id, is_typing, avatar) VALUES ";
        $prepare = [];

        foreach ($peers as $peer) {
            $query .= ' (%d, %s, %s, %d, %s, %s),';
            $prepare[] = $peer['user_id'];
            $prepare[] = $peer['name'];
            $prepare[] = $peer['email'];
            $prepare[] = $peer['conversation_id'];
            $prepare[] = $peer['is_typing'];
            $prepare[] = $peer['avatar'];
        }

        $query = rtrim($query, ',');

        $rows_affected = $wpdb->query($wpdb->prepare($query, $prepare));

        return new \WP_REST_Response([
            'status' => 'ok',
            'rows_affected' => $rows_affected,
        ], 200);
    }
}
