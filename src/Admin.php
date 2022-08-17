<?php

namespace Heave\PrixChat;

class Admin
{
    private $chat_service;

    public function __construct()
    {
        $this->chat_service = new ChatService();

        // Register admin page
        add_action('admin_menu', [$this, 'add_admin_page']);

        // Register admin page scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts'], 999);
    }

    public function add_admin_page()
    {
        add_menu_page(
            'Prix Chat',
            'Prix Chat',
            'edit_posts',
            'prix-chat',
            [$this, 'render_admin_page'],
            'dashicons-format-chat',
            3
        );
    }

    public function enqueue_admin_scripts()
    {
        wp_enqueue_style('prix-chat-admin', PRIX_CHAT_URL . '/react-ui/dist/index.css');
        wp_enqueue_script('prix-chat-admin', PRIX_CHAT_URL . '/react-ui/dist/index.js', [], wp_rand(), true);

        // Retrieve all users and pass them to scripts
        $conversations = $this->chat_service->get_conversations();
        
        $current_user = wp_get_current_user();

        $me = [
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'avatar' => get_avatar_url($current_user->ID),
        ];

        wp_localize_script('prix-chat-admin', 'prix', [
            'apiUrl'        => home_url('/wp-json/prix-chat/v1/'),
            'nonce'         => wp_create_nonce('wp_rest'),
            'conversations' => $conversations,
            'me'            => $me,
        ]);
    }

    public function render_admin_page()
    {
?>
        <div class="wrap">
            <div id="root"></div>
        </div>
<?php
    }
}
