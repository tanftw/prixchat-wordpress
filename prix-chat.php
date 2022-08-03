<?php
/*
Plugin Name: Prix Chat
Plugin URI: https://prixchat.com/
Description: Simple, efficient chat for startups.
Version: 0.0.1
Author: Heave
Author URI: https://heave.app/
License: GPLv2 or later
Text Domain: heave
*/

// Prevent loading this file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '7.0.0', '<' ) ) {
	die( __( 'I need at least PHP 7.0 to run properly!', 'heave' ) );
}

require_once __DIR__ . '/vendor/autoload.php';

define('PRIX_CHAT_DIR', __DIR__);
define('PRIX_CHAT_URL', plugins_url('', __FILE__));

add_action( 'wp_enqueue_scripts', 'load_scripts' );
function load_scripts() {
    wp_enqueue_script( 'wp-react-kickoff', PRIX_CHAT_URL . '/components/build/static/js/main.8312e861.js', ['wp-element' ], wp_rand(), true );
    wp_localize_script( 'wp-react-kickoff', 'prix', [
        'apiUrl' => home_url( '/wp-json' ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ] );
}

add_action('wp_footer', function () {
	echo '<div id="root"></div>';
});

if (is_admin()) {
    new Heave\PrixChat\Migration;
}

new Heave\PrixChat\Rest;
new Heave\PrixChat\SSE;
