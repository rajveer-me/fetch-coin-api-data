<?php
/*
Plugin Name: Fetches data from API Crypto Widget
Description: Fetches data from CoinCap API and displays it in a widget, updated every 5 minutes.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enqueue styles and scripts if needed
function crypto_widget_enqueue_assets() {
    wp_enqueue_style('crypto-widget-style', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'crypto_widget_enqueue_assets');


// Enqueue block editor assets
function crypto_widget_block_assets() {
    wp_enqueue_script(
        'crypto-widget-block',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        filemtime(plugin_dir_path(__FILE__) . 'block.js')
    );
}
add_action('enqueue_block_editor_assets', 'crypto_widget_block_assets');

// Register block type
function crypto_register_widget_block_type() {
    register_block_type('crypto/widget-block', array(
        'render_callback' => 'crypto_widget_display', // Use the same display function as the widget
    ));
}
add_action('init', 'crypto_register_widget_block_type');



// Shortcode function to display the widget content
function crypto_widget_shortcode() {
    ob_start(); // Start output buffering
    crypto_widget_display(); // Use the same function that renders the widget
    return ob_get_clean(); // Return the buffered output
}

// Register the shortcode
add_shortcode('crypto_widget', 'crypto_widget_shortcode');


// Function to fetch data from API
function crypto_widget_fetch_data() {
    $response = wp_remote_get('https://api.coincap.io/v2/assets');

    if (is_wp_error($response)) {
        return false; // Handle error
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['data'])) {
        set_transient('crypto_widget_data', $data['data'], 5 * MINUTE_IN_SECONDS);
        return $data['data'];
    }

    return false;
}

// Shortcode or widget display function
function crypto_widget_display() {
    // Try to retrieve cached data
    $data = get_transient('crypto_widget_data');

    if (!$data) {
        $data = crypto_widget_fetch_data(); // Fetch if not cached
    }

    if ($data) {
        // Example: Display the top 5 cryptocurrencies
        echo '<div class="crypto-widget">';
        echo '<h3>Top 5 Cryptocurrencies</h3>';
        echo '<ul>';
        foreach (array_slice($data, 0, 5) as $crypto) {
            echo '<li>' . esc_html($crypto['name']) . ' (' . esc_html($crypto['symbol']) . '): $' . esc_html(number_format($crypto['priceUsd'], 2)) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<p>Unable to fetch data.</p>';
    }
}

// Register widget
class Crypto_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'crypto_widget',
            esc_html__('Crypto Widget', 'text_domain'),
            array('description' => esc_html__('Displays top cryptocurrencies', 'text_domain'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        crypto_widget_display();
        echo $args['after_widget'];
    }
}

function register_crypto_widget() {
    register_widget('Crypto_Widget');
}
add_action('widgets_init', 'register_crypto_widget');

// Schedule data fetching every 5 minutes
if (!wp_next_scheduled('crypto_widget_update_event')) {
    wp_schedule_event(time(), '5minutes', 'crypto_widget_update_event');
}

add_action('crypto_widget_update_event', 'crypto_widget_fetch_data');

// Custom interval for cron job (5 minutes)
function crypto_widget_cron_intervals($schedules) {
    $schedules['5minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display' => __('Every 5 Minutes')
    );
    return $schedules;
}
add_filter('cron_schedules', 'crypto_widget_cron_intervals');
