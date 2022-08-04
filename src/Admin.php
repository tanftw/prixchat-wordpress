<?php
namespace Heave\PrixChat;

class Admin
{
    public function __construct()
    {
        // Register admin page
        add_action('admin_menu', [$this, 'add_admin_page']);

        // Register admin page scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_admin_page()
    {
        add_menu_page(
            'Prix Chat',
            'Prix Chat',
            'manage_options',
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

        wp_localize_script('prix-chat-admin', 'prix', [
            'apiUrl' => home_url( '/wp-json' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
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