<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add IRM Reports submenu page under IRM Dashboard.
 */
function irm_add_reports_submenu_page() {
    add_submenu_page(
        'irm_dashboard', // Parent slug (IRM Dashboard)
        __( 'Reports', 'incident-response-manager' ), // Page title
        __( 'Reports', 'incident-response-manager' ), // Menu title
        'access_reports', // Capability required (Chiefs have this)
        'irm_reports', // Menu slug
        'irm_render_reports_page' // Callback function
    );
}
add_action( 'admin_menu', 'irm_add_reports_submenu_page' );

/**
 * Render the main Reports page.
 * This page will act as a router for different reports based on a query parameter.
 */
function irm_render_reports_page() {
    if ( ! current_user_can( 'access_reports' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.', 'incident-response-manager' ) );
    }

    // Handle CSV export actions at the top, before any HTML output
    if (isset($_POST['action']) && isset($_POST['_wpnonce_irm_export_report_nonce'])) {
        $report_type_export = isset($_POST['report_type_export']) ? sanitize_key($_POST['report_type_export']) : '';
        
        if ($report_type_export === 'incident_report' && wp_verify_nonce($_POST['_wpnonce_irm_export_report_nonce'], 'irm_export_incident_report_action')) {
            $filters = array(
                'filter_date_from' => isset($_POST['filter_date_from_csv']) ? sanitize_text_field($_POST['filter_date_from_csv']) : '',
                'filter_date_to' => isset($_POST['filter_date_to_csv']) ? sanitize_text_field($_POST['filter_date_to_csv']) : '',
                'filter_incident_type' => isset($_POST['filter_incident_type_csv']) ? sanitize_text_field($_POST['filter_incident_type_csv']) : '',
                'filter_incident_status' => isset($_POST['filter_incident_status_csv']) ? sanitize_text_field($_POST['filter_incident_status_csv']) : '',
            );
            irm_export_incident_report_csv_handler($filters);
        } elseif ($report_type_export === 'resource_utilization' && wp_verify_nonce($_POST['_wpnonce_irm_export_report_nonce'], 'irm_export_resource_report_action')) {
            $filters = array(
                'filter_date_from' => isset($_POST['filter_date_from_csv']) ? sanitize_text_field($_POST['filter_date_from_csv']) : '',
                'filter_date_to' => isset($_POST['filter_date_to_csv']) ? sanitize_text_field($_POST['filter_date_to_csv']) : '',
                'filter_resource_type' => isset($_POST['filter_resource_type_csv']) ? sanitize_text_field($_POST['filter_resource_type_csv']) : '',
                'filter_specific_resource' => isset($_POST['filter_specific_resource_csv']) ? absint($_POST['filter_specific_resource_csv']) : 0,
            );
            irm_export_resource_utilization_report_csv_handler($filters);
        } elseif ($report_type_export === 'personnel_activity' && wp_verify_nonce($_POST['_wpnonce_irm_export_report_nonce'], 'irm_export_personnel_activity_report_action')) {
            $filters = array(
                'filter_date_from' => isset($_POST['filter_date_from_csv']) ? sanitize_text_field($_POST['filter_date_from_csv']) : '',
                'filter_date_to' => isset($_POST['filter_date_to_csv']) ? sanitize_text_field($_POST['filter_date_to_csv']) : '',
                'filter_specific_personnel' => isset($_POST['filter_specific_personnel_csv']) ? absint($_POST['filter_specific_personnel_csv']) : 0,
                'filter_shift' => isset($_POST['filter_shift_csv']) ? absint($_POST['filter_shift_csv']) : 0,
            );
            irm_export_personnel_activity_report_csv_handler($filters);
        }
    }


    $current_report = isset( $_GET['report_type'] ) ? sanitize_key( $_GET['report_type'] ) : 'main';

    ?>
    <div class="wrap irm-reports-wrap">
        <h1><?php _e( 'Incident Response Manager Reports', 'incident-response-manager' ); ?></h1>

        <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Report types', 'incident-response-manager' ); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=irm_reports&report_type=incident_report')); ?>" class="nav-tab <?php if ($current_report === 'incident_report') echo 'nav-tab-active'; ?>">
                <?php _e('Incident Report', 'incident-response-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=irm_reports&report_type=resource_utilization')); ?>" class="nav-tab <?php if ($current_report === 'resource_utilization') echo 'nav-tab-active'; ?>">
                <?php _e('Resource Utilization', 'incident-response-manager'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=irm_reports&report_type=personnel_activity')); ?>" class="nav-tab <?php if ($current_report === 'personnel_activity') echo 'nav-tab-active'; ?>">
                <?php _e('Personnel Activity', 'incident-response-manager'); ?>
            </a>
        </nav>

        <div class="irm-report-content">
            <?php
            switch ( $current_report ) {
                case 'incident_report':
                    irm_render_incident_report_page_content();
                    break;
                case 'resource_utilization':
                    irm_render_resource_utilization_report_page_content();
                    break;
                case 'personnel_activity':
                    irm_render_personnel_activity_report_page_content();
                    break;
                default:
                    echo '<h2>' . __('Welcome to Reports', 'incident-response-manager') . '</h2>';
                    echo '<p>' . __('Please select a report type from the tabs above to view data and apply filters.', 'incident-response-manager') . '</p>';
                    break;
            }
            ?>
        </div>
    </div>
    <style>
        .irm-reports-wrap .irm-report-content { margin-top: 20px; }
        .irm-reports-wrap .form-table th { width: 180px; } 
        .irm-reports-wrap .report-table { margin-top: 20px; }
        .irm-reports-wrap .report-table th, .irm-reports-wrap .report-table td { padding: 8px; text-align: left; }
    </style>
    <?php
}

// Helper function to handle CSV export
function irm_handle_report_export($filename, $header_row, $data_rows) {
    if (headers_sent()) {
        wp_die(__('Could not export CSV, headers already sent.', 'incident-response-manager'));
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); 

    if (!empty($header_row)) {
        fputcsv($output, $header_row);
    }

    foreach ($data_rows as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Handler for Incident Report CSV Export
 */
function irm_export_incident_report_csv_handler($filters) {
    $export_data = irm_query_incident_report_data($filters);
    $csv_filename = 'incident_report_' . date('Y-m-d') . '.csv';
    $csv_header = array(
        __('ID', 'incident-response-manager'), __('Date/Time', 'incident-response-manager'),
        __('Type', 'incident-response-manager'), __('Location', 'incident-response-manager'),
        __('Status', 'incident-response-manager'), __('Units Assigned (Vehicles)', 'incident-response-manager'),
        __('Personnel Assigned', 'incident-response-manager'), __('Summary', 'incident-response-manager'),
    );
    $csv_data_rows = array();
    foreach ($export_data as $incident) {
        $status_terms = get_the_terms($incident->ID, 'incident_status');
        $status_display = !is_wp_error($status_terms) && !empty($status_terms) ? implode(', ', wp_list_pluck($status_terms, 'name')) : __('N/A');
        $assigned_personnel_ids = (array) get_post_meta($incident->ID, '_assigned_personnel_ids', true);
        $assigned_vehicle_ids = (array) get_post_meta($incident->ID, '_assigned_vehicle_ids', true);
        $csv_data_rows[] = array(
            $incident->ID, get_post_meta($incident->ID, '_datetime', true),
            get_post_meta($incident->ID, '_incident_type', true), get_post_meta($incident->ID, '_location', true),
            $status_display, count(array_filter($assigned_vehicle_ids)),
            count(array_filter($assigned_personnel_ids)), wp_strip_all_tags(wp_trim_words($incident->post_content, 20, '...'))
        );
    }
    irm_handle_report_export($csv_filename, $csv_header, $csv_data_rows);
}


/**
 * Render Incident Report page content (form and results).
 */
function irm_render_incident_report_page_content() {
    $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
    $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
    $filter_incident_type = isset($_GET['filter_incident_type']) ? sanitize_text_field($_GET['filter_incident_type']) : '';
    $filter_incident_status = isset($_GET['filter_incident_status']) ? sanitize_text_field($_GET['filter_incident_status']) : '';
    ?>
    <h2><?php _e('Incident Report', 'incident-response-manager'); ?></h2>
    <form method="GET">
        <input type="hidden" name="page" value="irm_reports">
        <input type="hidden" name="report_type" value="incident_report">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="filter_date_from"><?php _e('Date From:', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_from" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_date_to"><?php _e('Date To:', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_to" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_incident_type"><?php _e('Incident Type:', 'incident-response-manager'); ?></label></th>
                <td><input type="text" id="filter_incident_type" name="filter_incident_type" value="<?php echo esc_attr($filter_incident_type); ?>" placeholder="<?php _e('e.g., Fire, Medical', 'incident-response-manager'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_incident_status"><?php _e('Incident Status:', 'incident-response-manager'); ?></label></th>
                <td>
                    <select id="filter_incident_status" name="filter_incident_status">
                        <option value=""><?php _e('-- All Statuses --', 'incident-response-manager'); ?></option>
                        <?php
                        $statuses = get_terms(array('taxonomy' => 'incident_status', 'hide_empty' => false));
                        if (!is_wp_error($statuses)) {
                            foreach ($statuses as $status) {
                                echo '<option value="' . esc_attr($status->slug) . '" ' . selected($filter_incident_status, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="irm_filter_incident_report_display" class="button button-primary" value="<?php _e('Filter Report', 'incident-response-manager'); ?>">
        </p>
    </form>

    <form method="POST">
        <input type="hidden" name="action" value="export_incident_report_csv">
        <input type="hidden" name="report_type_export" value="incident_report">
        <?php wp_nonce_field('irm_export_incident_report_action', '_wpnonce_irm_export_report_nonce'); ?>
        <input type="hidden" name="filter_date_from_csv" value="<?php echo esc_attr($filter_date_from); ?>">
        <input type="hidden" name="filter_date_to_csv" value="<?php echo esc_attr($filter_date_to); ?>">
        <input type="hidden" name="filter_incident_type_csv" value="<?php echo esc_attr($filter_incident_type); ?>">
        <input type="hidden" name="filter_incident_status_csv" value="<?php echo esc_attr($filter_incident_status); ?>">
        <p class="submit">
            <input type="submit" class="button" value="<?php _e('Export to CSV (based on current filters)', 'incident-response-manager'); ?>">
        </p>
    </form>

    <?php
    if (isset($_GET['irm_filter_incident_report_display']) || !empty(array_filter(compact('filter_date_from', 'filter_date_to', 'filter_incident_type', 'filter_incident_status')))) {
        $incidents_data = irm_query_incident_report_data(compact('filter_date_from', 'filter_date_to', 'filter_incident_type', 'filter_incident_status'));
        if (empty($incidents_data)) {
            echo '<p>' . __('No incidents found matching your criteria.', 'incident-response-manager') . '</p>';
        } else {
            ?>
            <table class="widefat fixed striped report-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'incident-response-manager'); ?></th><th><?php _e('Date/Time', 'incident-response-manager'); ?></th>
                        <th><?php _e('Type', 'incident-response-manager'); ?></th><th><?php _e('Location', 'incident-response-manager'); ?></th>
                        <th><?php _e('Status', 'incident-response-manager'); ?></th><th><?php _e('Units (Vehicles)', 'incident-response-manager'); ?></th>
                        <th><?php _e('Personnel', 'incident-response-manager'); ?></th><th><?php _e('Summary', 'incident-response-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($incidents_data as $incident) {
                        $assigned_personnel_ids = (array) get_post_meta($incident->ID, '_assigned_personnel_ids', true);
                        $assigned_vehicle_ids = (array) get_post_meta($incident->ID, '_assigned_vehicle_ids', true);
                        $units_count = count(array_filter($assigned_vehicle_ids)); 
                        $personnel_count = count(array_filter($assigned_personnel_ids));
                        $status_terms = get_the_terms($incident->ID, 'incident_status');
                        $status_display = !is_wp_error($status_terms) && !empty($status_terms) ? implode(', ', wp_list_pluck($status_terms, 'name')) : __('N/A');

                        echo '<tr>';
                        echo '<td><a href="' . esc_url(get_edit_post_link($incident->ID)) . '">' . esc_html($incident->ID) . '</a></td>';
                        echo '<td>' . esc_html(get_post_meta($incident->ID, '_datetime', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($incident->ID, '_incident_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($incident->ID, '_location', true)) . '</td>';
                        echo '<td>' . esc_html($status_display) . '</td>';
                        echo '<td>' . esc_html($units_count) . '</td>';
                        echo '<td>' . esc_html($personnel_count) . '</td>';
                        echo '<td>' . esc_html(wp_trim_words($incident->post_content, 15, '...')) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
    }
    ?>
    <?php
}

/**
 * Query data for Incident Report based on filters.
 */
function irm_query_incident_report_data($filters) {
    $args = array(
        'post_type' => 'incident',
        'posts_per_page' => -1, 
        'post_status' => 'publish', 
        'meta_key' => '_datetime', 
        'orderby' => 'meta_value', 
        'order' => 'DESC',
        'meta_query' => array('relation' => 'AND'),
        'tax_query' => array('relation' => 'AND'),
    );

    if (!empty($filters['filter_date_from'])) {
        $args['meta_query'][] = array(
            'key' => '_datetime',
            'value' => sanitize_text_field($filters['filter_date_from']) . ' 00:00:00',
            'compare' => '>=',
            'type' => 'DATETIME'
        );
    }
    if (!empty($filters['filter_date_to'])) {
        $args['meta_query'][] = array(
            'key' => '_datetime',
            'value' => sanitize_text_field($filters['filter_date_to']) . ' 23:59:59',
            'compare' => '<=',
            'type' => 'DATETIME'
        );
    }
    if (!empty($filters['filter_incident_type'])) {
        $args['meta_query'][] = array(
            'key' => '_incident_type',
            'value' => sanitize_text_field($filters['filter_incident_type']),
            'compare' => 'LIKE'
        );
    }
    if (!empty($filters['filter_incident_status'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'incident_status',
            'field' => 'slug',
            'terms' => sanitize_text_field($filters['filter_incident_status']),
        );
    }
    if (empty($filters['filter_date_from']) && empty($filters['filter_date_to']) && empty($args['meta_query']['meta_query'])) { // Check if meta_query is truly empty apart from relation
         unset($args['meta_key']); 
         $args['orderby'] = 'date';
    }
    
    $query = new WP_Query($args);
    return $query->posts; 
}

/**
 * Handler for Resource Utilization Report CSV Export
 */
function irm_export_resource_utilization_report_csv_handler($filters) {
    $export_data = irm_query_resource_utilization_data($filters); 
    
    $csv_filename = 'resource_utilization_report_' . date('Y-m-d') . '.csv';
    $csv_header = array(
        __('Resource ID/Name', 'incident-response-manager'), __('Type', 'incident-response-manager'),
        __('# Incidents Assigned', 'incident-response-manager'), __('Maintenance Logs', 'incident-response-manager'),
        __('Current Status', 'incident-response-manager')
    );
    $csv_data_rows = array();

    foreach ($export_data as $resource) { 
        $resource_type = get_post_type_object($resource->post_type)->labels->singular_name;
        $incidents_assigned_count = 0;
        if ($resource->post_type === 'vehicle') {
            $incident_query_args = array(
                'post_type' => 'incident', 'posts_per_page' => -1, 'post_status' => 'publish',
                'meta_query' => array(
                    array('key' => '_assigned_vehicle_ids', 'value' => '"' . $resource->ID . '"', 'compare' => 'LIKE')
                ),
                'date_query' => array('relation' => 'AND')
            );
            if (!empty($filters['filter_date_from'])) $incident_query_args['date_query']['after'] = $filters['filter_date_from'];
            if (!empty($filters['filter_date_to'])) $incident_query_args['date_query']['before'] = $filters['filter_date_to'];
            if (count($incident_query_args['date_query']) === 1) unset($incident_query_args['date_query']['relation']);


            $incidents_assigned_query = new WP_Query($incident_query_args);
            $incidents_assigned_count = $incidents_assigned_query->found_posts;
        } 

        $maintenance_logs_count = get_comments(array('post_id' => $resource->ID, 'type' => $resource->post_type . '_maintenance_log', 'count' => true, 'status' => 'approve'));
        $status_terms = get_the_terms($resource->ID, $resource->post_type . '_status');
        $status_display = !is_wp_error($status_terms) && !empty($status_terms) ? implode(', ', wp_list_pluck($status_terms, 'name')) : __('N/A');

        $csv_data_rows[] = array(
            $resource->post_title . ' (ID: ' . $resource->ID . ')', $resource_type,
            $incidents_assigned_count, $maintenance_logs_count,
            $status_display
        );
    }
    irm_handle_report_export($csv_filename, $csv_header, $csv_data_rows);
}


/**
 * Render Resource Utilization Report page content.
 */
function irm_render_resource_utilization_report_page_content() {
    $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
    $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
    $filter_resource_type = isset($_GET['filter_resource_type']) ? sanitize_text_field($_GET['filter_resource_type']) : ''; 
    $filter_specific_resource = isset($_GET['filter_specific_resource']) ? absint($_GET['filter_specific_resource']) : 0;
    ?>
    <h2><?php _e('Resource Utilization Report', 'incident-response-manager'); ?></h2>
     <form method="GET">
        <input type="hidden" name="page" value="irm_reports">
        <input type="hidden" name="report_type" value="resource_utilization">
        <table class="form-table">
             <tr valign="top">
                <th scope="row"><label for="filter_date_from_res"><?php _e('Date From (for incident assignment):', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_from_res" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_date_to_res"><?php _e('Date To (for incident assignment):', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_to_res" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_resource_type"><?php _e('Resource Type:', 'incident-response-manager'); ?></label></th>
                <td>
                    <select id="filter_resource_type" name="filter_resource_type">
                        <option value=""><?php _e('-- All Types --', 'incident-response-manager'); ?></option>
                        <option value="vehicle" <?php selected($filter_resource_type, 'vehicle'); ?>><?php _e('Vehicle', 'incident-response-manager'); ?></option>
                        <option value="equipment" <?php selected($filter_resource_type, 'equipment'); ?>><?php _e('Equipment', 'incident-response-manager'); ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_specific_resource"><?php _e('Specific Resource:', 'incident-response-manager'); ?></label></th>
                <td>
                    <select id="filter_specific_resource" name="filter_specific_resource">
                        <option value="0"><?php _e('-- All Resources --', 'incident-response-manager'); ?></option>
                        <?php
                        $resource_posts_q_args = array('post_type' => array('vehicle', 'equipment'), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC');
                        if($filter_resource_type) $resource_posts_q_args['post_type'] = $filter_resource_type; // Filter dropdown by selected type
                        
                        $resource_posts = get_posts($resource_posts_q_args);
                        foreach ($resource_posts as $resource_post) {
                             echo '<option value="' . esc_attr($resource_post->ID) . '" ' . selected($filter_specific_resource, $resource_post->ID, false) . '>' . esc_html($resource_post->post_title) . ' (' . esc_html(get_post_type_object($resource_post->post_type)->labels->singular_name) . ')' . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="irm_filter_resource_report_display" class="button button-primary" value="<?php _e('Filter Report', 'incident-response-manager'); ?>">
        </p>
    </form>

    <form method="POST">
        <input type="hidden" name="action" value="export_resource_report_csv">
        <input type="hidden" name="report_type_export" value="resource_utilization">
        <?php wp_nonce_field('irm_export_resource_report_action', '_wpnonce_irm_export_report_nonce'); ?>
        <input type="hidden" name="filter_date_from_csv" value="<?php echo esc_attr($filter_date_from); ?>">
        <input type="hidden" name="filter_date_to_csv" value="<?php echo esc_attr($filter_date_to); ?>">
        <input type="hidden" name="filter_resource_type_csv" value="<?php echo esc_attr($filter_resource_type); ?>">
        <input type="hidden" name="filter_specific_resource_csv" value="<?php echo esc_attr($filter_specific_resource); ?>">
        <p class="submit">
            <input type="submit" class="button" value="<?php _e('Export to CSV (based on current filters)', 'incident-response-manager'); ?>">
        </p>
    </form>

    <?php
    if (isset($_GET['irm_filter_resource_report_display']) || !empty(array_filter(compact('filter_date_from', 'filter_date_to', 'filter_resource_type', 'filter_specific_resource')))) {
        $resources_data = irm_query_resource_utilization_data(compact('filter_date_from', 'filter_date_to', 'filter_resource_type', 'filter_specific_resource'));
        if (empty($resources_data)) {
            echo '<p>' . __('No resources found matching your criteria.', 'incident-response-manager') . '</p>';
        } else {
            ?>
            <table class="widefat fixed striped report-table">
                <thead>
                    <tr>
                        <th><?php _e('Resource ID/Name', 'incident-response-manager'); ?></th>
                        <th><?php _e('Type', 'incident-response-manager'); ?></th>
                        <th><?php _e('# Incidents Assigned', 'incident-response-manager'); ?></th>
                        <th><?php _e('Maintenance Logs', 'incident-response-manager'); ?></th>
                        <th><?php _e('Current Status', 'incident-response-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resources_data as $resource): 
                    $resource_type_obj = get_post_type_object($resource->post_type);
                    $resource_type_label = $resource_type_obj ? $resource_type_obj->labels->singular_name : $resource->post_type;
                    
                    $incidents_assigned_count = 0;
                    if ($resource->post_type === 'vehicle') {
                        $incident_query_args = array(
                            'post_type' => 'incident', 'posts_per_page' => -1, 'post_status' => 'publish',
                            'meta_query' => array(
                                array('key' => '_assigned_vehicle_ids', 'value' => '"' . $resource->ID . '"', 'compare' => 'LIKE')
                            ),
                            'date_query' => array('relation' => 'AND')
                        );
                         if (!empty($filter_date_from)) $incident_query_args['date_query']['after'] = $filter_date_from;
                         if (!empty($filter_date_to)) $incident_query_args['date_query']['before'] = $filter_date_to;
                         if (count($incident_query_args['date_query']) === 1) unset($incident_query_args['date_query']['relation']);

                        $incidents_assigned_query = new WP_Query($incident_query_args);
                        $incidents_assigned_count = $incidents_assigned_query->found_posts;
                    }
                    
                    $maintenance_logs_count = get_comments(array('post_id' => $resource->ID, 'type' => $resource->post_type . '_maintenance_log', 'count' => true, 'status' => 'approve'));
                    $status_terms = get_the_terms($resource->ID, $resource->post_type . '_status');
                    $status_display = !is_wp_error($status_terms) && !empty($status_terms) ? implode(', ', wp_list_pluck($status_terms, 'name')) : __('N/A');
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url(get_edit_post_link($resource->ID)); ?>"><?php echo esc_html($resource->post_title); ?> (ID: <?php echo esc_html($resource->ID); ?>)</a></td>
                        <td><?php echo esc_html($resource_type_label); ?></td>
                        <td><?php echo esc_html($incidents_assigned_count); ?></td>
                        <td><?php echo esc_html($maintenance_logs_count); ?></td>
                        <td><?php echo esc_html($status_display); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }
    ?>
    <?php
}

/**
 * Query data for Resource Utilization Report.
 */
function irm_query_resource_utilization_data($filters) {
    $resource_types = array();
    if (!empty($filters['filter_resource_type'])) {
        $resource_types[] = sanitize_text_field($filters['filter_resource_type']);
    } else {
        $resource_types = array('vehicle', 'equipment'); 
    }

    $args = array(
        'post_type' => $resource_types,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    );

    if (!empty($filters['filter_specific_resource'])) {
        $args['p'] = absint($filters['filter_specific_resource']); 
    }
    
    $query = new WP_Query($args);
    return $query->posts;
}

/**
 * Handler for Personnel Activity Report CSV Export
 */
function irm_export_personnel_activity_report_csv_handler($filters) {
    $export_data = irm_query_personnel_activity_data($filters);
    $csv_filename = 'personnel_activity_report_' . date('Y-m-d') . '.csv';
    $csv_header = array(
        __('Personnel ID', 'incident-response-manager'), __('Name', 'incident-response-manager'),
        __('Rank', 'incident-response-manager'), __('Shift', 'incident-response-manager'),
        __('# Incidents Responded To', 'incident-response-manager'),
    );
    $csv_data_rows = array();

    foreach ($export_data as $personnel) {
        $shift_id = get_post_meta($personnel->ID, '_assigned_shift_id', true);
        $shift_name = $shift_id ? get_the_title($shift_id) : __('N/A');
        
        $incident_query_args = array(
            'post_type' => 'incident', 'posts_per_page' => -1, 'post_status' => 'publish',
             'meta_query' => array(
                array('key' => '_assigned_personnel_ids', 'value' => '"' . $personnel->ID . '"', 'compare' => 'LIKE')
            ),
            'date_query' => array('relation' => 'AND')
        );
        if (!empty($filters['filter_date_from'])) $incident_query_args['date_query']['after'] = $filters['filter_date_from'];
        if (!empty($filters['filter_date_to'])) $incident_query_args['date_query']['before'] = $filters['filter_date_to'];
        if (count($incident_query_args['date_query']) === 1) unset($incident_query_args['date_query']['relation']);

        $incidents_responded_query = new WP_Query($incident_query_args);
        $incidents_responded_count = $incidents_responded_query->found_posts;

        $csv_data_rows[] = array(
            $personnel->ID, $personnel->post_title,
            get_post_meta($personnel->ID, '_rank', true), $shift_name,
            $incidents_responded_count
        );
    }
    irm_handle_report_export($csv_filename, $csv_header, $csv_data_rows);
}


/**
 * Render Personnel Activity Report page content.
 */
function irm_render_personnel_activity_report_page_content() {
    $filter_date_from = isset($_GET['filter_date_from']) ? sanitize_text_field($_GET['filter_date_from']) : '';
    $filter_date_to = isset($_GET['filter_date_to']) ? sanitize_text_field($_GET['filter_date_to']) : '';
    $filter_specific_personnel = isset($_GET['filter_specific_personnel']) ? absint($_GET['filter_specific_personnel']) : 0;
    $filter_shift = isset($_GET['filter_shift']) ? absint($_GET['filter_shift']) : 0;
    ?>
    <h2><?php _e('Personnel Activity Report', 'incident-response-manager'); ?></h2>
    <form method="GET">
        <input type="hidden" name="page" value="irm_reports">
        <input type="hidden" name="report_type" value="personnel_activity">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="filter_date_from_pa"><?php _e('Date From (for incident assignment):', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_from_pa" name="filter_date_from" value="<?php echo esc_attr($filter_date_from); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_date_to_pa"><?php _e('Date To (for incident assignment):', 'incident-response-manager'); ?></label></th>
                <td><input type="date" id="filter_date_to_pa" name="filter_date_to" value="<?php echo esc_attr($filter_date_to); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="filter_specific_personnel"><?php _e('Specific Personnel:', 'incident-response-manager'); ?></label></th>
                <td>
                    <select id="filter_specific_personnel" name="filter_specific_personnel">
                        <option value="0"><?php _e('-- All Personnel --', 'incident-response-manager'); ?></option>
                        <?php
                        $personnel_posts = get_posts(array('post_type' => 'personnel', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                        foreach ($personnel_posts as $p_post) {
                             echo '<option value="' . esc_attr($p_post->ID) . '" ' . selected($filter_specific_personnel, $p_post->ID, false) . '>' . esc_html($p_post->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
             <tr valign="top">
                <th scope="row"><label for="filter_shift"><?php _e('Shift:', 'incident-response-manager'); ?></label></th>
                <td>
                    <select id="filter_shift" name="filter_shift">
                        <option value="0"><?php _e('-- All Shifts --', 'incident-response-manager'); ?></option>
                        <?php
                        $shifts = get_posts(array('post_type' => 'shift', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                        foreach ($shifts as $s_post) {
                             echo '<option value="' . esc_attr($s_post->ID) . '" ' . selected($filter_shift, $s_post->ID, false) . '>' . esc_html($s_post->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="irm_filter_personnel_activity_display" class="button button-primary" value="<?php _e('Filter Report', 'incident-response-manager'); ?>">
        </p>
    </form>

    <form method="POST">
        <input type="hidden" name="action" value="export_personnel_activity_csv">
        <input type="hidden" name="report_type_export" value="personnel_activity">
        <?php wp_nonce_field('irm_export_personnel_activity_report_action', '_wpnonce_irm_export_report_nonce'); ?>
        <input type="hidden" name="filter_date_from_csv" value="<?php echo esc_attr($filter_date_from); ?>">
        <input type="hidden" name="filter_date_to_csv" value="<?php echo esc_attr($filter_date_to); ?>">
        <input type="hidden" name="filter_specific_personnel_csv" value="<?php echo esc_attr($filter_specific_personnel); ?>">
        <input type="hidden" name="filter_shift_csv" value="<?php echo esc_attr($filter_shift); ?>">
        <p class="submit">
            <input type="submit" class="button" value="<?php _e('Export to CSV (based on current filters)', 'incident-response-manager'); ?>">
        </p>
    </form>

    <?php
    if (isset($_GET['irm_filter_personnel_activity_display']) || !empty(array_filter(compact('filter_date_from', 'filter_date_to', 'filter_specific_personnel', 'filter_shift')))) {
        $personnel_data = irm_query_personnel_activity_data(compact('filter_date_from', 'filter_date_to', 'filter_specific_personnel', 'filter_shift'));
        if (empty($personnel_data)) {
            echo '<p>' . __('No personnel activity found matching your criteria.', 'incident-response-manager') . '</p>';
        } else {
            ?>
            <table class="widefat fixed striped report-table">
                <thead>
                    <tr>
                        <th><?php _e('Personnel ID', 'incident-response-manager'); ?></th>
                        <th><?php _e('Name', 'incident-response-manager'); ?></th>
                        <th><?php _e('Rank', 'incident-response-manager'); ?></th>
                        <th><?php _e('Shift', 'incident-response-manager'); ?></th>
                        <th><?php _e('# Incidents Responded To', 'incident-response-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($personnel_data as $personnel): 
                    $shift_id = get_post_meta($personnel->ID, '_assigned_shift_id', true);
                    $shift_name = $shift_id ? get_the_title($shift_id) : __('N/A');
                    
                    $incident_query_args = array(
                        'post_type' => 'incident', 'posts_per_page' => -1, 'post_status' => 'publish',
                         'meta_query' => array(
                            array('key' => '_assigned_personnel_ids', 'value' => '"' . $personnel->ID . '"', 'compare' => 'LIKE')
                        ),
                        'date_query' => array('relation' => 'AND')
                    );
                    if (!empty($filter_date_from)) $incident_query_args['date_query']['after'] = $filter_date_from;
                    if (!empty($filter_date_to)) $incident_query_args['date_query']['before'] = $filter_date_to;
                    if (count($incident_query_args['date_query']) === 1) unset($incident_query_args['date_query']['relation']);

                    $incidents_responded_query = new WP_Query($incident_query_args);
                    $incidents_responded_count = $incidents_responded_query->found_posts;
                ?>
                    <tr>
                        <td><?php echo esc_html($personnel->ID); ?></td>
                        <td><a href="<?php echo esc_url(get_edit_post_link($personnel->ID)); ?>"><?php echo esc_html($personnel->post_title); ?></a></td>
                        <td><?php echo esc_html(get_post_meta($personnel->ID, '_rank', true)); ?></td>
                        <td><?php echo esc_html($shift_name); ?></td>
                        <td><?php echo esc_html($incidents_responded_count); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }
    ?>
    <?php
}

/**
 * Query data for Personnel Activity Report.
 */
function irm_query_personnel_activity_data($filters) {
    $args = array(
        'post_type' => 'personnel',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array('relation' => 'AND'),
    );

    if (!empty($filters['filter_specific_personnel'])) {
        $args['p'] = absint($filters['filter_specific_personnel']);
    }
    if (!empty($filters['filter_shift'])) {
        $args['meta_query'][] = array(
            'key' => '_assigned_shift_id',
            'value' => absint($filters['filter_shift']),
            'compare' => '='
        );
    }
     // The date range filter for personnel activity applies to the incidents they were involved in,
    // so it's not directly applied to the personnel query itself but rather when counting incidents.
    
    $query = new WP_Query($args);
    return $query->posts;
}


?>
