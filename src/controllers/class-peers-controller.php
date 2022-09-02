<?php

namespace PrixChat\Controllers;

use PrixChat\ChatService;

class Peers_Controller extends Base_Controller
{
    public function get_items($request)
    {
        // Get peers
    }

    public function create_items($request)
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
