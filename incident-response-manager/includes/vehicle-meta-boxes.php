<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add meta boxes for Vehicles
 */
function irm_vehicle_meta_boxes() {
    add_meta_box(
        'irm_vehicle_details',
        __( 'Vehicle Details', 'incident-response-manager' ),
        'irm_render_vehicle_meta_box',
        'vehicle',
        'normal',
        'high'
    );
    add_meta_box(
        'irm_vehicle_maintenance_log',
        __( 'Maintenance Log', 'incident-response-manager' ),
        'irm_render_vehicle_maintenance_log_meta_box',
        'vehicle',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'irm_vehicle_meta_boxes' );

/**
 * Render meta box for Vehicle Maintenance Log
 */
function irm_render_vehicle_maintenance_log_meta_box( $post ) {
    ?>
    <div id="vehicle-maintenance-log-display">
        <h4><?php _e( 'Maintenance Activity Log:', 'incident-response-manager' ); ?></h4>
        <ul>
            <?php
            $comments = get_comments( array(
                'post_id' => $post->ID,
                'status'  => 'approve',
                'orderby' => 'comment_date_gmt',
                'order'   => 'DESC'
            ) );

            if ( $comments ) {
                foreach ( $comments as $comment ) {
                    printf(
                        '<li><strong>%s:</strong> %s <br><small>%s at %s</small></li>',
                        esc_html( $comment->comment_author ),
                        wp_kses_post( $comment->comment_content ),
                        esc_html( date_i18n( get_option( 'date_format' ), strtotime( $comment->comment_date ) ) ),
                        esc_html( date_i18n( get_option( 'time_format' ), strtotime( $comment->comment_date ) ) )
                    );
                }
            } else {
                echo '<li>' . __( 'No maintenance log entries yet.', 'incident-response-manager' ) . '</li>';
            }
            ?>
        </ul>
    </div>
    <hr>
    <h4><?php _e( 'Add New Maintenance Log Entry:', 'incident-response-manager' ); ?></h4>
    <textarea id="irm_new_vehicle_log_entry" name="irm_new_vehicle_log_entry" class="widefat" rows="3" placeholder="<?php _e( 'Enter maintenance details here...', 'incident-response-manager' ); ?>"></textarea>
    <p>
        <button type="button" id="irm_add_vehicle_log_entry_button" class="button">
            <?php _e( 'Add Maintenance Log Entry', 'incident-response-manager' ); ?>
        </button>
        <?php wp_nonce_field( 'irm_add_vehicle_log_nonce_action', 'irm_add_vehicle_log_nonce_field' ); ?>
    </p>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#irm_add_vehicle_log_entry_button').on('click', function() {
                var logEntry = $('#irm_new_vehicle_log_entry').val();
                var postId = <?php echo $post->ID; ?>;
                var nonce = $('#irm_add_vehicle_log_nonce_field').val();

                if (logEntry.trim() === '') {
                    alert('<?php _e( "Log entry cannot be empty.", "incident-response-manager" ); ?>');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'irm_add_vehicle_maintenance_log',
                        post_id: postId,
                        log_entry: logEntry,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var newLog = '<li><strong>' + response.data.author + ':</strong> ' + response.data.content + ' <br><small>' + response.data.date + ' at ' + response.data.time + '</small></li>';
                            $('#vehicle-maintenance-log-display ul').prepend(newLog);
                            $('#irm_new_vehicle_log_entry').val('');
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
 * Render meta box for Vehicles
 */
function irm_render_vehicle_meta_box( $post ) {
    // Add nonce for security
    wp_nonce_field( 'irm_save_vehicle_meta_data', 'irm_vehicle_meta_nonce' );

    // Get existing values
    $vehicle_type = get_post_meta( $post->ID, '_vehicle_type', true );
    $call_sign = get_post_meta( $post->ID, '_call_sign', true );
    // $status = get_post_meta( $post->ID, '_status', true ); // Old status field, now handled by taxonomy
    $assigned_personnel = get_post_meta( $post->ID, '_assigned_personnel', true );
    $maintenance_schedule = get_post_meta( $post->ID, '_maintenance_schedule', true ); // This will be 'Next Scheduled Maintenance Date'
    $last_maintenance_date = get_post_meta( $post->ID, '_last_maintenance_date', true );


    ?>
    <p>
        <label for="vehicle_type"><?php _e( 'Vehicle Type:', 'incident-response-manager' ); ?></label>
        <input type="text" id="vehicle_type" name="vehicle_type" value="<?php echo esc_attr( $vehicle_type ); ?>" class="widefat">
    </p>
    <p>
        <label for="call_sign"><?php _e( 'Call Sign:', 'incident-response-manager' ); ?></label>
        <input type="text" id="call_sign" name="call_sign" value="<?php echo esc_attr( $call_sign ); ?>" class="widefat">
    </p>
    <p>
        <label for="assigned_personnel"><?php _e( 'Assigned Personnel (e.g. primary operator):', 'incident-response-manager' ); ?></label>
        <input type="text" id="assigned_personnel" name="assigned_personnel" value="<?php echo esc_attr( $assigned_personnel ); ?>" class="widefat">
    </p>
    <hr>
    <h4><?php _e('Maintenance Information', 'incident-response-manager'); ?></h4>
    <p>
        <label for="last_maintenance_date"><?php _e( 'Last Maintenance Date:', 'incident-response-manager' ); ?></label>
        <input type="date" id="last_maintenance_date" name="last_maintenance_date" value="<?php echo esc_attr( $last_maintenance_date ); ?>" class="regular-text">
    </p>
    <p>
        <label for="maintenance_schedule"><?php _e( 'Next Scheduled Maintenance Date:', 'incident-response-manager' ); ?></label>
        <input type="date" id="maintenance_schedule" name="maintenance_schedule" value="<?php echo esc_attr( $maintenance_schedule ); ?>" class="regular-text">
    </p>
    <p>
        <em><?php _e('Vehicle status (e.g., Available, In Maintenance) is managed using the "Vehicle Statuses" box (taxonomy).', 'incident-response-manager'); ?></em>
    </p>
    <?php
}

/**
 * AJAX handler for adding a new vehicle maintenance log entry
 */
add_action( 'wp_ajax_irm_add_vehicle_maintenance_log', 'irm_handle_add_vehicle_maintenance_log' );

function irm_handle_add_vehicle_maintenance_log() {
    check_ajax_referer( 'irm_add_vehicle_log_nonce_action', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $log_entry = isset( $_POST['log_entry'] ) ? wp_kses_post( trim( $_POST['log_entry'] ) ) : '';

    if ( ! $post_id || empty( $log_entry ) ) {
        wp_send_json_error( __( 'Missing data.', 'incident-response-manager' ) );
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) { // Or a more specific capability like 'add_maintenance_logs'
        wp_send_json_error( __( 'You do not have permission to add logs to this vehicle.', 'incident-response-manager' ) );
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
        'comment_type'         => 'vehicle_maintenance_log', // Custom comment type
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
 * Save meta box data for Vehicles
 */
function irm_save_vehicle_meta_data( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['irm_vehicle_meta_nonce'] ) || ! wp_verify_nonce( $_POST['irm_vehicle_meta_nonce'], 'irm_save_vehicle_meta_data' ) ) {
        return;
    }

    // Check if current user can edit post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save fields
    if ( isset( $_POST['vehicle_type'] ) ) {
        update_post_meta( $post_id, '_vehicle_type', sanitize_text_field( $_POST['vehicle_type'] ) );
    }
    if ( isset( $_POST['call_sign'] ) ) {
        update_post_meta( $post_id, '_call_sign', sanitize_text_field( $_POST['call_sign'] ) );
    }
    // Old status field removed, not saved here.
    // if ( isset( $_POST['status'] ) ) {
    //     update_post_meta( $post_id, '_status', sanitize_text_field( $_POST['status'] ) );
    // }
    if ( isset( $_POST['assigned_personnel'] ) ) {
        update_post_meta( $post_id, '_assigned_personnel', sanitize_text_field( $_POST['assigned_personnel'] ) );
    }
    if ( isset( $_POST['maintenance_schedule'] ) ) {
        update_post_meta( $post_id, '_maintenance_schedule', sanitize_text_field( $_POST['maintenance_schedule'] ) );
    }
    if ( isset( $_POST['last_maintenance_date'] ) ) {
        update_post_meta( $post_id, '_last_maintenance_date', sanitize_text_field( $_POST['last_maintenance_date'] ) );
    }
}
add_action( 'save_post_vehicle', 'irm_save_vehicle_meta_data' );

?>
