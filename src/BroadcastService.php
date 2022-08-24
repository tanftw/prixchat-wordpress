<?php

namespace Heave\PrixChat;

class BroadcastService
{
    protected $after;

    public $request;

    public $chat_service;

    protected $conversations;

    public function __construct()
    {
        $this->chat_service = new ChatService();
    }

    public function send()
    {
        $params = $this->request->get_params();
        $conversation_id = $params['conversation_id'];

        if (!is_numeric($conversation_id)) {
            exit;
        }

        $response = [];

        $peers = Peer::get([
            'conversation_id' => $conversation_id,
        ]);

        if ($peers) {
            $response['peers'] = $peers;
        }

        $messages = Message::get([
            'conversation_id' => $this->request->get_param('conversation_id'),
        ]);

        // Add seens to messages
        if ($messages) {
            foreach ($messages as $index => $message) {
                foreach ($peers as $id => $peer) {
                    if ($peer->last_seen >= $message->created_at) {
                        if (empty($message->seens)) {
                            $message->seens = [];
                        }

                        $message->seens[] = $peer;
                        unset($peers[$id]);
                    }
                }

                $messages[$index] = $message;
            }

            $messages = array_reverse($messages);

            $response['messages'] = $messages;
        }

        // Set last seen and fetch conversations every 5 seconds
        $second = date('s');
        if ($second % 5 === 0) {
            $conversations = $this->chat_service->get_conversations();

            if ($conversations) {
                $response['conversations'] = $conversations;
            }

            Peer::set_last_seen($conversation_id);
        }

        if (!empty($response)) {
            $json = json_encode($response);

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
