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

    public function send()
    {
        $params = $this->request->get_params();
        $conversation_id = $params['conversation_id'];

        if (!is_numeric($conversation_id)) {
            exit;
        }

        $messages = $this->chat_service->get_messages([
            'after' => $this->after,
            'conversation_id' => $this->request->get_param('conversation_id'),
        ]);

        $conversations = $this->chat_service->get_conversations();

        // Set last seen every 3 seconds
        $second = date('s');
        if ($second % 3 === 0) {
            Conversation::set_last_seen($conversation_id);
        }

        if (count($messages) > 0) {
            $this->after = $messages[0]->id;
            $messages = array_reverse($messages);
            $json = json_encode(compact([
                'messages',
                'conversations',
            ]));
            
            echo "data: {$json}\n\n";
          
        } else {
            echo "data: ping\n\n";
        }

        ob_flush();
        flush();
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

            // Stop broadcasting SSE if the client is not connected
            if (connection_status() !== CONNECTION_NORMAL) {
                exit;
            }
        }
    }
}
