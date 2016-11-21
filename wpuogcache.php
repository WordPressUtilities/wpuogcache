<?php

/*
Plugin Name: WPU Open Graph Cache
Plugin URI: https://github.com/Darklg/WPUtilities
Description: Ensure Open Graph Cache is Fresh
Version: 0.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

if (!defined('ABSPATH')) {
    return;
}

class WPUOGCache {
    private $cache_key = 'wpuogcache_cleared';
    private $cron_key = 'wpuogcache_clearposts';

    public function __construct() {
        add_action('init', array(&$this,
            'init'
        ));
        add_action('save_post', array(&$this,
            'clearPostCache'
        ), 90, 2);
        add_action($this->cron_key, array(&$this,
            'clearPosts'
        ));
    }

    public function init() {
        if (!wp_next_scheduled($this->cron_key)) {
            wp_schedule_event(time(), 'hourly', $this->cron_key);
        }
    }

    public function clearPosts() {
        $wpq_latest_posts = new WP_Query(array(
            'posts_per_page' => 5,
            'meta_query' => array(
                array(
                    'key' => $this->cache_key,
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        while ($wpq_latest_posts->have_posts()) {
            $wpq_latest_posts->the_post();
            $this->clearCacheForUrl(get_the_ID(), get_permalink());
        }
        wp_reset_postdata();
    }

    public function clearPostCache($post_id, $post) {
        $post_status = get_post_status($post);
        if ($post_status == 'publish') {
            $this->clearCacheForUrl($post_id, get_permalink($post_id));
        }
    }

    public function clearCacheForUrl($id, $url) {
        $response = wp_remote_post('https://graph.facebook.com/', array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'blocking' => false,
            'headers' => array(),
            'body' => array(
                'id' => $url,
                'scrape' => 'true'
            )
        ));
        update_post_meta($id, $this->cache_key, '1');
    }
}

$WPUOGCache = new WPUOGCache();
