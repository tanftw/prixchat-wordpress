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
if (!defined('ABSPATH')) {
    exit;
}

if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die(__('I need at least PHP 7.0 to run properly!', 'heave'));
}

require_once __DIR__ . '/vendor/autoload.php';

define('PRIX_CHAT_DIR', __DIR__);
define('PRIX_CHAT_URL', plugins_url('', __FILE__));

if (is_admin()) {
    new Heave\PrixChat\Migration;
    new Heave\PrixChat\Admin;
}

new Heave\PrixChat\Rest;
new Heave\PrixChat\SSE;
