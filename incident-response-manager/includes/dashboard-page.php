<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add IRM Dashboard admin page.
 */
function irm_add_dashboard_page() {
    add_menu_page(
        __( 'IRM Dashboard', 'incident-response-manager' ), 
        __( 'IRM Dashboard', 'incident-response-manager' ), 
        'read_incidents', 
        'irm_dashboard', 
        'irm_render_dashboard_page', 
        'dashicons-dashboard', 
        20 
    );
}
add_action( 'admin_menu', 'irm_add_dashboard_page' );

/**
 * Render the IRM Dashboard page.
 */
function irm_render_dashboard_page() {
    if ( ! current_user_can( 'read_incidents' ) ) { 
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'incident-response-manager' ) );
    }
    ?>
    <div class="wrap irm-dashboard-wrap">
        <h1><?php _e( 'Incident Response Manager Dashboard', 'incident-response-manager' ); ?></h1>
        
        <div id="dashboard-widgets-wrap">
            <div id="dashboard-widgets" class="metabox-holder">
                <div id="postbox-container-1" class="postbox-container">
                    <?php irm_dashboard_active_incidents(); ?>
                    <?php irm_dashboard_incidents_last_7_days(); // Added new chart widget ?>
                </div>
                <div id="postbox-container-2" class="postbox-container">
                    <?php irm_dashboard_vehicle_status(); ?>
                    <?php irm_dashboard_equipment_status(); ?>
                </div>
                 <div id="postbox-container-3" class="postbox-container">
                    <?php irm_dashboard_personnel_on_duty(); // Moved here for balance if 2 cols ?>
                </div>
            </div>
        </div>
    </div>
    <style>
        .irm-dashboard-wrap .postbox-container {
            width: 49%; 
            margin-right: 1%;
            float: left;
            margin-bottom: 20px; /* Ensure some space below containers if they wrap */
        }
         .irm-dashboard-wrap #postbox-container-3 { /* Example for a third column or different layout */
            /* width: 100%; clear: both; */ /* Uncomment for full width on new row */
        }
        .irm-dashboard-wrap .postbox {
            margin-bottom: 20px;
        }
        @media screen and (max-width: 1200px) { /* Adjust breakpoint for 2 columns */
             .irm-dashboard-wrap .postbox-container {
                width: 100%;
                margin-right: 0;
                float: none;
            }
        }
         @media screen and (max-width: 782px) {
            /* Already set to 100% width, this is fine */
        }
    </style>
    <?php
}

/**
 * Active Incidents Widget
 */
function irm_dashboard_active_incidents() {
    $active_status_terms_objects = get_terms(array(
        'taxonomy' => 'incident_status',
        'exclude' => term_exists('Closed', 'incident_status') ? get_term_by('slug', 'closed', 'incident_status')->term_id : 0,
    ));

    $chart_labels = array();
    $chart_data = array();
    $active_incidents_count = 0;

    if (!is_wp_error($active_status_terms_objects) && !empty($active_status_terms_objects)) {
        foreach ($active_status_terms_objects as $term) {
            if ($term->count > 0) { // Only include statuses with active incidents
                $chart_labels[] = $term->name;
                $chart_data[] = $term->count;
            }
            $active_incidents_count += $term->count;
        }
    } else {
        $active_status_slugs = array('reported', 'dispatched', 'on-scene', 'under-control'); 
        foreach($active_status_slugs as $slug){
            $term = get_term_by('slug', $slug, 'incident_status');
            if($term && !is_wp_error($term)){
                $chart_labels[] = $term->name;
                $chart_data[] = $term->count;
                $active_incidents_count += $term->count;
            } else {
                // $chart_labels[] = ucfirst(str_replace('-', ' ', $slug)); 
                // $chart_data[] = 0;
            }
        }
    }
    
    wp_localize_script( 'irm-charts', 'irmActiveIncidentsData', array(
        'labels' => !empty($chart_labels) ? $chart_labels : array(__('No Active Incidents')),
        'data' => !empty($chart_data) ? $chart_data : array(0),
        'title' => __('Active Incidents by Status', 'incident-response-manager')
    ) );
    ?>
    <div id="irm_active_incidents_summary" class="postbox">
        <h2 class="hndle"><span><?php _e( 'Active Incidents Summary', 'incident-response-manager' ); ?></span></h2>
        <div class="inside">
            <p><?php printf(_n('There is %d active incident.', 'There are %d active incidents.', $active_incidents_count, 'incident-response-manager'), $active_incidents_count); ?></p>
            <canvas id="irmActiveIncidentsChart" width="400" height="250"></canvas>
            <p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=incident' ) ); ?>"><?php _e( 'View All Incidents', 'incident-response-manager' ); ?></a></p>
        </div>
    </div>
    <?php
}


/**
 * Personnel On Duty Widget (Simplified)
 */
function irm_dashboard_personnel_on_duty() {
    ?>
    <div id="irm_personnel_on_duty" class="postbox">
        <h2 class="hndle"><span><?php _e( 'Personnel On Duty (Approximation)', 'incident-response-manager' ); ?></span></h2>
        <div class="inside">
            <?php
            $shifts = get_posts( array(
                'post_type' => 'shift',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            ) );

            if ( empty( $shifts ) ) {
                echo '<p>' . __( 'No shifts defined. Please create shifts and assign personnel.', 'incident-response-manager' ) . '</p>';
            } else {
                $current_wp_time_obj = new DateTimeImmutable("now", wp_timezone());
                $total_on_duty = 0;
                
                echo '<ul>';
                foreach ( $shifts as $shift_post ) {
                    $shift_start_time_str = get_post_meta( $shift_post->ID, '_shift_start_time', true );
                    $shift_end_time_str = get_post_meta( $shift_post->ID, '_shift_end_time', true );
                    $is_on_duty_currently = false;

                    if ( $shift_start_time_str && $shift_end_time_str ) {
                        try {
                            $shift_start_dt = DateTimeImmutable::createFromFormat('H:i', $shift_start_time_str, wp_timezone());
                            $shift_end_dt = DateTimeImmutable::createFromFormat('H:i', $shift_end_time_str, wp_timezone());
                            
                            if ($shift_start_dt && $shift_end_dt) {
                                $current_time_only_str = $current_wp_time_obj->format('H:i');
                                $current_dt_time_only = DateTimeImmutable::createFromFormat('H:i', $current_time_only_str, wp_timezone());

                                if ($shift_end_dt < $shift_start_dt) { 
                                   if ($current_dt_time_only >= $shift_start_dt || $current_dt_time_only < $shift_end_dt) {
                                        $is_on_duty_currently = true;
                                    }
                                } else { 
                                    if ($current_dt_time_only >= $shift_start_dt && $current_dt_time_only < $shift_end_dt) {
                                        $is_on_duty_currently = true;
                                    }
                                }
                            }
                        } catch (Exception $e) { /* Invalid time format */ }
                    }
                    
                    if ($is_on_duty_currently) {
                        $personnel_args = array(
                            'post_type' => 'personnel',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                            'meta_query' => array(
                                array(
                                    'key' => '_assigned_shift_id',
                                    'value' => $shift_post->ID,
                                    'compare' => '=',
                                ),
                            ),
                            'fields' => 'ids', // Only get IDs for counting
                        );
                        $assigned_personnel_query = new WP_Query( $personnel_args );
                        $count = $assigned_personnel_query->found_posts;
                        $total_on_duty += $count;
                        
                        echo '<li><strong>' . esc_html( $shift_post->post_title ) . ':</strong> ' . sprintf( _n( '%d personnel', '%d personnel', $count, 'incident-response-manager' ), $count ) . '</li>';
                    }
                }
                echo '</ul>';
                echo '<p><strong>' . sprintf( __( 'Total Estimated On Duty: %d', 'incident-response-manager' ), $total_on_duty ) . '</strong></p>';
                echo '<p><em>' . __( 'Note: This is a simplified approximation based on shift times and does not account for complex rotations or days off.', 'incident-response-manager' ) . '</em></p>';
                 echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=irm_duty_roster' ) ) . '">' . __( 'View Full Duty Roster', 'incident-response-manager' ) . '</a></p>';
            }
             wp_reset_postdata();
            ?>
        </div>
    </div>
    <?php
}

/**
 * Vehicle Status Summary Widget
 */
function irm_dashboard_vehicle_status() {
    $status_terms = get_terms( array(
        'taxonomy' => 'vehicle_status',
        'hide_empty' => false,
    ) );

    $chart_labels = array();
    $chart_data = array();
    $total_vehicles = 0;

    if ( !is_wp_error( $status_terms ) && !empty( $status_terms ) ) {
        foreach ( $status_terms as $term ) {
            $chart_labels[] = $term->name;
            $chart_data[] = $term->count;
            $total_vehicles += $term->count;
        }
    }

    wp_localize_script( 'irm-charts', 'irmVehicleStatusData', array(
        'labels' => !empty($chart_labels) ? $chart_labels : array(__('No Statuses')),
        'data' => !empty($chart_data) ? $chart_data : array(0),
        'title' => __('Vehicle Availability', 'incident-response-manager')
    ) );
    ?>
    <div id="irm_vehicle_status_summary" class="postbox">
        <h2 class="hndle"><span><?php _e( 'Vehicle Status Summary', 'incident-response-manager' ); ?></span></h2>
        <div class="inside">
            <?php
            if ( empty( $status_terms ) || is_wp_error( $status_terms ) ) {
                echo '<p>' . __( 'No vehicle statuses defined or found.', 'incident-response-manager' ) . '</p>';
            } else {
                echo '<canvas id="irmVehicleStatusChart" width="400" height="250"></canvas>';
                echo '<p><strong>' . sprintf( __( 'Total Vehicles: %d', 'incident-response-manager' ), $total_vehicles ) . '</strong></p>';
                echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=vehicle' ) ) . '">' . __( 'Manage Vehicles', 'incident-response-manager' ) . '</a></p>';
                echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=irm_maintenance_alerts' ) ) . '">' . __( 'View Maintenance Alerts', 'incident-response-manager' ) . '</a></p>';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Equipment Status Summary Widget
 */
function irm_dashboard_equipment_status() {
     $status_terms = get_terms( array(
        'taxonomy' => 'equipment_status',
        'hide_empty' => false,
    ) );

    $chart_labels = array();
    $chart_data = array();
    $total_equipment = 0;

    if ( !is_wp_error( $status_terms ) && !empty( $status_terms ) ) {
        foreach ( $status_terms as $term ) {
            $chart_labels[] = $term->name;
            $chart_data[] = $term->count;
            $total_equipment += $term->count;
        }
    }
    
    wp_localize_script( 'irm-charts', 'irmEquipmentStatusData', array(
        'labels' => !empty($chart_labels) ? $chart_labels : array(__('No Statuses')),
        'data' => !empty($chart_data) ? $chart_data : array(0),
        'title' => __('Equipment Availability', 'incident-response-manager')
    ) );
    ?>
    <div id="irm_equipment_status_summary" class="postbox">
        <h2 class="hndle"><span><?php _e( 'Equipment Status Summary', 'incident-response-manager' ); ?></span></h2>
        <div class="inside">
            <?php
            if ( empty( $status_terms ) || is_wp_error( $status_terms ) ) {
                echo '<p>' . __( 'No equipment statuses defined or found.', 'incident-response-manager' ) . '</p>';
            } else {
                echo '<canvas id="irmEquipmentStatusChart" width="400" height="250"></canvas>';
                 echo '<p><strong>' . sprintf( __( 'Total Equipment Items: %d', 'incident-response-manager' ), $total_equipment ) . '</strong></p>';
                echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=equipment' ) ) . '">' . __( 'Manage Equipment', 'incident-response-manager' ) . '</a></p>';
                echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=irm_maintenance_alerts' ) ) . '">' . __( 'View Maintenance Alerts', 'incident-response-manager' ) . '</a></p>';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Incidents over Last 7 Days Widget
 */
function irm_dashboard_incidents_last_7_days() {
    $dates = array();
    $incident_counts = array();
    $total_incidents_last_7_days = 0;

    for ( $i = 6; $i >= 0; $i-- ) {
        $date_obj = new DateTimeImmutable( "-{$i} days", wp_timezone() );
        $dates[] = $date_obj->format( 'M j' ); // Format for display: e.g., Oct 26

        $args = array(
            'post_type' => 'incident',
            'post_status' => 'publish', // Or any status considered an "occurrence"
            'date_query' => array(
                array(
                    'year'  => $date_obj->format( 'Y' ),
                    'month' => $date_obj->format( 'm' ),
                    'day'   => $date_obj->format( 'd' ),
                ),
            ),
            'fields' => 'ids', 
        );
        $query = new WP_Query( $args );
        $incident_counts[] = $query->found_posts;
        $total_incidents_last_7_days += $query->found_posts;
    }

    wp_localize_script( 'irm-charts', 'irmIncidentsLast7DaysData', array(
        'labels' => $dates,
        'data' => $incident_counts,
        'title' => __('Incidents per Day (Last 7 Days)', 'incident-response-manager'),
        'datasetLabel' => __('Incidents', 'incident-response-manager')
    ) );
    ?>
    <div id="irm_incidents_last_7_days" class="postbox">
        <h2 class="hndle"><span><?php _e( 'Incidents - Last 7 Days', 'incident-response-manager' ); ?></span></h2>
        <div class="inside">
             <p><?php printf(_n('There was %d incident in the last 7 days.', 'There were %d incidents in the last 7 days.', $total_incidents_last_7_days, 'incident-response-manager'), $total_incidents_last_7_days); ?></p>
            <canvas id="irmIncidentsLast7DaysChart" width="400" height="200"></canvas>
        </div>
    </div>
    <?php
}
?>
