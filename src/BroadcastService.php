<?php

namespace Heave\PrixChat;

class BroadcastService
{
    protected $after;

    protected $queue = [];

    public $request;

    public $chat_service;

    public function __construct()
    {
        $this->chat_service = new ChatService();
    }

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

    public function send()
    {
        $messages = $this->chat_service->get_messages([
            'after' => $this->after,
            'conversation_id' => $this->request->get_param('conversation_id'),
        ]);

        if (count($messages) > 0) {
            $this->after = $messages[0]->id;
            $messages = array_reverse($messages);
            $json = json_encode($messages);
            echo "data: {$json}\n\n";
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

        while (!connection_aborted()) {
            // Send data to client
            $this->send();

            // Wait 1 second for the next message / event
            sleep(1);
        }
    }
}
