<?php
namespace Heave\PrixChat;

class CacheService
{
    public function __construct()
    {
        // Update cache daily
        // add_action('admin_init', [$this, 'update_peers_cache']);
    }
    
    /**
     * We use mass update to update cache to avoid performance issues
     * 
     * @return void
     */
    public function update_peers_cache()
    {
        global $wpdb;
        
        $users = get_users();

        $user_peers = [];
        $when_then = [];
        $when_then_conversations = [];
        
        foreach ($users as $user) {
            $peer = [
                'user_id' => $user->ID,
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ];

            $user_peers[$user->ID] = $peer;
            $when_then[] = $wpdb->prepare("WHEN user_id = %d THEN %s ", $peer['user_id'], $peer['avatar']);
        }

        $case = '(CASE ' . implode(' ', $when_then) . ' END)';
        $sql = "UPDATE {$wpdb->prefix}prix_chat_peers SET avatar = $case";

        $conversations = $wpdb->get_results("SELECT * FROM wp_prix_chat_conversations");

        foreach ($conversations as $conversation) {
            $peers = json_decode($conversation->peers, true);

            $peers = array_map(function ($peer) use ($user_peers) {
                if (isset($user_peers[$peer['user_id']])) {
                    return array_merge($peer, $user_peers[$peer['user_id']]);
                }

                return $peer;
            }, $peers);

            $when_then_conversations[] = $wpdb->prepare("WHEN id = %d THEN %s ", $conversation->id, json_encode($peers));
        }
        $case_conversation = '(CASE ' . implode(' ', $when_then_conversations) . ' END)';
        $sql_conversation = "UPDATE {$wpdb->prefix}prix_chat_conversations SET peers = $case_conversation";

        $wpdb->query($sql);
        $wpdb->query($sql_conversation);
    }
}