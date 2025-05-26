<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add meta boxes for Incidents
 */
function irm_incident_meta_boxes() {
    add_meta_box(
        'irm_incident_details',
        __( 'Incident Details', 'incident-response-manager' ),
        'irm_render_incident_meta_box',
        'incident',
        'normal',
        'high'
    );
    add_meta_box(
        'irm_incident_log',
        __( 'Incident Log', 'incident-response-manager' ),
        'irm_render_incident_log_meta_box',
        'incident',
        'normal', // Changed from 'side' to 'normal' to give more space
        'default'
    );
}
add_action( 'add_meta_boxes', 'irm_incident_meta_boxes' );

/**
 * Render meta box for Incident Log
 */
function irm_render_incident_log_meta_box( $post ) {
    ?>
    <div id="incident-log-display">
        <h4><?php _e( 'Activity Log:', 'incident-response-manager' ); ?></h4>
        <ul>
            <?php
            $comments = get_comments( array(
                'post_id' => $post->ID,
                'status'  => 'approve', // Or 'hold', 'spam', 'trash' if needed
                'orderby' => 'comment_date_gmt',
                'order'   => 'DESC' // Show newest first
            ) );

            if ( $comments ) {
                foreach ( $comments as $comment ) {
                    printf(
                        '<li><strong>%s:</strong> %s <br><small>%s at %s</small></li>',
                        esc_html( $comment->comment_author ),
                        wp_kses_post( $comment->comment_content ), // Using wp_kses_post for safety
                        esc_html( date_i18n( get_option( 'date_format' ), strtotime( $comment->comment_date ) ) ),
                        esc_html( date_i18n( get_option( 'time_format' ), strtotime( $comment->comment_date ) ) )
                    );
                }
            } else {
                echo '<li>' . __( 'No log entries yet.', 'incident-response-manager' ) . '</li>';
            }
            ?>
        </ul>
    </div>
    <hr>
    <h4><?php _e( 'Add New Log Entry:', 'incident-response-manager' ); ?></h4>
    <textarea id="irm_new_log_entry" name="irm_new_log_entry" class="widefat" rows="3" placeholder="<?php _e( 'Enter log update here...', 'incident-response-manager' ); ?>"></textarea>
    <p>
        <button type="button" id="irm_add_log_entry_button" class="button">
            <?php _e( 'Add Log Entry', 'incident-response-manager' ); ?>
        </button>
        <?php wp_nonce_field( 'irm_add_incident_log_nonce_action', 'irm_add_incident_log_nonce_field' ); ?>
    </p>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#irm_add_log_entry_button').on('click', function() {
                var logEntry = $('#irm_new_log_entry').val();
                var postId = <?php echo $post->ID; ?>;
                var nonce = $('#irm_add_incident_log_nonce_field').val();

                if (logEntry.trim() === '') {
                    alert('<?php _e( "Log entry cannot be empty.", "incident-response-manager" ); ?>');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'irm_add_incident_log_entry',
                        post_id: postId,
                        log_entry: logEntry,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var newLog = '<li><strong>' + response.data.author + ':</strong> ' + response.data.content + ' <br><small>' + response.data.date + ' at ' + response.data.time + '</small></li>';
                            $('#incident-log-display ul').prepend(newLog); // Add to top for newest first
                            $('#irm_new_log_entry').val(''); // Clear textarea
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('AJAX Error: ' + textStatus + ' - ' + errorThrown);
                    }
                });
            });
        });
    </script>
    <?php
}

/**
 * Render meta box for Incidents
 */
function irm_render_incident_meta_box( $post ) {
    // Add nonce for security
    wp_nonce_field( 'irm_save_incident_meta_data', 'irm_incident_meta_nonce' );

    // Get existing values
    $incident_type = get_post_meta( $post->ID, '_incident_type', true );
    $location = get_post_meta( $post->ID, '_location', true );
    $datetime = get_post_meta( $post->ID, '_datetime', true );
    $responding_units = get_post_meta( $post->ID, '_responding_units', true );
    // $status = get_post_meta( $post->ID, '_status', true ); // Status is now handled by taxonomy
    $assigned_personnel_ids = get_post_meta( $post->ID, '_assigned_personnel_ids', true );
    if ( ! is_array( $assigned_personnel_ids ) ) {
        $assigned_personnel_ids = array();
    }
    $assigned_vehicle_ids = get_post_meta( $post->ID, '_assigned_vehicle_ids', true );
    if ( ! is_array( $assigned_vehicle_ids ) ) {
        $assigned_vehicle_ids = array();
    }

    ?>
    <p>
        <label for="incident_type"><?php _e( 'Incident Type:', 'incident-response-manager' ); ?></label>
        <input type="text" id="incident_type" name="incident_type" value="<?php echo esc_attr( $incident_type ); ?>" class="widefat">
    </p>
    <p>
        <label for="location"><?php _e( 'Location:', 'incident-response-manager' ); ?></label>
        <input type="text" id="location" name="location" value="<?php echo esc_attr( $location ); ?>" class="widefat">
    </p>
    <p>
        <label for="datetime"><?php _e( 'Date/Time:', 'incident-response-manager' ); ?></label>
        <input type="datetime-local" id="datetime" name="datetime" value="<?php echo esc_attr( $datetime ); ?>" class="widefat">
    </p>
    <p>
        <label for="responding_units"><?php _e( 'Responding Units (Details):', 'incident-response-manager' ); ?></label>
        <textarea id="responding_units" name="responding_units" class="widefat"><?php echo esc_textarea( $responding_units ); ?></textarea>
    </p>
    
    <?php
    // Personnel Selector
    $all_personnel = get_posts( array( 'post_type' => 'personnel', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    if ( ! empty( $all_personnel ) ) {
        ?>
        <p>
            <label for="assigned_personnel_ids"><?php _e( 'Assign Personnel:', 'incident-response-manager' ); ?></label>
            <select id="assigned_personnel_ids" name="assigned_personnel_ids[]" multiple class="widefat" style="min-height: 100px;">
                <?php foreach ( $all_personnel as $person ) : ?>
                    <option value="<?php echo esc_attr( $person->ID ); ?>" <?php selected( in_array( $person->ID, $assigned_personnel_ids ), true ); ?>>
                        <?php echo esc_html( $person->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    } else {
        echo '<p>' . __( 'No personnel available to assign.', 'incident-response-manager' ) . '</p>';
    }

    // Vehicle Selector
    $all_vehicles = get_posts( array( 'post_type' => 'vehicle', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    if ( ! empty( $all_vehicles ) ) {
        ?>
        <p>
            <label for="assigned_vehicle_ids"><?php _e( 'Assign Vehicles:', 'incident-response-manager' ); ?></label>
            <select id="assigned_vehicle_ids" name="assigned_vehicle_ids[]" multiple class="widefat" style="min-height: 100px;">
                <?php foreach ( $all_vehicles as $vehicle ) : ?>
                    <option value="<?php echo esc_attr( $vehicle->ID ); ?>" <?php selected( in_array( $vehicle->ID, $assigned_vehicle_ids ), true ); ?>>
                        <?php echo esc_html( $vehicle->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    } else {
        echo '<p>' . __( 'No vehicles available to assign.', 'incident-response-manager' ) . '</p>';
    }
    ?>
    <?php
}

/**
 * AJAX handler for adding a new log entry (comment)
 */
add_action( 'wp_ajax_irm_add_incident_log_entry', 'irm_handle_add_incident_log_entry' );

function irm_handle_add_incident_log_entry() {
    check_ajax_referer( 'irm_add_incident_log_nonce_action', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $log_entry = isset( $_POST['log_entry'] ) ? wp_kses_post( trim( $_POST['log_entry'] ) ) : ''; // Sanitize content

    if ( ! $post_id || empty( $log_entry ) ) {
        wp_send_json_error( __( 'Missing data.', 'incident-response-manager' ) );
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( __( 'You do not have permission to add logs to this incident.', 'incident-response-manager' ) );
        return;
    }

    $current_user = wp_get_current_user();
    $time = current_time('mysql');

    $commentdata = array(
        'comment_post_ID'      => $post_id,
        'comment_author'       => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_author_url'   => $current_user->user_url,
        'comment_content'      => $log_entry,
        'comment_type'         => 'incident_log', // Custom comment type if needed for differentiation
        'comment_parent'       => 0,
        'user_id'              => $current_user->ID,
        'comment_date'         => $time,
        'comment_approved'     => 1,
    );

    $comment_id = wp_insert_comment( $commentdata );

    if ( is_wp_error( $comment_id ) ) {
        wp_send_json_error( $comment_id->get_error_message() );
    } else {
        wp_send_json_success( array(
            'author'  => esc_html( $current_user->display_name ),
            'content' => wp_kses_post( $log_entry ),
            'date'    => esc_html( date_i18n( get_option( 'date_format' ), strtotime( $time ) ) ),
            'time'    => esc_html( date_i18n( get_option( 'time_format' ), strtotime( $time ) ) ),
        ) );
    }
}


/**
 * Save meta box data for Incidents
 */
function irm_save_incident_meta_data( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['irm_incident_meta_nonce'] ) || ! wp_verify_nonce( $_POST['irm_incident_meta_nonce'], 'irm_save_incident_meta_data' ) ) {
        return;
    }

    // Check if current user can edit post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save fields
    if ( isset( $_POST['incident_type'] ) ) {
        update_post_meta( $post_id, '_incident_type', sanitize_text_field( $_POST['incident_type'] ) );
    }
    if ( isset( $_POST['location'] ) ) {
        update_post_meta( $post_id, '_location', sanitize_text_field( $_POST['location'] ) );
    }
    if ( isset( $_POST['datetime'] ) ) {
        update_post_meta( $post_id, '_datetime', sanitize_text_field( $_POST['datetime'] ) );
    }
    if ( isset( $_POST['responding_units'] ) ) {
        update_post_meta( $post_id, '_responding_units', sanitize_textarea_field( $_POST['responding_units'] ) );
    }
    // Status is now handled by taxonomy, so we remove the old status meta saving.
    // if ( isset( $_POST['status'] ) ) {
    //     update_post_meta( $post_id, '_status', sanitize_text_field( $_POST['status'] ) );
    // }

    // Save Assigned Personnel
    if ( isset( $_POST['assigned_personnel_ids'] ) ) {
        $personnel_ids = array_map( 'intval', $_POST['assigned_personnel_ids'] );
        update_post_meta( $post_id, '_assigned_personnel_ids', $personnel_ids );
    } else {
        delete_post_meta( $post_id, '_assigned_personnel_ids' ); // Clear if nothing is selected
    }

    // Save Assigned Vehicles
    if ( isset( $_POST['assigned_vehicle_ids'] ) ) {
        $vehicle_ids = array_map( 'intval', $_POST['assigned_vehicle_ids'] );
        update_post_meta( $post_id, '_assigned_vehicle_ids', $vehicle_ids );
    } else {
        delete_post_meta( $post_id, '_assigned_vehicle_ids' ); // Clear if nothing is selected
    }
}
add_action( 'save_post_incident', 'irm_save_incident_meta_data' );

?>
