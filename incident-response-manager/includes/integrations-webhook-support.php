<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// --- Webhook Settings (Simplified: using wp_options) ---

define('IRM_WEBHOOK_URL_OPTION_NAME', 'irm_webhook_url_new_incident');

// Function to get the webhook URL (example - in a real scenario, this would be part of a settings page)
function irm_get_new_incident_webhook_url() {
    return get_option(IRM_WEBHOOK_URL_OPTION_NAME, '');
}

// Function to set the webhook URL (example - for testing or programmatic setup)
// function irm_set_new_incident_webhook_url($url) {
//     update_option(IRM_WEBHOOK_URL_OPTION_NAME, esc_url_raw($url));
// }

// --- Trigger Webhook on New Incident Creation ---

/**
 * Fires when a post is saved.
 *
 * @param int     $post_ID Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post being updated or not.
 */
function irm_trigger_new_incident_webhook($post_ID, $post, $update) {
    // Check if this is a new incident being published for the first time
    if ($post->post_type === 'incident' && !$update && $post->post_status === 'publish') {
        $webhook_url = irm_get_new_incident_webhook_url();

        if (empty($webhook_url)) {
            return; // No webhook URL configured
        }

        // Prepare data - using a dummy WP_REST_Request to leverage irm_prepare_item_for_response
        // In a real scenario, you might construct a WP_REST_Request object if needed by your formatter,
        // or manually build the payload.
        // For simplicity, we'll manually build a basic payload or use a simplified version.
        
        // Attempt to get full data using a dummy request for irm_prepare_item_for_response
        // This is a bit of a hack for using the existing function outside a real REST context.
        $dummy_request = new WP_REST_Request('GET', ''); // Method and route don't matter much here
        $dummy_request->set_namespace('irm/v1'); // Set namespace if your prepare function uses it for links
        
        $response = irm_prepare_item_for_response($post, $dummy_request);
        $payload = is_wp_error($response) ? null : $response->get_data();

        if (!$payload) {
            // Fallback to simpler payload if irm_prepare_item_for_response failed or is too complex here
            $payload = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'status' => $post->post_status,
                'link' => get_permalink($post->ID),
                'message' => 'New incident created: ' . $post->post_title
            );
        }
        
        $args = array(
            'body'        => json_encode($payload),
            'headers'     => array('Content-Type' => 'application/json'),
            'timeout'     => 15, // seconds
            'redirection' => 5,
            'blocking'    => false, // Set to true if you need to wait for response, false for fire-and-forget
            'sslverify'   => apply_filters('irm_webhook_sslverify', true), // Allow filtering SSL verification
        );

        // Use wp_remote_post to send the webhook
        $response = wp_remote_post($webhook_url, $args);

        if (is_wp_error($response)) {
            // Log error (e.g., using error_log or a custom logging solution)
            error_log('IRM Webhook Error for New Incident: ' . $response->get_error_message());
        } else {
            // Optionally log success or response code
            // $response_code = wp_remote_retrieve_response_code($response);
            // error_log('IRM Webhook Success for New Incident: Response Code ' . $response_code);
        }
    }
}
// Hook into save_post, but ensure it's for new posts and correct type.
// save_post runs after the data is saved.
add_action('save_post_incident', 'irm_trigger_new_incident_webhook', 10, 3);


// --- Admin UI for setting Webhook URL (Very Basic Example) ---
// This would typically be part of a proper settings page.

function irm_webhook_settings_field() {
    add_settings_field(
        'irm_webhook_url_new_incident_field',
        __('New Incident Webhook URL', 'incident-response-manager'),
        'irm_webhook_url_field_callback',
        'general', // Add to General settings page for simplicity
        'default', 
        array( 'label_for' => IRM_WEBHOOK_URL_OPTION_NAME )
    );
    register_setting('general', IRM_WEBHOOK_URL_OPTION_NAME, 'esc_url_raw');
}
// add_action('admin_init', 'irm_webhook_settings_field'); // Uncomment to add to General Settings

function irm_webhook_url_field_callback($args) {
    $option_name = $args['label_for'];
    $url = get_option($option_name, '');
    echo '<input type="url" id="' . esc_attr($option_name) . '" name="' . esc_attr($option_name) . '" value="' . esc_attr($url) . '" class="regular-text" />';
    echo '<p class="description">' . __('Enter the URL to send a webhook to when a new incident is published.', 'incident-response-manager') . '</p>';
}

// Note: To actually save the setting from the General Settings page,
// you'd need to ensure 'general' group is used in register_setting.
// This basic UI example is primarily illustrative for a full plugin.
// For this task, the core is the webhook sending mechanism.

?>
