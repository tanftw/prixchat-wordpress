<?php
/*
Plugin Name: Prix Chat
Plugin URI: https://prixchat.com/
Description: Simple, efficient chat for startups.
Version: 0.0.1
Author: Heave
Author URI: https://heave.app/
License: GPLv2 or later
Text Domain: prix-chat
Domain Path: /languages
*/

// Prevent loading this file directly
if (!defined('ABSPATH')) {
    wp_die(__('Please do not load this file directly. Thanks!', 'prix-chat'));
}

// This plugin require PHP > 7.0
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    wp_die(__('I need at least PHP 7.0 to run properly!', 'prix-chat'));
}

require_once __DIR__ . '/vendor/autoload.php';

define('PRIX_CHAT_DIR', __DIR__);
define('PRIX_CHAT_URL', plugins_url('', __FILE__));

if (is_admin()) {
    new Heave\PrixChat\Migration;
    new Heave\PrixChat\Admin;
    new Heave\PrixChat\CacheService;
}

new Heave\PrixChat\Rest;
new Heave\PrixChat\SSE;

// Make sure we leave nothing after uninstalled
register_activation_hook(__FILE__, function () {
    register_uninstall_hook(__FILE__, function () {
        \Heave\PrixChat\Migration::down();
    });
});

// Clean up after deactivation
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('prix_chat_clear_cache');
});
