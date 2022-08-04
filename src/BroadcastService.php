<?php

namespace Heave\PrixChat;

class BroadcastService
{
    protected $last_id = 0;

    protected $queue = [];

    public $request;

    public function add($message)
    {
        $this->queue[] = $message;
    }

    private function get_conversation_id_from_hash($hash)
    {
        global $wpdb;

        $hash_id = substr($hash, 1);
      
        if ($hash[0] === 'g') {
            return intval($hash_id);
        }

        $current_user_id = get_current_user_id();
        $target_id = $hash_id;

        $peer_pair = "{$current_user_id}-{$target_id}";

        if ($current_user_id > $target_id) {
            $peer_pair = "{$target_id}-{$current_user_id}";        
        }

        $conversationId = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}prix_chat_conversations WHERE peer_pair = %s",
                $peer_pair
            )
        );

        return $conversationId;
    }

    public function get_messages($args = [])
    {
        global $wpdb;
        $hash = $this->request->get_param('id');
        
        if (!$hash) {
            return;
        }

        $id = $this->get_conversation_id_from_hash($hash);

        if (!$id) {
            return [];
        }

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d AND id > %d ORDER BY id ASC",
            $id,
            $args['after'] ?? 0
        ));

        // Format messages for display in the chat
        array_map(function ($message) {
            $message->reactions = [];
            // Replace \n with <br>
            $message->content = str_replace("\n", "<br>", $message->content);

            return $message;
        }, $messages);

        return $messages;
    }

    public function send()
    {
        $messages = $this->get_messages([
            'after' => $this->last_id,
        ]);

        if (count($messages) > 0) {
            $this->last_id = end($messages)->id;
            $json = json_encode($messages);
            echo "data: {$json}\n\n";
            ob_flush();
            flush();
        } else {
            echo "data: ping\n\n";
            ob_flush();
            flush();
        }
    }

    public function start()
    {
        // Enable cors
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Credentials: true');

        // Start broadcasting SSE
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        while (true) {

            // Send data to client
            $this->send();

            // Wait 1 second for the next message / event
            sleep(1);

            if (connection_aborted()) {
                exit;
            }
        }
    }
}
