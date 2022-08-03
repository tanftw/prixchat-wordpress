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

    public function get_messages($args = [])
    {
        global $wpdb;

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}prix_chat_messages WHERE conversation_id = %d AND id > %d ORDER BY id ASC",
            $this->request->get_param('id', 0),
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
