<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add Maintenance Alerts admin page.
 */
function irm_add_maintenance_alerts_admin_page() {
    add_submenu_page(
        'edit.php?post_type=incident', // Parent slug
        __( 'Maintenance Alerts', 'incident-response-manager' ),
        __( 'Maintenance Alerts', 'incident-response-manager' ),
        'manage_vehicles', // Capability - Chiefs and admins
        'irm_maintenance_alerts',
        'irm_render_maintenance_alerts_page'
    );
}
add_action( 'admin_menu', 'irm_add_maintenance_alerts_admin_page' );

/**
 * Render the Maintenance Alerts page.
 */
function irm_render_maintenance_alerts_page() {
    if ( ! current_user_can( 'manage_vehicles' ) && ! current_user_can( 'manage_equipment' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'incident-response-manager' ) );
    }
    ?>
    <div class="wrap">
        <h1><?php _e( 'Maintenance Alerts', 'incident-response-manager' ); ?></h1>
        <p><?php _e( 'This page highlights vehicles and equipment that are overdue for maintenance or due within the next 30 days.', 'incident-response-manager' ); ?></p>
        
        <?php
        $today = date( 'Y-m-d' );
        $threshold_date_30_days = date( 'Y-m-d', strtotime( '+30 days' ) );

        $post_types_to_check = array( 'vehicle', 'equipment' );
        
        foreach ($post_types_to_check as $post_type) {
            $cpt_object = get_post_type_object($post_type);
            echo '<h2>' . sprintf(__( '%s Due for Maintenance', 'incident-response-manager'), $cpt_object->labels->name) . '</h2>';

            $args = array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => array(
                    'relation' => 'OR',
                    array( // Overdue
                        'key' => '_maintenance_schedule',
                        'value' => $today,
                        'compare' => '<',
                        'type' => 'DATE',
                    ),
                    array( // Due in next 30 days
                        'key' => '_maintenance_schedule',
                        'value' => array( $today, $threshold_date_30_days ),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE',
                    ),
                ),
                'orderby' => 'meta_value',
                'meta_key' => '_maintenance_schedule',
                'order' => 'ASC',
            );

            $items_due = new WP_Query( $args );

            if ( $items_due->have_posts() ) {
                echo '<table class="widefat fixed striped">';
                echo '<thead><tr><th>' . esc_html($cpt_object->labels->singular_name) . '</th><th>' . __('Next Scheduled Maintenance', 'incident-response-manager') . '</th><th>' . __('Status', 'incident-response-manager') . '</th><th>' . __('Last Maintenance', 'incident-response-manager') . '</th></tr></thead>';
                echo '<tbody>';
                while ( $items_due->have_posts() ) {
                    $items_due->the_post();
                    $item_id = get_the_ID();
                    $next_maintenance_date = get_post_meta( $item_id, '_maintenance_schedule', true );
                    $last_maintenance_date = get_post_meta( $item_id, '_last_maintenance_date', true );
                    
                    $status_terms = get_the_terms( $item_id, $post_type . '_status' );
                    $status_display = ! is_wp_error( $status_terms ) && ! empty( $status_terms ) ? implode( ', ', wp_list_pluck( $status_terms, 'name' ) ) : __( 'N/A', 'incident-response-manager' );

                    $alert_style = '';
                    if ($next_maintenance_date < $today) {
                        $alert_style = 'style="color: red; font-weight: bold;"'; // Overdue
                    }

                    echo '<tr>';
                    echo '<td><a href="' . esc_url(get_edit_post_link($item_id)) . '">' . esc_html(get_the_title()) . '</a></td>';
                    echo '<td ' . $alert_style . '>' . esc_html( $next_maintenance_date ? date_i18n(get_option('date_format'), strtotime($next_maintenance_date)) : __('Not Set', 'incident-response-manager') ) . '</td>';
                    echo '<td>' . esc_html($status_display) . '</td>';
                    echo '<td>' . esc_html( $last_maintenance_date ? date_i18n(get_option('date_format'), strtotime($last_maintenance_date)) : __('N/A', 'incident-response-manager') ) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>' . sprintf( __( 'No %s found that are overdue or due for maintenance in the next 30 days.', 'incident-response-manager' ), strtolower($cpt_object->labels->name) ) . '</p>';
            }
            wp_reset_postdata();
            echo '<hr>';
        }
        ?>
    </div>
    <?php
}

?>
