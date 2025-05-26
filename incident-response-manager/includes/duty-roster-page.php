<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add Duty Roster admin page.
 */
function irm_add_duty_roster_admin_page() {
    add_submenu_page(
        'edit.php?post_type=incident', // Parent slug (Incidents CPT)
        __( 'Duty Roster', 'incident-response-manager' ), // Page title
        __( 'Duty Roster', 'incident-response-manager' ), // Menu title
        'read_shifts', // Capability required - at least view shifts
        'irm_duty_roster', // Menu slug
        'irm_render_duty_roster_page' // Callback function to render the page
    );
}
add_action( 'admin_menu', 'irm_add_duty_roster_admin_page' );

/**
 * Render the Duty Roster page.
 */
function irm_render_duty_roster_page() {
    if ( ! current_user_can( 'read_shifts' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'incident-response-manager' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php _e( 'Duty Roster', 'incident-response-manager' ); ?></h1>
        <p><?php _e( 'This page shows personnel assigned to each shift and attempts to indicate who is currently on duty based on defined shift times.', 'incident-response-manager' ); ?></p>
        <hr>

        <?php
        $shifts = get_posts( array(
            'post_type' => 'shift',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ) );

        if ( empty( $shifts ) ) {
            echo '<p>' . __( 'No shifts defined. Please create shifts first.', 'incident-response-manager' ) . '</p>';
            return;
        }

        // Get current time in WordPress timezone
        $current_wp_time = current_time( 'H:i:s' ); // HH:MM:SS format for comparison
        $current_wp_timestamp = current_time( 'timestamp' );
        $today_day_name = date_i18n('l', $current_wp_timestamp); // For displaying today's day

        echo '<h2>' . sprintf( __('Current Date & Time: %s, %s'), $today_day_name, date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $current_wp_timestamp) ) . '</h2>';


        foreach ( $shifts as $shift_post ) {
            echo '<h3>' . esc_html( $shift_post->post_title ) . '</h3>';

            $shift_start_time = get_post_meta( $shift_post->ID, '_shift_start_time', true );
            $shift_end_time = get_post_meta( $shift_post->ID, '_shift_end_time', true );
            $shift_details = !empty($shift_post->post_content) ? '<p><em>' . wp_kses_post($shift_post->post_content) . '</em></p>' : '';


            echo '<p>';
            if ( $shift_start_time && $shift_end_time ) {
                printf(
                    __( 'Shift Times: %s - %s', 'incident-response-manager' ),
                    esc_html( date( 'g:i A', strtotime( $shift_start_time ) ) ),
                    esc_html( date( 'g:i A', strtotime( $shift_end_time ) ) )
                );
                
                // Simple on-duty check (assumes shifts don't cross midnight in a simple way for this check)
                // This check is basic. For 24-hour shifts or complex rotations, this needs more logic.
                $is_on_duty_currently = false;
                $start_timestamp = strtotime( $shift_start_time );
                $end_timestamp = strtotime( $shift_end_time );
                $current_time_of_day_timestamp = strtotime( $current_wp_time );

                if ($start_timestamp <= $end_timestamp) { // Shift does not cross midnight (e.g., 08:00 - 17:00)
                    if ($current_time_of_day_timestamp >= $start_timestamp && $current_time_of_day_timestamp < $end_timestamp) {
                        $is_on_duty_currently = true;
                    }
                } else { // Shift crosses midnight (e.g., 20:00 - 04:00)
                    if ($current_time_of_day_timestamp >= $start_timestamp || $current_time_of_day_timestamp < $end_timestamp) {
                        $is_on_duty_currently = true;
                    }
                }
                
                if( $is_on_duty_currently ){
                     echo ' <strong style="color: green;">(' . __( 'Currently On Duty', 'incident-response-manager' ) . ')</strong>';
                } else {
                     echo ' <strong style="color: red;">(' . __( 'Currently Off Duty', 'incident-response-manager' ) . ')</strong>';
                }

            } else {
                _e( 'Shift times not fully defined.', 'incident-response-manager' );
            }
            echo '</p>';
            echo $shift_details;


            $personnel_args = array(
                'post_type' => 'personnel',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_assigned_shift_id',
                        'value' => $shift_post->ID,
                        'compare' => '=',
                    ),
                ),
                'orderby' => 'title',
                'order' => 'ASC',
            );
            $assigned_personnel = get_posts( $personnel_args );

            if ( ! empty( $assigned_personnel ) ) {
                echo '<ul>';
                foreach ( $assigned_personnel as $person ) {
                    $rank = get_post_meta( $person->ID, '_rank', true );
                    echo '<li>' . esc_html( $person->post_title ) . ( $rank ? ' (' . esc_html( $rank ) . ')' : '' ) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __( 'No personnel assigned to this shift.', 'incident-response-manager' ) . '</p>';
            }
            echo '<hr>';
        }
        ?>
        <p><em><strong><?php _e('Note:', 'incident-response-manager'); ?></strong> <?php _e('The "Currently On Duty" status is a basic calculation based on defined shift start/end times and the current server time. It does not account for complex multi-day rotation patterns (e.g., 24/48, Kelly schedule) or specific day assignments within such rotations. A more advanced system would require tracking the full rotation cycle for each shift/personnel.', 'incident-response-manager'); ?></em></p>
    </div>
    <?php
}

?>
