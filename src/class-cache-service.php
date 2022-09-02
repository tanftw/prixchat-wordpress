<?php
namespace PrixChat;

class CacheService
{
    public function __construct()
    {
        // Register wp cronjob for clearing cache every day
        add_action('prix_chat_clear_cache', [$this, 'clear_cache']);
        
        if (!wp_next_scheduled('prix_chat_clear_cache')) {
            wp_schedule_event(time(), 'daily', 'prix_chat_clear_cache');
        }
    }
    
    /**
     * We use mass update to update cache to avoid performance issues
     * 
     * @return void
     */
    public function clear_cache()
    {
        global $wpdb;
        
        $users = get_users();

        $user_peers = [];
        $when_then = [];
        $prepare = [];

        foreach ($users as $user) {
            $peer = [
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ];

            $user_peers[$user->ID] = $peer;
            $when_then[] = "WHEN user_id = %d THEN %s ";
            $prepare[] = $peer['user_id'];
            $prepare[] = $peer['avatar'];
        }

        $case = '(CASE ' . implode(' ', $when_then) . ' END)';
        $sql = "UPDATE {$wpdb->prefix}prix_chat_peers SET avatar = $case";
        
        $wpdb->query($wpdb->prepare($sql, $prepare));
    }
}