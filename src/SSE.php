<?php
namespace Heave\PrixChat;

class SSE
{
    private $broadcast_service;

    public function __construct()
    {
        $this->broadcast_service = new BroadcastService();

        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_rest_routes()
    {
        register_rest_route('prix-chat/v1', '/sse', [
            'methods' => 'GET',
            'callback' => [$this, 'sse_callback'],
        ]);
    }

    public function sse_callback(\WP_Rest_Request $request)
    {
        $this->broadcast_service->request = $request;
        $this->broadcast_service->start();
    }
}