<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register custom REST API endpoints.
 */
add_action( 'rest_api_init', 'irm_register_rest_endpoints' );

function irm_register_rest_endpoints() {
    $namespace = 'irm/v1'; // API Namespace

    // --- Incident Endpoints ---
    register_rest_route( $namespace, '/incidents', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'irm_get_incidents',
            'permission_callback' => 'irm_get_incidents_permissions_check',
            'args' => irm_get_incident_list_args(), 
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'irm_create_incident',
            'permission_callback' => 'irm_create_incident_permissions_check',
            'args' => irm_get_incident_args(),
        ),
    ) );

    register_rest_route( $namespace, '/incidents/(?P<id>\d+)', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'irm_get_incident',
            'permission_callback' => 'irm_get_incident_permissions_check',
            'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
        array(
            'methods' => WP_REST_Server::EDITABLE, 
            'callback' => 'irm_update_incident',
            'permission_callback' => 'irm_update_incident_permissions_check',
            'args' => irm_get_incident_args( false ), 
        ),
        array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => 'irm_delete_incident',
            'permission_callback' => 'irm_delete_incident_permissions_check',
            'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
    ) );

    register_rest_route( $namespace, '/incidents/(?P<id>\d+)/logs', array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'irm_get_incident_logs',
            'permission_callback' => 'irm_get_incident_logs_permissions_check',
             'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'irm_add_incident_log',
            'permission_callback' => 'irm_add_incident_log_permissions_check', // Corrected permission callback
            'args' => array( 
                'id' => array('validate_callback' => 'is_numeric', 'required' => true),
                'log_entry' => array( 'required' => true, 'sanitize_callback' => 'wp_kses_post' ) 
            ),
        ),
    ) );

    register_rest_route( $namespace, '/incidents/(?P<id>\d+)/assign', array(
        array(
            'methods' => WP_REST_Server::EDITABLE, 
            'callback' => 'irm_assign_to_incident',
            'permission_callback' => 'irm_assign_to_incident_permissions_check', 
            'args' => array(
                'id' => array('validate_callback' => 'is_numeric', 'required' => true),
                'personnel_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'sanitize_callback' => 'irm_sanitize_integer_array' ),
                'vehicle_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'sanitize_callback' => 'irm_sanitize_integer_array' ),
            ),
        ),
    ) );

    // --- Personnel Endpoints ---
    $personnel_cpt_slug = 'personnel';
    irm_register_standard_cpt_routes($namespace, $personnel_cpt_slug, 'irm_get_personnel_list_args', 'irm_get_personnel_args', 'irm_get_personnel_permissions_check', 'irm_edit_personnel_permissions_check', 'irm_delete_personnel_permissions_check');
    register_rest_route( $namespace, "/{$personnel_cpt_slug}/(?P<id>\d+)/shifts", array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'irm_get_personnel_shifts',
        'permission_callback' => 'irm_get_personnel_permissions_check', 
        'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
    ));


    // --- Vehicle Endpoints ---
    $vehicle_cpt_slug = 'vehicle';
    irm_register_standard_cpt_routes($namespace, $vehicle_cpt_slug, 'irm_get_vehicle_list_args', 'irm_get_vehicle_args', 'irm_get_vehicles_permissions_check', 'irm_edit_vehicles_permissions_check', 'irm_delete_vehicles_permissions_check');
    irm_register_log_routes($namespace, $vehicle_cpt_slug, 'irm_get_vehicle_maintenance_logs', 'irm_add_vehicle_maintenance_log', 'irm_get_vehicle_maintenance_logs_permissions_check', 'irm_add_vehicle_maintenance_log_permissions_check');


    // --- Equipment Endpoints ---
    $equipment_cpt_slug = 'equipment';
    irm_register_standard_cpt_routes($namespace, $equipment_cpt_slug, 'irm_get_equipment_list_args', 'irm_get_equipment_args', 'irm_get_equipment_permissions_check', 'irm_edit_equipment_permissions_check', 'irm_delete_equipment_permissions_check');
    irm_register_log_routes($namespace, $equipment_cpt_slug, 'irm_get_equipment_maintenance_logs', 'irm_add_equipment_maintenance_log', 'irm_get_equipment_maintenance_logs_permissions_check', 'irm_add_equipment_maintenance_log_permissions_check');

    // --- Shift Endpoints ---
    $shift_cpt_slug = 'shift';
    irm_register_standard_cpt_routes($namespace, $shift_cpt_slug, 'irm_get_shift_list_args', 'irm_get_shift_args', 'irm_get_shifts_permissions_check', 'irm_edit_shifts_permissions_check', 'irm_delete_shifts_permissions_check');

    // --- Duty Roster Endpoint ---
    register_rest_route( $namespace, '/roster', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'irm_get_duty_roster',
        'permission_callback' => 'irm_view_roster_permissions_check', 
        'args' => array(
            'shift_id' => array( 'validate_callback' => 'is_numeric', 'sanitize_callback' => 'absint'),
            'date' => array( 'sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'irm_validate_date_format_optional' ), 
        ),
    ) );
}

/**
 * Helper function to register standard CPT routes (GET all, POST new, GET one, PUT one, DELETE one)
 */
function irm_register_standard_cpt_routes($namespace, $cpt_slug, $list_args_callback, $item_args_callback, $get_perm_callback, $edit_perm_callback, $delete_perm_callback) {
    register_rest_route( $namespace, "/{$cpt_slug}", array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => "irm_get_{$cpt_slug}_items", 
            'permission_callback' => $get_perm_callback,
            'args' => call_user_func($list_args_callback),
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => "irm_create_{$cpt_slug}_item",
            'permission_callback' => $edit_perm_callback, 
            'args' => call_user_func($item_args_callback),
        ),
    ) );

    register_rest_route( $namespace, "/{$cpt_slug}/(?P<id>\d+)", array(
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => "irm_get_{$cpt_slug}_item",
            'permission_callback' => $get_perm_callback, 
            'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
        array(
            'methods' => WP_REST_Server::EDITABLE, 
            'callback' => "irm_update_{$cpt_slug}_item",
            'permission_callback' => $edit_perm_callback,
            'args' => call_user_func($item_args_callback, false), 
        ),
        array(
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => "irm_delete_{$cpt_slug}_item",
            'permission_callback' => $delete_perm_callback,
            'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
    ) );
}

/**
 * Helper function to register log routes for a CPT (GET logs, POST log)
 */
function irm_register_log_routes($namespace, $cpt_slug, $get_callback, $post_callback, $get_log_perm_callback, $add_log_perm_callback) {
    register_rest_route( $namespace, "/{$cpt_slug}/(?P<id>\d+)/logs", array( 
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => $get_callback,
            'permission_callback' => $get_log_perm_callback, 
            'args' => array('id' => array('validate_callback' => 'is_numeric', 'required' => true)),
        ),
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => $post_callback,
            'permission_callback' => $add_log_perm_callback, 
            'args' => array( 
                'id' => array('validate_callback' => 'is_numeric', 'required' => true),
                'log_entry' => array( 'required' => true, 'sanitize_callback' => 'wp_kses_post' ) 
            ),
        ),
    ) );
}


// --- Helper function to prepare CPT item for REST response ---
function irm_prepare_item_for_response( $post_or_id, $request ) {
    $post = get_post($post_or_id);
    if (!$post) {
        return new WP_Error('rest_post_invalid_id', __('Invalid post ID.'), array('status' => 404));
    }

    $post_type = $post->post_type;
    $data = array();
    
    $data['id'] = $post->ID;
    if (post_type_supports($post_type, 'title')) {
        $data['title'] = array(
            'raw' => $post->post_title,
            'rendered' => get_the_title($post->ID)
        );
    }
    if (post_type_supports($post_type, 'editor')) {
         $data['content'] = array(
            'raw' => $post->post_content,
            'rendered' => apply_filters('the_content', $post->post_content)
        );
    }
    $data['date_created_gmt'] = $post->post_date_gmt;
    $data['date_modified_gmt'] = $post->post_modified_gmt;
    $data['slug'] = $post->post_name;
    $data['link'] = get_permalink( $post->ID );
    $data['status'] = $post->post_status; 

    $meta = get_post_meta( $post->ID );
    $data['meta'] = array();
    
    $allowed_meta_keys_map = array(
        'incident' => array('_incident_type', '_location', '_datetime', '_responding_units', '_assigned_personnel_ids', '_assigned_vehicle_ids'),
        'personnel' => array('_rank', '_assigned_shift_id', '_qualifications', '_contact_information'),
        'vehicle' => array('_vehicle_type', '_call_sign', '_assigned_personnel', '_maintenance_schedule', '_last_maintenance_date'),
        'equipment' => array('_equipment_type', '_equipment_id', '_location', '_maintenance_schedule', '_last_maintenance_date'),
        'shift' => array('_shift_start_time', '_shift_end_time'),
    );

    $allowed_meta_keys = isset($allowed_meta_keys_map[$post_type]) ? $allowed_meta_keys_map[$post_type] : array();

    foreach ( $allowed_meta_keys as $key ) {
        if (isset($meta[$key])) {
            $output_key = ltrim($key, '_'); 
            $data['meta'][$output_key] = maybe_unserialize( $meta[$key][0] );
        } else {
            $output_key = ltrim($key, '_');
            $data['meta'][$output_key] = null; 
        }
    }
    
    $taxonomies = get_object_taxonomies( $post_type, 'objects' );
    foreach ( $taxonomies as $taxonomy_slug => $taxonomy_obj ) {
        if ($taxonomy_obj->show_in_rest) { 
            $terms = get_the_terms( $post->ID, $taxonomy_slug );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                $data[$taxonomy_slug] = array_map(function($term) {
                    return array('id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug);
                }, $terms);
            } else {
                $data[$taxonomy_slug] = array();
            }
        }
    }

    if ($post_type === 'incident') {
        $assigned_personnel_ids = isset($data['meta']['assigned_personnel_ids']) ? (array)$data['meta']['assigned_personnel_ids'] : array();
        $data['meta']['assigned_personnel_details'] = array_map(function($id) {
            $p = get_post(absint($id));
            return $p ? array('id' => absint($id), 'title' => $p->post_title, 'link' => get_permalink(absint($id))) : null;
        }, array_filter($assigned_personnel_ids)); 
        
        $assigned_vehicle_ids = isset($data['meta']['assigned_vehicle_ids']) ? (array)$data['meta']['assigned_vehicle_ids'] : array();
        $data['meta']['assigned_vehicle_details'] = array_map(function($id) {
            $v = get_post(absint($id));
            return $v ? array('id' => absint($id), 'title' => $v->post_title, 'link' => get_permalink(absint($id))) : null;
        }, array_filter($assigned_vehicle_ids));
    }
    
    $response = new WP_REST_Response( $data );
    $base = rest_get_route_for_post_type_items( $post_type );
    if ($base) { 
         $base = sprintf( '%s/%s', $request->get_namespace(), $base );
         $response->add_link( 'collection', rest_url( $base ) );
    }
    $response->add_link( 'self', rest_url( trailingslashit( $base ?: $request->get_route() ) . $post->ID ) ); 
   
    if (post_type_supports($post_type, 'author') && $post->post_author) {
        $response->add_link( 'author', rest_url( 'wp/v2/users/' . $post->post_author ) );
    }

    return $response;
}


// --- Incident Specific ---
function irm_get_incidents_permissions_check(WP_REST_Request $request) { 
    if (!empty($request['id'])) return current_user_can('read_incident', absint($request['id']));
    return current_user_can('edit_incidents'); 
} 

function irm_get_incidents(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'incident',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
        'orderby' => $request->get_param('orderby') ?: 'date', 
        'order' => $request->get_param('order') ?: 'DESC',
        'post_status' => $request->get_param('post_status') ?: 'publish', 
    );

    if ($request->get_param('modified_after') || $request->get_param('modified_before')) {
        if (!$request->get_param('orderby')) { 
            $args['orderby'] = 'modified';
        }
    }

    if ( $tax_status_slug = $request->get_param('incident_status') ) { 
        $args['tax_query'][] = array(
            'taxonomy' => 'incident_status',
            'field' => 'slug', 
            'terms' => $tax_status_slug,
        );
    }

    $date_query = array('relation' => 'AND'); 
    if ( $date_after = $request->get_param('date_after') ) {
        $date_query[] = array('column' => 'post_date_gmt', 'after' => $date_after, 'inclusive' => true); 
    }
    if ( $date_before = $request->get_param('date_before') ) {
        $date_query[] = array('column' => 'post_date_gmt', 'before' => $date_before, 'inclusive' => true);
    }
    if ( $modified_after = $request->get_param('modified_after') ) {
        $date_query[] = array('column' => 'post_modified_gmt', 'after' => $modified_after, 'inclusive' => true);
    }
     if ( $modified_before = $request->get_param('modified_before') ) {
        $date_query[] = array('column' => 'post_modified_gmt', 'before' => $modified_before, 'inclusive' => true);
    }
    
    if (count($date_query) > 1) { 
        $args['date_query'] = $date_query;
    }
    
    $query = new WP_Query( $args );
    $incidents = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $response_obj = irm_prepare_item_for_response( $query->post, $request );
            $incidents[] = $response_obj->get_data(); 
        }
    }
    wp_reset_postdata();

    $total_posts = $query->found_posts;
    $max_pages = $query->max_num_pages;

    $response = new WP_REST_Response( $incidents );
    $response->header( 'X-WP-Total', $total_posts );
    $response->header( 'X-WP-TotalPages', $max_pages );

    return $response;
}

function irm_create_incident_permissions_check(WP_REST_Request $request) { 
    return current_user_can('publish_incidents'); 
}

function irm_create_incident(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $new_incident_args = array(
        'post_type' => 'incident',
        'post_title' => isset($params['title']) ? sanitize_text_field($params['title']) : 'New Incident',
        'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
        'post_status' => isset($params['status']) ? sanitize_key($params['status']) : 'publish', 
        'meta_input' => array(),
    );
    $incident_args_def = irm_get_incident_args(true);
    foreach ($incident_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'incident_status_id', 'incident_status_slug'])) continue; 
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            $new_incident_args['meta_input'][$meta_key] = call_user_func($def['sanitize_callback'], $params[$key]);
        }
    }
    
    $post_id = wp_insert_post( $new_incident_args, true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'incident_creation_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }

    if (isset($params['incident_status_id']) && term_exists(intval($params['incident_status_id']), 'incident_status')) {
        wp_set_post_terms( $post_id, array(intval($params['incident_status_id'])), 'incident_status' );
    } elseif (isset($params['incident_status_slug'])) {
        $term = get_term_by('slug', sanitize_text_field($params['incident_status_slug']), 'incident_status');
        if ($term && !is_wp_error($term)) {
            wp_set_post_terms( $post_id, array($term->term_id), 'incident_status' );
        }
    } else {
        $default_status = get_term_by('slug', 'reported', 'incident_status');
        if ($default_status && !is_wp_error($default_status)) {
            wp_set_post_terms($post_id, array($default_status->term_id), 'incident_status');
        }
    }

    $incident_post = get_post($post_id);
    $response = irm_prepare_item_for_response($incident_post, $request);
    $response->set_status(201); 
    return $response;
}

function irm_get_incident_permissions_check(WP_REST_Request $request) { 
    return current_user_can('read_incident', absint($request['id']));
}
function irm_get_incident(WP_REST_Request $request) {
    $post = get_post( absint($request['id']) );
    if ( ! $post || $post->post_type !== 'incident' ) {
        return new WP_Error( 'rest_post_invalid_id', 'Incident not found.', array( 'status' => 404 ) );
    }
    return irm_prepare_item_for_response($post, $request);
}

function irm_update_incident_permissions_check(WP_REST_Request $request) { 
    return current_user_can('edit_incident', absint($request['id']));
}
function irm_update_incident(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'incident') {
        return new WP_Error('rest_post_invalid_id', 'Incident not found.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $update_args = array( 'ID' => $post_id );

    if (isset($params['title'])) $update_args['post_title'] = sanitize_text_field($params['title']);
    if (isset($params['content'])) $update_args['post_content'] = wp_kses_post($params['content']);
    if (isset($params['status'])) $update_args['post_status'] = sanitize_key($params['status']);
    
    $incident_args_def = irm_get_incident_args(false);
    foreach ($incident_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'incident_status_id', 'incident_status_slug'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}"; 
            update_post_meta($post_id, $meta_key, call_user_func($def['sanitize_callback'], $params[$key]));
        }
    }
    
    if (count($update_args) > 1) {
        $updated_post_id = wp_update_post( $update_args, true );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'incident_update_failed', $updated_post_id->get_error_message(), array( 'status' => 500 ) );
        }
    }

    if (isset($params['incident_status_id'])) {
        wp_set_post_terms( $post_id, array(intval($params['incident_status_id'])), 'incident_status' );
    } elseif (isset($params['incident_status_slug'])) {
         $term = get_term_by('slug', sanitize_text_field($params['incident_status_slug']), 'incident_status');
        if ($term && !is_wp_error($term)) {
            wp_set_post_terms( $post_id, array($term->term_id), 'incident_status' );
        }
    }

    $updated_post = get_post($post_id);
    return irm_prepare_item_for_response($updated_post, $request);
}

function irm_delete_incident_permissions_check(WP_REST_Request $request) { 
    return current_user_can('delete_incident', absint($request['id']));
}
function irm_delete_incident(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'incident') {
        return new WP_Error('rest_post_invalid_id', 'Incident not found.', array('status' => 404));
    }
    $result = wp_trash_post($post_id); 
    if ( ! $result ) {
        return new WP_Error( 'incident_deletion_failed', 'Failed to delete incident.', array( 'status' => 500 ) );
    }
    $trashed_post_data = irm_prepare_item_for_response($result, $request)->get_data();
    return new WP_REST_Response( array( 'message' => 'Incident trashed successfully.', 'previous_data' => $trashed_post_data ), 200 );
}

function irm_get_incident_logs_permissions_check(WP_REST_Request $request) { 
    return current_user_can('read_incident', absint($request['id']));
}
function irm_get_incident_logs(WP_REST_Request $request) {
    $incident_id = absint($request['id']);
    $post = get_post($incident_id);
    if (!$post || $post->post_type !== 'incident') {
        return new WP_Error('rest_post_invalid_id', 'Incident not found for logs.', array('status' => 404));
    }

    $comments_args = array(
        'post_id' => $incident_id,
        'orderby' => 'comment_date_gmt',
        'order' => $request->get_param('order') ?: 'DESC',
        'status' => 'approve',
        'type' => 'incident_log',
        'number' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
    );
    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $comments_args );


    $formatted_logs = array();
    foreach ($comments as $comment) {
        $formatted_logs[] = array(
            'log_id' => $comment->comment_ID,
            'author_name' => $comment->comment_author,
            'author_id' => (int) $comment->user_id,
            'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, $comments_args)),
            'date_gmt' => $comment->comment_date_gmt,
        );
    }
    
    $total_comments = get_comments(array_merge($comments_args, array('count' => true, 'post_id' => $incident_id, 'type' => 'incident_log', 'status' => 'approve', 'number' => 0, 'paged' => 0)));
    $max_pages = ($comments_args['number'] > 0 && $total_comments > 0) ? ceil($total_comments / $comments_args['number']) : 1;
    
    $response = new WP_REST_Response($formatted_logs, 200);
    $response->header( 'X-WP-Total', $total_comments );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_add_incident_log_permissions_check(WP_REST_Request $request) {
    // Changed permission check to use 'add_incident_logs' capability against the specific incident ID.
    return current_user_can('add_incident_logs', absint($request['id']));
}
function irm_add_incident_log(WP_REST_Request $request) {
    $incident_id = absint($request['id']);
    $post = get_post($incident_id);
     if (!$post || $post->post_type !== 'incident') {
        return new WP_Error('rest_post_invalid_id', 'Incident not found to add log.', array('status' => 404));
    }

    $log_entry = $request->get_param('log_entry');
    if (empty($log_entry)) {
        return new WP_Error('rest_missing_callback_param', 'Log entry cannot be empty.', array('status' => 400, 'param' => 'log_entry'));
    }

    $current_user = wp_get_current_user();
    $commentdata = array(
        'comment_post_ID' => $incident_id,
        'comment_author' => $current_user->display_name,
        'comment_author_email' => $current_user->user_email,
        'comment_author_url' => $current_user->user_url,
        'comment_content' => wp_kses_post($log_entry),
        'comment_type' => 'incident_log', 
        'user_id' => $current_user->ID,
        'comment_approved' => 1,
    );

    $comment_id = wp_insert_comment($commentdata);

    if (!$comment_id || is_wp_error($comment_id)) {
        return new WP_Error('log_creation_failed', is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Failed to add log entry.', array('status' => 500));
    }
    $comment = get_comment($comment_id);
    $response_data = array(
        'log_id' => $comment->comment_ID,
        'author_name' => $comment->comment_author,
        'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, array())),
        'date_gmt' => $comment->comment_date_gmt,
        'message' => 'Log added successfully.'
    );
    $response = new WP_REST_Response($response_data, 201);
    $response->add_link('self', rest_url(sprintf('%s/incidents/%d/logs/%d', $request->get_namespace(), $incident_id, $comment_id)));
    $response->add_link('up', rest_url(sprintf('%s/incidents/%d', $request->get_namespace(), $incident_id)));
    return $response;
}

function irm_assign_to_incident_permissions_check(WP_REST_Request $request) {
    return current_user_can('edit_incident', absint($request['id']));
}
function irm_assign_to_incident(WP_REST_Request $request) {
    $incident_id = absint($request['id']);
     $post = get_post($incident_id);
    if (!$post || $post->post_type !== 'incident') {
        return new WP_Error('rest_post_invalid_id', 'Incident not found for assignment.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $updated = false;

    if (isset($params['personnel_ids'])) { 
        if (current_user_can('assign_personnel_to_incidents')) { 
            $personnel_ids = is_array($params['personnel_ids']) ? array_map('absint', $params['personnel_ids']) : array();
            foreach($personnel_ids as $p_id) {
                if ($p_id > 0 && (!get_post($p_id) || get_post_type($p_id) !== 'personnel')) {
                    return new WP_Error('rest_invalid_param', sprintf('Invalid personnel ID: %d', $p_id), array('status' => 400));
                }
            }
            update_post_meta($incident_id, '_assigned_personnel_ids', $personnel_ids);
            $updated = true;
        } else {
             return new WP_Error('rest_cannot_assign_personnel', __('You do not have permission to assign personnel.'), array('status' => 403));
        }
    }

    if (isset($params['vehicle_ids'])) { 
         if (current_user_can('assign_vehicles_to_incidents')) { 
            $vehicle_ids = is_array($params['vehicle_ids']) ? array_map('absint', $params['vehicle_ids']) : array();
            foreach($vehicle_ids as $v_id) {
                if ($v_id > 0 && (!get_post($v_id) || get_post_type($v_id) !== 'vehicle')) {
                     return new WP_Error('rest_invalid_param', sprintf('Invalid vehicle ID: %d', $v_id), array('status' => 400));
                }
            }
            update_post_meta($incident_id, '_assigned_vehicle_ids', $vehicle_ids);
            $updated = true;
        } else {
            return new WP_Error('rest_cannot_assign_vehicles', __('You do not have permission to assign vehicles.'), array('status' => 403));
        }
    }

    if (!$updated && (isset($params['personnel_ids']) || isset($params['vehicle_ids']) )) { 
         return new WP_Error('assignment_failed_or_no_permission', 'No changes made due to invalid data or insufficient permissions.', array('status' => 400));
    } elseif (!$updated) { 
        return new WP_Error('assignment_no_data', 'No personnel or vehicle IDs provided for assignment.', array('status' => 400));
    }
    
    $incident_post = get_post($incident_id);
    return irm_prepare_item_for_response($incident_post, $request);
}


// --- Argument Definition Callbacks (Common List Args) ---
function irm_get_common_list_args() {
    return array(
        'per_page' => array('sanitize_callback' => 'absint', 'default' => 10, 'validate_callback' => 'rest_validate_request_arg'),
        'page' => array('sanitize_callback' => 'absint', 'default' => 1, 'validate_callback' => 'rest_validate_request_arg'),
        'orderby' => array('sanitize_callback' => 'sanitize_key', 'default' => 'date', 'enum' => array('date', 'modified', 'id', 'title', 'rand')),
        'order' => array('sanitize_callback' => 'sanitize_key', 'default' => 'DESC', 'enum' => array('ASC', 'DESC')),
        'post_status' => array('sanitize_callback' => 'sanitize_text_field', 'default' => 'publish'), 
        'modified_before' => array('sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'irm_validate_datetime_format_optional'),
        'modified_after' => array('sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'irm_validate_datetime_format_optional'),
        'date_before' => array('sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'irm_validate_date_format_optional'),
        'date_after' => array('sanitize_callback' => 'sanitize_text_field', 'validate_callback' => 'irm_validate_date_format_optional'),
    );
}

function irm_get_incident_list_args() {
    return array_merge(irm_get_common_list_args(), array(
        'incident_status' => array('sanitize_callback' => 'sanitize_text_field'), 
    ));
}
function irm_get_personnel_list_args() {
     return array_merge(irm_get_common_list_args(), array(
        'rank' => array('sanitize_callback' => 'sanitize_text_field'),
        'shift_id' => array('sanitize_callback' => 'absint'),
    ));
}
function irm_get_vehicle_list_args() {
    return array_merge(irm_get_common_list_args(), array(
        'vehicle_status' => array('sanitize_callback' => 'sanitize_text_field'), 
        'vehicle_type' => array('sanitize_callback' => 'sanitize_text_field'),
    ));
}
function irm_get_equipment_list_args() {
     return array_merge(irm_get_common_list_args(), array(
        'equipment_status' => array('sanitize_callback' => 'sanitize_text_field'), 
        'equipment_type' => array('sanitize_callback' => 'sanitize_text_field'),
        'location' => array('sanitize_callback' => 'sanitize_text_field'),
    ));
}
function irm_get_shift_list_args() {
    return irm_get_common_list_args(); 
}


function irm_get_incident_args( $is_create = true ) { 
    $args = array(
        'title' => array(
            'required' => $is_create, 
            'type' => 'string',
            'description' => __( 'Title of the incident.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'content' => array(
            'type' => 'string',
            'description' => __( 'Detailed description of the incident.', 'incident-response-manager' ),
            'sanitize_callback' => 'wp_kses_post'
        ),
         'status' => array( 
            'type' => 'string',
            'description' => __( 'The status of the post.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_key',
            'enum' => array('publish', 'draft', 'pending', 'private', 'trash')
        ),
        'incident_type' => array(
            'type' => 'string',
            'description' => __( 'Type of the incident (e.g., Fire, Medical).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'location' => array(
            'type' => 'string',
            'description' => __( 'Location of the incident.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'datetime' => array(
            'type' => 'string',
            'description' => __( 'Date and time of the incident (YYYY-MM-DD HH:MM:SS).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_datetime_format'
        ),
        'incident_status_id' => array(
            'type' => 'integer',
            'description' => __( 'Term ID for the incident status.', 'incident-response-manager' ),
            'sanitize_callback' => 'absint'
        ),
         'incident_status_slug' => array(
            'type' => 'string',
            'description' => __( 'Slug for the incident status.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'responding_units' => array(
            'type' => 'string',
            'description' => __( 'Text description of responding units.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_textarea_field'
        ),
    );
    if (!$is_create) { 
        $args['title']['required'] = false;
    }
    return $args;
}

function irm_get_personnel_args( $is_create = true ) {
    $args = array(
        'title' => array(
            'required' => $is_create,
            'type' => 'string',
            'description' => __( 'Name of the personnel.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'content' => array( 
            'type' => 'string',
            'description' => __( 'Biography or notes about the personnel.', 'incident-response-manager' ),
            'sanitize_callback' => 'wp_kses_post'
        ),
        'status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'enum' => array('publish', 'draft', 'pending', 'private', 'trash')),
        'rank' => array(
            'type' => 'string',
            'description' => __( 'Rank of the personnel.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'assigned_shift_id' => array(
            'type' => 'integer',
            'description' => __( 'ID of the assigned shift.', 'incident-response-manager' ),
            'sanitize_callback' => 'absint'
        ),
        'qualifications' => array(
            'type' => 'string',
            'description' => __( 'Qualifications of the personnel.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_textarea_field'
        ),
        'contact_information' => array(
            'type' => 'string',
            'description' => __( 'Contact information for the personnel.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_textarea_field'
        ),
    );
     if (!$is_create) {
        $args['title']['required'] = false;
    }
    return $args;
}

function irm_get_vehicle_args( $is_create = true ) {
    $args = array(
        'title' => array(
            'required' => $is_create,
            'type' => 'string',
            'description' => __( 'Name or identifier of the vehicle.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'content' => array( 
            'type' => 'string',
            'description' => __( 'Additional notes or description for the vehicle.', 'incident-response-manager' ),
            'sanitize_callback' => 'wp_kses_post'
        ),
        'status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'enum' => array('publish', 'draft', 'pending', 'private', 'trash')),
        'vehicle_type' => array(
            'type' => 'string',
            'description' => __( 'Type of the vehicle (e.g., Engine, Ladder, Ambulance).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'call_sign' => array(
            'type' => 'string',
            'description' => __( 'Call sign of the vehicle.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'assigned_personnel' => array( 
            'type' => 'string',
            'description' => __( 'Primary assigned personnel or crew.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'last_maintenance_date' => array(
            'type' => 'string',
            'description' => __( 'Date of last maintenance (YYYY-MM-DD).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_date_format_optional' 
        ),
        'maintenance_schedule' => array( 
            'type' => 'string',
            'description' => __( 'Next scheduled maintenance date (YYYY-MM-DD).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_date_format_optional'
        ),
        'vehicle_status_id' => array( 
            'type' => 'integer',
            'description' => __( 'Term ID for the vehicle status.', 'incident-response-manager' ),
            'sanitize_callback' => 'absint'
        ),
        'vehicle_status_slug' => array( 
            'type' => 'string',
            'description' => __( 'Slug for the vehicle status.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
    );
    if (!$is_create) {
        $args['title']['required'] = false;
    }
    return $args;
}

function irm_get_equipment_args( $is_create = true ) {
    $args = array(
        'title' => array(
            'required' => $is_create,
            'type' => 'string',
            'description' => __( 'Name or identifier of the equipment.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'content' => array( 
            'type' => 'string',
            'description' => __( 'Additional notes or description for the equipment.', 'incident-response-manager' ),
            'sanitize_callback' => 'wp_kses_post'
        ),
        'status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'enum' => array('publish', 'draft', 'pending', 'private', 'trash')),
        'equipment_type' => array(
            'type' => 'string',
            'description' => __( 'Type of the equipment (e.g., SCBA, Hose, Nozzle).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'equipment_id_meta' => array( 
            'type' => 'string',
            'description' => __( 'Unique ID for the equipment item.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'location' => array( 
            'type' => 'string',
            'description' => __( 'Current location of the equipment (e.g., Vehicle ID, Station).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'last_maintenance_date' => array(
            'type' => 'string',
            'description' => __( 'Date of last maintenance or inspection (YYYY-MM-DD).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_date_format_optional'
        ),
        'maintenance_schedule' => array( 
            'type' => 'string',
            'description' => __( 'Next scheduled maintenance or inspection date (YYYY-MM-DD).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_date_format_optional'
        ),
        'equipment_status_id' => array( 
            'type' => 'integer',
            'description' => __( 'Term ID for the equipment status.', 'incident-response-manager' ),
            'sanitize_callback' => 'absint'
        ),
        'equipment_status_slug' => array( 
            'type' => 'string',
            'description' => __( 'Slug for the equipment status.', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
    );
    if (!$is_create) {
        $args['title']['required'] = false;
    }
    return $args;
}

function irm_get_shift_args( $is_create = true ) {
    $args = array(
        'title' => array(
            'required' => $is_create,
            'type' => 'string',
            'description' => __( 'Name of the shift (e.g., A Shift, B Shift).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field'
        ),
        'content' => array( 
            'type' => 'string',
            'description' => __( 'Description of the shift rotation pattern (e.g., 24/48, Kelly Schedule).', 'incident-response-manager' ),
            'sanitize_callback' => 'wp_kses_post'
        ),
        'status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'enum' => array('publish', 'draft', 'pending', 'private', 'trash')),
        'shift_start_time' => array(
            'type' => 'string',
            'description' => __( 'Start time of the shift (HH:MM or HH:MM:SS).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_time_format_optional'
        ),
        'shift_end_time' => array(
            'type' => 'string',
            'description' => __( 'End time of the shift (HH:MM or HH:MM:SS).', 'incident-response-manager' ),
            'sanitize_callback' => 'sanitize_text_field',
            'validate_callback' => 'irm_validate_time_format_optional'
        ),
    );
    if (!$is_create) {
        $args['title']['required'] = false;
    }
    return $args;
}


// --- Personnel CRUD Callbacks ---
function irm_get_personnel_items(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'personnel',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
        'orderby' => $request->get_param('orderby') ?: 'title',
        'order' => $request->get_param('order') ?: 'ASC',
        'post_status' => $request->get_param('status') ?: 'publish',
    );
    if ($request->get_param('modified_after') || $request->get_param('modified_before')) {
        if (!$request->get_param('orderby')) { $args['orderby'] = 'modified'; }
    }
    $date_query = array('relation' => 'AND');
    if ( $date_after = $request->get_param('date_after') ) $date_query[] = array('column' => 'post_date_gmt', 'after' => $date_after, 'inclusive' => true);
    if ( $date_before = $request->get_param('date_before') ) $date_query[] = array('column' => 'post_date_gmt', 'before' => $date_before, 'inclusive' => true);
    if ( $modified_after = $request->get_param('modified_after') ) $date_query[] = array('column' => 'post_modified_gmt', 'after' => $modified_after, 'inclusive' => true);
    if ( $modified_before = $request->get_param('modified_before') ) $date_query[] = array('column' => 'post_modified_gmt', 'before' => $modified_before, 'inclusive' => true);
    if (count($date_query) > 1) $args['date_query'] = $date_query;

    if ($rank = $request->get_param('rank')) {
        $args['meta_query'][] = array(
            'key' => '_rank',
            'value' => sanitize_text_field($rank),
            'compare' => '='
        );
    }
    if ($shift_id = $request->get_param('shift_id')) {
         $args['meta_query'][] = array(
            'key' => '_assigned_shift_id',
            'value' => absint($shift_id),
            'compare' => '='
        );
    }
    if (isset($args['meta_query']) && count($args['meta_query']) > 0) {
        $args['meta_query']['relation'] = 'AND';
    }


    $query = new WP_Query( $args );
    $items = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $response = irm_prepare_item_for_response( $query->post, $request );
            $items[] = $response->get_data();
        }
    }
    wp_reset_postdata();

    $total_posts = $query->found_posts;
    $max_pages = $query->max_num_pages;

    $response = new WP_REST_Response( $items );
    $response->header( 'X-WP-Total', $total_posts );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_create_personnel_item(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $personnel_args_def = irm_get_personnel_args(true);
    
    $new_item_args = array(
        'post_type' => 'personnel',
        'post_title' => isset($params['title']) ? sanitize_text_field($params['title']) : 'New Personnel',
        'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
        'post_status' => isset($params['status']) ? sanitize_key($params['status']) : 'publish',
        'meta_input' => array(),
    );

    foreach ($personnel_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status'])) continue; 
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            $new_item_args['meta_input'][$meta_key] = call_user_func($def['sanitize_callback'], $params[$key]);
        }
    }
    
    $post_id = wp_insert_post( $new_item_args, true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'personnel_creation_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }
    $post_obj = get_post($post_id);
    $response = irm_prepare_item_for_response($post_obj, $request);
    $response->set_status(201);
    return $response;
}

function irm_get_personnel_item(WP_REST_Request $request) {
    $post = get_post( absint($request['id']) );
    if ( ! $post || $post->post_type !== 'personnel' ) {
        return new WP_Error( 'rest_post_invalid_id', 'Personnel not found.', array( 'status' => 404 ) );
    }
    return irm_prepare_item_for_response($post, $request);
}

function irm_update_personnel_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'personnel') {
        return new WP_Error('rest_post_invalid_id', 'Personnel not found.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $personnel_args_def = irm_get_personnel_args(false);
    $update_args = array( 'ID' => $post_id );

    if (isset($params['title'])) $update_args['post_title'] = sanitize_text_field($params['title']);
    if (isset($params['content'])) $update_args['post_content'] = wp_kses_post($params['content']);
    if (isset($params['status'])) $update_args['post_status'] = sanitize_key($params['status']);
    
    foreach ($personnel_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            update_post_meta($post_id, $meta_key, call_user_func($def['sanitize_callback'], $params[$key]));
        }
    }

    if (count($update_args) > 1) { 
        $updated_post_id = wp_update_post( $update_args, true );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'personnel_update_failed', $updated_post_id->get_error_message(), array( 'status' => 500 ) );
        }
    }

    $updated_post = get_post($post_id);
    return irm_prepare_item_for_response($updated_post, $request);
}

function irm_delete_personnel_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'personnel') {
        return new WP_Error('rest_post_invalid_id', 'Personnel not found.', array('status' => 404));
    }

    $result = wp_trash_post($post_id); 
    if ( ! $result ) {
        return new WP_Error( 'personnel_deletion_failed', 'Failed to delete personnel.', array( 'status' => 500 ) );
    }
    $trashed_post_data = irm_prepare_item_for_response($result, $request)->get_data();
    return new WP_REST_Response( array( 'message' => 'Personnel trashed successfully.', 'previous_data' => $trashed_post_data ), 200 );
}

function irm_get_personnel_shifts(WP_REST_Request $request) {
    $personnel_id = absint($request['id']);
    $personnel = get_post($personnel_id);
    if (!$personnel || $personnel->post_type !== 'personnel') {
        return new WP_Error('rest_post_invalid_id', 'Personnel not found.', array('status' => 404));
    }
    
    $assigned_shift_id = get_post_meta($personnel_id, '_assigned_shift_id', true);
    if (empty($assigned_shift_id)) {
        return new WP_REST_Response(array(), 200); 
    }

    $shift_post = get_post(absint($assigned_shift_id));
    if (!$shift_post || $shift_post->post_type !== 'shift') {
        return new WP_Error('rest_invalid_shift_id', 'Assigned shift data not found.', array('status' => 404));
    }
    return irm_prepare_item_for_response($shift_post, $request);
}

// Permissions for Personnel
function irm_get_personnel_permissions_check(WP_REST_Request $request) { 
    if (!empty($request['id'])) return current_user_can('read_personnel', absint($request['id']));
    return current_user_can('edit_personnels'); 
}
function irm_edit_personnel_permissions_check(WP_REST_Request $request) {
    $post_id = isset($request['id']) ? absint($request['id']) : null;
    if ($post_id) return current_user_can('edit_personnel', $post_id);
    return current_user_can('publish_personnels'); 
}
function irm_delete_personnel_permissions_check(WP_REST_Request $request) { return current_user_can('delete_personnel', absint($request['id'])); }


// --- Vehicle Callbacks & Permissions ---
function irm_get_vehicle_items(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'vehicle',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
        'orderby' => $request->get_param('orderby') ?: 'title',
        'order' => $request->get_param('order') ?: 'ASC',
        'post_status' => $request->get_param('status') ?: 'publish',
    );
     if ($request->get_param('modified_after') || $request->get_param('modified_before')) {
        if (!$request->get_param('orderby')) { $args['orderby'] = 'modified'; }
    }
    $date_query = array('relation' => 'AND');
    if ( $date_after = $request->get_param('date_after') ) $date_query[] = array('column' => 'post_date_gmt', 'after' => $date_after, 'inclusive' => true);
    if ( $date_before = $request->get_param('date_before') ) $date_query[] = array('column' => 'post_date_gmt', 'before' => $date_before, 'inclusive' => true);
    if ( $modified_after = $request->get_param('modified_after') ) $date_query[] = array('column' => 'post_modified_gmt', 'after' => $modified_after, 'inclusive' => true);
    if ( $modified_before = $request->get_param('modified_before') ) $date_query[] = array('column' => 'post_modified_gmt', 'before' => $modified_before, 'inclusive' => true);
    if (count($date_query) > 1) $args['date_query'] = $date_query;

    if ( $tax_status_slug = $request->get_param('vehicle_status') ) { 
        $args['tax_query'][] = array(
            'taxonomy' => 'vehicle_status',
            'field' => 'slug',
            'terms' => sanitize_text_field($tax_status_slug),
        );
    }
    if ($vehicle_type = $request->get_param('vehicle_type')) {
        $args['meta_query'][] = array(
            'key' => '_vehicle_type',
            'value' => sanitize_text_field($vehicle_type),
            'compare' => '='
        );
    }
    if (isset($args['meta_query']) && count($args['meta_query']) > 0) {
        $args['meta_query']['relation'] = 'AND';
    }

    $query = new WP_Query( $args );
    $items = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $response = irm_prepare_item_for_response( $query->post, $request );
            $items[] = $response->get_data();
        }
    }
    wp_reset_postdata();
    $total_posts = $query->found_posts;
    $max_pages = $query->max_num_pages;
    $response = new WP_REST_Response( $items );
    $response->header( 'X-WP-Total', $total_posts );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_create_vehicle_item(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $vehicle_args_def = irm_get_vehicle_args(true);
    
    $new_item_args = array(
        'post_type' => 'vehicle',
        'post_title' => isset($params['title']) ? sanitize_text_field($params['title']) : 'New Vehicle',
        'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
        'post_status' => isset($params['status']) ? sanitize_key($params['status']) : 'publish',
        'meta_input' => array(),
    );

    foreach ($vehicle_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'vehicle_status_id', 'vehicle_status_slug'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            $new_item_args['meta_input'][$meta_key] = call_user_func($def['sanitize_callback'], $params[$key]);
        }
    }
    
    $post_id = wp_insert_post( $new_item_args, true );
    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'vehicle_creation_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }

    if (isset($params['vehicle_status_id']) && term_exists(intval($params['vehicle_status_id']), 'vehicle_status')) {
        wp_set_post_terms( $post_id, array(intval($params['vehicle_status_id'])), 'vehicle_status' );
    } elseif (isset($params['vehicle_status_slug'])) {
        $term = get_term_by('slug', sanitize_text_field($params['vehicle_status_slug']), 'vehicle_status');
        if ($term && !is_wp_error($term)) wp_set_post_terms( $post_id, array($term->term_id), 'vehicle_status' );
    } else { 
        $default_status = get_term_by('slug', 'available', 'vehicle_status');
        if ($default_status && !is_wp_error($default_status)) wp_set_post_terms($post_id, array($default_status->term_id), 'vehicle_status');
    }

    $post_obj = get_post($post_id);
    $response = irm_prepare_item_for_response($post_obj, $request);
    $response->set_status(201);
    return $response;
}

function irm_get_vehicle_item(WP_REST_Request $request) {
    $post = get_post( absint($request['id']) );
    if ( ! $post || $post->post_type !== 'vehicle' ) {
        return new WP_Error( 'rest_post_invalid_id', 'Vehicle not found.', array( 'status' => 404 ) );
    }
    return irm_prepare_item_for_response($post, $request);
}

function irm_update_vehicle_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'vehicle') {
        return new WP_Error('rest_post_invalid_id', 'Vehicle not found.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $vehicle_args_def = irm_get_vehicle_args(false);
    $update_args = array( 'ID' => $post_id );

    if (isset($params['title'])) $update_args['post_title'] = sanitize_text_field($params['title']);
    if (isset($params['content'])) $update_args['post_content'] = wp_kses_post($params['content']);
    if (isset($params['status'])) $update_args['post_status'] = sanitize_key($params['status']);
    
    foreach ($vehicle_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'vehicle_status_id', 'vehicle_status_slug'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            update_post_meta($post_id, $meta_key, call_user_func($def['sanitize_callback'], $params[$key]));
        }
    }
        
    if (count($update_args) > 1) { 
        $updated_post_id = wp_update_post( $update_args, true );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'vehicle_update_failed', $updated_post_id->get_error_message(), array( 'status' => 500 ) );
        }
    }
    
    if (isset($params['vehicle_status_id'])) {
        wp_set_post_terms( $post_id, array(intval($params['vehicle_status_id'])), 'vehicle_status' );
    } elseif (isset($params['vehicle_status_slug'])) {
         $term = get_term_by('slug', sanitize_text_field($params['vehicle_status_slug']), 'vehicle_status');
        if ($term && !is_wp_error($term)) wp_set_post_terms( $post_id, array($term->term_id), 'vehicle_status' );
    }

    $updated_post = get_post($post_id);
    return irm_prepare_item_for_response($updated_post, $request);
}

function irm_delete_vehicle_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'vehicle') {
        return new WP_Error('rest_post_invalid_id', 'Vehicle not found.', array('status' => 404));
    }
    $result = wp_trash_post($post_id); 
    if ( ! $result ) {
        return new WP_Error( 'vehicle_deletion_failed', 'Failed to delete vehicle.', array( 'status' => 500 ) );
    }
    $trashed_post_data = irm_prepare_item_for_response($result, $request)->get_data();
    return new WP_REST_Response( array( 'message' => 'Vehicle trashed successfully.', 'previous_data' => $trashed_post_data ), 200 );
}

function irm_get_vehicle_maintenance_logs_permissions_check(WP_REST_Request $request) {
    return current_user_can('edit_vehicle', absint($request['id']));
}
function irm_get_vehicle_maintenance_logs(WP_REST_Request $request) {
    $item_id = absint($request['id']);
    $post = get_post($item_id);
    if (!$post || $post->post_type !== 'vehicle') {
        return new WP_Error('rest_post_invalid_id', 'Vehicle not found for logs.', array('status' => 404));
    }
    $comments_args = array(
        'post_id' => $item_id, 
        'orderby' => 'comment_date_gmt', 
        'order' => $request->get_param('order') ?: 'DESC', 
        'status' => 'approve', 
        'type' => 'vehicle_maintenance_log',
        'number' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
    );
    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $comments_args );
    
    $formatted_logs = array();
    foreach ($comments as $comment) {
        $formatted_logs[] = array(
            'log_id' => $comment->comment_ID, 'author_name' => $comment->comment_author,
            'author_id' => (int)$comment->user_id, 'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, $comments_args)),
            'date_gmt' => $comment->comment_date_gmt,
        );
    }
    $total_comments = get_comments(array_merge($comments_args, array('count' => true, 'post_id' => $item_id, 'type' => 'vehicle_maintenance_log', 'status' => 'approve', 'number' => 0, 'paged' => 0)));
    $max_pages = ($comments_args['number'] > 0 && $total_comments > 0) ? ceil($total_comments / $comments_args['number']) : 1;

    $response = new WP_REST_Response($formatted_logs, 200);
    $response->header( 'X-WP-Total', $total_comments );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_add_vehicle_maintenance_log_permissions_check(WP_REST_Request $request) {
     return current_user_can('edit_vehicle', absint($request['id']));
}
function irm_add_vehicle_maintenance_log(WP_REST_Request $request) {
    $item_id = absint($request['id']);
    $post = get_post($item_id);
    if (!$post || $post->post_type !== 'vehicle') {
        return new WP_Error('rest_post_invalid_id', 'Vehicle not found to add log.', array('status' => 404));
    }
    $log_entry = $request->get_param('log_entry');
    if (empty($log_entry)) {
        return new WP_Error('rest_missing_callback_param', 'Log entry cannot be empty.', array('status' => 400, 'param' => 'log_entry'));
    }
    $current_user = wp_get_current_user();
    $commentdata = array(
        'comment_post_ID' => $item_id, 'comment_author' => $current_user->display_name,
        'comment_author_email' => $current_user->user_email, 'comment_author_url' => $current_user->user_url,
        'comment_content' => wp_kses_post($log_entry), 'comment_type' => 'vehicle_maintenance_log',
        'user_id' => $current_user->ID, 'comment_approved' => 1,
    );
    $comment_id = wp_insert_comment($commentdata);
    if (!$comment_id || is_wp_error($comment_id)) {
        return new WP_Error('log_creation_failed', is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Failed to add log entry.', array('status' => 500));
    }
    $comment = get_comment($comment_id);
    $response_data = array(
        'log_id' => $comment->comment_ID, 'author_name' => $comment->comment_author,
        'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, array())), 
        'date_gmt' => $comment->comment_date_gmt,
        'message' => 'Log added successfully.'
    );
    $response = new WP_REST_Response($response_data, 201);
    $response->add_link('self', rest_url(sprintf('%s/vehicles/%d/logs/%d', $request->get_namespace(), $item_id, $comment_id)));
    $response->add_link('up', rest_url(sprintf('%s/vehicles/%d', $request->get_namespace(), $item_id)));
    return $response;
}

// Permission callbacks for Vehicle CPT
function irm_get_vehicles_permissions_check(WP_REST_Request $request) { 
    if (!empty($request['id'])) return current_user_can('read_vehicle', absint($request['id']));
    return current_user_can('edit_vehicles'); 
}
function irm_edit_vehicles_permissions_check(WP_REST_Request $request) { 
    $post_id = isset($request['id']) ? absint($request['id']) : null;
    if ($post_id) return current_user_can('edit_vehicle', $post_id);
    return current_user_can('publish_vehicles');
}
function irm_delete_vehicles_permissions_check(WP_REST_Request $request) { return current_user_can('delete_vehicle', absint($request['id'])); }


// --- Equipment Callbacks & Permissions ---
function irm_get_equipment_items(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'equipment',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
        'orderby' => $request->get_param('orderby') ?: 'title',
        'order' => $request->get_param('order') ?: 'ASC',
        'post_status' => $request->get_param('status') ?: 'publish',
    );
    if ($request->get_param('modified_after') || $request->get_param('modified_before')) {
        if (!$request->get_param('orderby')) { $args['orderby'] = 'modified'; }
    }
    $date_query = array('relation' => 'AND');
    if ( $date_after = $request->get_param('date_after') ) $date_query[] = array('column' => 'post_date_gmt', 'after' => $date_after, 'inclusive' => true);
    if ( $date_before = $request->get_param('date_before') ) $date_query[] = array('column' => 'post_date_gmt', 'before' => $date_before, 'inclusive' => true);
    if ( $modified_after = $request->get_param('modified_after') ) $date_query[] = array('column' => 'post_modified_gmt', 'after' => $modified_after, 'inclusive' => true);
    if ( $modified_before = $request->get_param('modified_before') ) $date_query[] = array('column' => 'post_modified_gmt', 'before' => $modified_before, 'inclusive' => true);
    if (count($date_query) > 1) $args['date_query'] = $date_query;

    if ( $tax_status_slug = $request->get_param('equipment_status') ) { 
        $args['tax_query'][] = array(
            'taxonomy' => 'equipment_status',
            'field' => 'slug',
            'terms' => sanitize_text_field($tax_status_slug),
        );
    }
    if ($equipment_type = $request->get_param('equipment_type')) {
        $args['meta_query'][] = array(
            'key' => '_equipment_type',
            'value' => sanitize_text_field($equipment_type),
            'compare' => '='
        );
    }
     if ($location = $request->get_param('location')) {
        $args['meta_query'][] = array(
            'key' => '_location',
            'value' => sanitize_text_field($location),
            'compare' => 'LIKE' 
        );
    }
     if (isset($args['meta_query']) && count($args['meta_query']) > 0) {
        $args['meta_query']['relation'] = 'AND';
    }

    $query = new WP_Query( $args );
    $items = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $response = irm_prepare_item_for_response( $query->post, $request );
            $items[] = $response->get_data();
        }
    }
    wp_reset_postdata();
    $total_posts = $query->found_posts;
    $max_pages = $query->max_num_pages;
    $response = new WP_REST_Response( $items );
    $response->header( 'X-WP-Total', $total_posts );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_create_equipment_item(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $equipment_args_def = irm_get_equipment_args(true);
    
    $new_item_args = array(
        'post_type' => 'equipment',
        'post_title' => isset($params['title']) ? sanitize_text_field($params['title']) : 'New Equipment',
        'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '',
        'post_status' => isset($params['status']) ? sanitize_key($params['status']) : 'publish',
        'meta_input' => array(),
    );

    foreach ($equipment_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'equipment_status_id', 'equipment_status_slug', 'equipment_id_meta'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            $new_item_args['meta_input'][$meta_key] = call_user_func($def['sanitize_callback'], $params[$key]);
        }
    }
     if (isset($params['equipment_id_meta'])) { 
        $new_item_args['meta_input']['_equipment_id'] = sanitize_text_field($params['equipment_id_meta']);
    }
    
    $post_id = wp_insert_post( $new_item_args, true );
    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'equipment_creation_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }

    if (isset($params['equipment_status_id']) && term_exists(intval($params['equipment_status_id']), 'equipment_status')) {
        wp_set_post_terms( $post_id, array(intval($params['equipment_status_id'])), 'equipment_status' );
    } elseif (isset($params['equipment_status_slug'])) {
        $term = get_term_by('slug', sanitize_text_field($params['equipment_status_slug']), 'equipment_status');
        if ($term && !is_wp_error($term)) wp_set_post_terms( $post_id, array($term->term_id), 'equipment_status' );
    } else {
        $default_status = get_term_by('slug', 'available', 'equipment_status');
        if ($default_status && !is_wp_error($default_status)) wp_set_post_terms($post_id, array($default_status->term_id), 'equipment_status');
    }

    $post_obj = get_post($post_id);
    $response = irm_prepare_item_for_response($post_obj, $request);
    $response->set_status(201);
    return $response;
}

function irm_get_equipment_item(WP_REST_Request $request) {
    $post = get_post( absint($request['id']) );
    if ( ! $post || $post->post_type !== 'equipment' ) {
        return new WP_Error( 'rest_post_invalid_id', 'Equipment not found.', array( 'status' => 404 ) );
    }
    return irm_prepare_item_for_response($post, $request);
}

function irm_update_equipment_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'equipment') {
        return new WP_Error('rest_post_invalid_id', 'Equipment not found.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $equipment_args_def = irm_get_equipment_args(false);
    $update_args = array( 'ID' => $post_id );

    if (isset($params['title'])) $update_args['post_title'] = sanitize_text_field($params['title']);
    if (isset($params['content'])) $update_args['post_content'] = wp_kses_post($params['content']);
    if (isset($params['status'])) $update_args['post_status'] = sanitize_key($params['status']);
    
    foreach ($equipment_args_def as $key => $def) {
        if (in_array($key, ['title', 'content', 'status', 'equipment_status_id', 'equipment_status_slug', 'equipment_id_meta'])) continue;
        if (isset($params[$key])) {
            $meta_key = (strpos($key, '_') === 0) ? $key : "_{$key}";
            update_post_meta($post_id, $meta_key, call_user_func($def['sanitize_callback'], $params[$key]));
        }
    }
    if (isset($params['equipment_id_meta'])) { 
        update_post_meta($post_id, '_equipment_id', sanitize_text_field($params['equipment_id_meta']));
    }
        
    if (count($update_args) > 1) {
        $updated_post_id = wp_update_post( $update_args, true );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'equipment_update_failed', $updated_post_id->get_error_message(), array( 'status' => 500 ) );
        }
    }
    
    if (isset($params['equipment_status_id'])) {
        wp_set_post_terms( $post_id, array(intval($params['equipment_status_id'])), 'equipment_status' );
    } elseif (isset($params['equipment_status_slug'])) {
         $term = get_term_by('slug', sanitize_text_field($params['equipment_status_slug']), 'equipment_status');
        if ($term && !is_wp_error($term)) wp_set_post_terms( $post_id, array($term->term_id), 'equipment_status' );
    }

    $updated_post = get_post($post_id);
    return irm_prepare_item_for_response($updated_post, $request);
}

function irm_delete_equipment_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'equipment') {
        return new WP_Error('rest_post_invalid_id', 'Equipment not found.', array('status' => 404));
    }
    $result = wp_trash_post($post_id); 
    if ( ! $result ) {
        return new WP_Error( 'equipment_deletion_failed', 'Failed to delete equipment.', array( 'status' => 500 ) );
    }
    $trashed_post_data = irm_prepare_item_for_response($result, $request)->get_data();
    return new WP_REST_Response( array( 'message' => 'Equipment trashed successfully.', 'previous_data' => $trashed_post_data ), 200 );
}

function irm_get_equipment_maintenance_logs_permissions_check(WP_REST_Request $request) {
    return current_user_can('edit_equipment', absint($request['id']));
}
function irm_get_equipment_maintenance_logs(WP_REST_Request $request) {
    $item_id = absint($request['id']);
    $post = get_post($item_id);
    if (!$post || $post->post_type !== 'equipment') {
        return new WP_Error('rest_post_invalid_id', 'Equipment not found for logs.', array('status' => 404));
    }
    $comments_args = array(
        'post_id' => $item_id, 
        'orderby' => 'comment_date_gmt', 
        'order' => $request->get_param('order') ?: 'DESC', 
        'status' => 'approve', 
        'type' => 'equipment_maintenance_log',
        'number' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
    );
    $comments_query = new WP_Comment_Query;
    $comments = $comments_query->query( $comments_args );

    $formatted_logs = array();
    foreach ($comments as $comment) {
        $formatted_logs[] = array(
            'log_id' => $comment->comment_ID, 'author_name' => $comment->comment_author,
            'author_id' => (int)$comment->user_id, 'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, $comments_args)),
            'date_gmt' => $comment->comment_date_gmt,
        );
    }
    $total_comments = get_comments(array_merge($comments_args, array('count' => true, 'post_id' => $item_id, 'type' => 'equipment_maintenance_log', 'status' => 'approve', 'number' => 0, 'paged' => 0)));
    $max_pages = ($comments_args['number'] > 0 && $total_comments > 0) ? ceil($total_comments / $comments_args['number']) : 1;
    
    $response = new WP_REST_Response($formatted_logs, 200);
    $response->header( 'X-WP-Total', $total_comments );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_add_equipment_maintenance_log_permissions_check(WP_REST_Request $request) {
     return current_user_can('edit_equipment', absint($request['id']));
}
function irm_add_equipment_maintenance_log(WP_REST_Request $request) {
    $item_id = absint($request['id']);
     $post = get_post($item_id);
    if (!$post || $post->post_type !== 'equipment') {
        return new WP_Error('rest_post_invalid_id', 'Equipment not found to add log.', array('status' => 404));
    }
    $log_entry = $request->get_param('log_entry');
    if (empty($log_entry)) {
        return new WP_Error('rest_missing_callback_param', 'Log entry cannot be empty.', array('status' => 400, 'param' => 'log_entry'));
    }
    $current_user = wp_get_current_user();
    $commentdata = array(
        'comment_post_ID' => $item_id, 'comment_author' => $current_user->display_name,
        'comment_author_email' => $current_user->user_email, 'comment_author_url' => $current_user->user_url,
        'comment_content' => wp_kses_post($log_entry), 'comment_type' => 'equipment_maintenance_log',
        'user_id' => $current_user->ID, 'comment_approved' => 1,
    );
    $comment_id = wp_insert_comment($commentdata);
    if (!$comment_id || is_wp_error($comment_id)) {
        return new WP_Error('log_creation_failed', is_wp_error($comment_id) ? $comment_id->get_error_message() : 'Failed to add log entry.', array('status' => 500));
    }
    $comment = get_comment($comment_id);
    $response_data = array(
        'log_id' => $comment->comment_ID, 'author_name' => $comment->comment_author,
        'content' => array('rendered' => apply_filters('comment_text', $comment->comment_content, $comment, array())), 
        'date_gmt' => $comment->comment_date_gmt,
        'message' => 'Log added successfully.'
    );
    $response = new WP_REST_Response($response_data, 201);
    $response->add_link('self', rest_url(sprintf('%s/equipment/%d/logs/%d', $request->get_namespace(), $item_id, $comment_id)));
    $response->add_link('up', rest_url(sprintf('%s/equipment/%d', $request->get_namespace(), $item_id)));
    return $response;
}

// Permission callbacks for Equipment CPT
function irm_get_equipment_permissions_check(WP_REST_Request $request) { 
    if (!empty($request['id'])) return current_user_can('read_equipment', absint($request['id']));
    return current_user_can('edit_equipments'); 
}
function irm_edit_equipment_permissions_check(WP_REST_Request $request) { 
    $post_id = isset($request['id']) ? absint($request['id']) : null;
    if ($post_id) return current_user_can('edit_equipment', $post_id);
    return current_user_can('publish_equipments');
}
function irm_delete_equipment_permissions_check(WP_REST_Request $request) { return current_user_can('delete_equipment', absint($request['id'])); }


// --- Shift Callbacks & Permissions ---
function irm_get_shift_items(WP_REST_Request $request) {
    $args = array(
        'post_type' => 'shift',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1,
        'orderby' => $request->get_param('orderby') ?: 'title',
        'order' => $request->get_param('order') ?: 'ASC',
        'post_status' => $request->get_param('status') ?: 'publish',
    );
    if ($request->get_param('modified_after') || $request->get_param('modified_before')) {
        if (!$request->get_param('orderby')) { $args['orderby'] = 'modified'; }
    }
    $date_query = array('relation' => 'AND');
    if ( $date_after = $request->get_param('date_after') ) $date_query[] = array('column' => 'post_date_gmt', 'after' => $date_after, 'inclusive' => true);
    if ( $date_before = $request->get_param('date_before') ) $date_query[] = array('column' => 'post_date_gmt', 'before' => $date_before, 'inclusive' => true);
    if ( $modified_after = $request->get_param('modified_after') ) $date_query[] = array('column' => 'post_modified_gmt', 'after' => $modified_after, 'inclusive' => true);
    if ( $modified_before = $request->get_param('modified_before') ) $date_query[] = array('column' => 'post_modified_gmt', 'before' => $modified_before, 'inclusive' => true);
    if (count($date_query) > 1) $args['date_query'] = $date_query;


    $query = new WP_Query( $args );
    $items = array();
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $response = irm_prepare_item_for_response( $query->post, $request );
            $items[] = $response->get_data();
        }
    }
    wp_reset_postdata();
    $total_posts = $query->found_posts;
    $max_pages = $query->max_num_pages;
    $response = new WP_REST_Response( $items );
    $response->header( 'X-WP-Total', $total_posts );
    $response->header( 'X-WP-TotalPages', $max_pages );
    return $response;
}

function irm_create_shift_item(WP_REST_Request $request) {
    $params = $request->get_json_params();
    
    $new_item_args = array(
        'post_type' => 'shift',
        'post_title' => isset($params['title']) ? sanitize_text_field($params['title']) : 'New Shift',
        'post_content' => isset($params['content']) ? wp_kses_post($params['content']) : '', 
        'post_status' => isset($params['status']) ? sanitize_key($params['status']) : 'publish',
        'meta_input' => array(),
    );

    if (isset($params['shift_start_time'])) $new_item_args['meta_input']['_shift_start_time'] = sanitize_text_field($params['shift_start_time']);
    if (isset($params['shift_end_time'])) $new_item_args['meta_input']['_shift_end_time'] = sanitize_text_field($params['shift_end_time']);
    
    $post_id = wp_insert_post( $new_item_args, true );
    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'shift_creation_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }
    $post_obj = get_post($post_id);
    $response = irm_prepare_item_for_response($post_obj, $request);
    $response->set_status(201);
    return $response;
}

function irm_get_shift_item(WP_REST_Request $request) {
    $post = get_post( absint($request['id']) );
    if ( ! $post || $post->post_type !== 'shift' ) {
        return new WP_Error( 'rest_post_invalid_id', 'Shift not found.', array( 'status' => 404 ) );
    }
    return irm_prepare_item_for_response($post, $request);
}

function irm_update_shift_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'shift') {
        return new WP_Error('rest_post_invalid_id', 'Shift not found.', array('status' => 404));
    }

    $params = $request->get_json_params();
    $update_args = array( 'ID' => $post_id );

    if (isset($params['title'])) $update_args['post_title'] = sanitize_text_field($params['title']);
    if (isset($params['content'])) $update_args['post_content'] = wp_kses_post($params['content']);
    if (isset($params['status'])) $update_args['post_status'] = sanitize_key($params['status']);
    
    if (isset($params['shift_start_time'])) update_post_meta($post_id, '_shift_start_time', sanitize_text_field($params['shift_start_time']));
    if (isset($params['shift_end_time'])) update_post_meta($post_id, '_shift_end_time', sanitize_text_field($params['shift_end_time']));
        
    if (count($update_args) > 1) {
        $updated_post_id = wp_update_post( $update_args, true );
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error( 'shift_update_failed', $updated_post_id->get_error_message(), array( 'status' => 500 ) );
        }
    }
    
    $updated_post = get_post($post_id);
    return irm_prepare_item_for_response($updated_post, $request);
}

function irm_delete_shift_item(WP_REST_Request $request) {
    $post_id = absint($request['id']);
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'shift') {
        return new WP_Error('rest_post_invalid_id', 'Shift not found.', array('status' => 404));
    }
    $result = wp_trash_post($post_id); 
    if ( ! $result ) {
        return new WP_Error( 'shift_deletion_failed', 'Failed to delete shift.', array( 'status' => 500 ) );
    }
    $trashed_post_data = irm_prepare_item_for_response($result, $request)->get_data();
    return new WP_REST_Response( array( 'message' => 'Shift trashed successfully.', 'previous_data' => $trashed_post_data ), 200 );
}

// Permission callbacks for Shift CPT
function irm_get_shifts_permissions_check(WP_REST_Request $request) { 
    if (!empty($request['id'])) return current_user_can('read_shift', absint($request['id']));
    return current_user_can('edit_shifts'); 
}
function irm_edit_shifts_permissions_check(WP_REST_Request $request) { 
    $post_id = isset($request['id']) ? absint($request['id']) : null;
    if ($post_id) return current_user_can('edit_shift', $post_id);
    return current_user_can('publish_shifts'); 
}
function irm_delete_shifts_permissions_check(WP_REST_Request $request) { return current_user_can('delete_shift', absint($request['id'])); } 


// --- Duty Roster Callbacks & Permissions ---
function irm_view_roster_permissions_check(WP_REST_Request $request) { return current_user_can('read_shifts'); } 
function irm_get_duty_roster(WP_REST_Request $request) { 
    $shift_id_filter = $request->get_param('shift_id');
    $date_filter_param = $request->get_param('date'); 
    $target_date = null;

    if ($date_filter_param) {
        try {
            $target_date = new DateTime($date_filter_param, wp_timezone());
        } catch (Exception $e) {
            return new WP_Error('rest_invalid_param', __('Invalid date format for roster filter. Use YYYY-MM-DD.', 'incident-response-manager'), array('status' => 400, 'param' => 'date'));
        }
    } else {
        $target_date = new DateTime('now', wp_timezone());
    }

    $personnel_args = array(
        'post_type' => 'personnel',
        'posts_per_page' => -1,
        'meta_query' => array('relation' => 'AND'),
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    );
    if ($shift_id_filter) {
        $personnel_args['meta_query'][] = array(
            'key' => '_assigned_shift_id',
            'value' => $shift_id_filter,
            'compare' => '='
        );
    }
    $personnel_query = new WP_Query($personnel_args);
    $roster = array();
    if ($personnel_query->have_posts()) {
        while($personnel_query->have_posts()) {
            $personnel_query->the_post();
            $person_id = get_the_ID();
            $person_prepared_response = irm_prepare_item_for_response(get_post($person_id), $request);
            if (is_wp_error($person_prepared_response)) continue; 
            $person_data_item = $person_prepared_response->get_data();
            
            $person_data = array(
                'personnel' => $person_data_item, 
            );
            
            $assigned_shift_id = get_post_meta($person_id, '_assigned_shift_id', true);
            if ($assigned_shift_id) {
                $shift_post = get_post(absint($assigned_shift_id));
                if ($shift_post && $shift_post->post_type === 'shift') {
                    $shift_prepared_response = irm_prepare_item_for_response($shift_post, $request);
                    if (!is_wp_error($shift_prepared_response)) {
                        $person_data['assigned_shift'] = $shift_prepared_response->get_data();
                    
                        $is_on_duty = false; 
                        $shift_start_time_str = isset($person_data['assigned_shift']['meta']['shift_start_time']) ? $person_data['assigned_shift']['meta']['shift_start_time'] : null;
                        $shift_end_time_str = isset($person_data['assigned_shift']['meta']['shift_end_time']) ? $person_data['assigned_shift']['meta']['shift_end_time'] : null;

                        if ($shift_start_time_str && $shift_end_time_str) {
                             try {
                                $shift_start_dt = new DateTime($target_date->format('Y-m-d') . ' ' . $shift_start_time_str, wp_timezone());
                                $shift_end_dt = new DateTime($target_date->format('Y-m-d') . ' ' . $shift_end_time_str, wp_timezone());

                                if ($shift_end_dt <= $shift_start_dt) { 
                                    $shift_end_dt->modify('+1 day');
                                }
                                
                                $current_target_time_for_comparison = new DateTime($target_date->format('Y-m-d H:i:s'), wp_timezone());

                                if ($current_target_time_for_comparison >= $shift_start_dt && $current_target_time_for_comparison < $shift_end_dt) {
                                    $is_on_duty = true;
                                } else if ($shift_end_dt < $shift_start_dt) { 
                                     // This logic needs to be more robust for multi-day spanning shifts and checking across midnight accurately.
                                     // The current logic is a simplified check based on time of day.
                                }

                            } catch (Exception $e) { /* Time string invalid */ }
                        }
                         $person_data['assigned_shift']['currently_on_duty_approximation'] = $is_on_duty;
                    }
                }
            }
            $roster[] = $person_data;
        }
    }
    wp_reset_postdata();
    return new WP_REST_Response($roster, 200);
}


// --- Sanitization Callbacks ---
function irm_sanitize_integer_array( $param, $request, $key ) {
    if ( is_string( $param ) ) { 
        $param = explode( ',', $param );
    }
    return is_array( $param ) ? array_map( 'absint', $param ) : array();
}

// --- Validation Callbacks ---
function irm_validate_datetime_format( $param, $request, $key ) {
    if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    $pattern = '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/'; 
    if ( ! preg_match( $pattern, $param, $matches ) ) {
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid datetime format (YYYY-MM-DD HH:MM or YYYY-MM-DD HH:MM:SS).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    }
    $parse_param = $param;
    if (isset($matches[3]) && $matches[3] === '') { 
        $parse_param = $param . ':00';
    } elseif (!isset($matches[3])) { 
         $parse_param = $param . ':00';
    }

    $datetime = date_parse_from_format('Y-m-d H:i:s', str_replace('T', ' ', $parse_param));

    if ($datetime['error_count'] > 0 || $datetime['warning_count'] > 0 || !checkdate($datetime['month'], $datetime['day'], $datetime['year']) || !($datetime['hour'] !== false && $datetime['minute'] !== false && $datetime['second'] !== false)) {
         return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid calendar date/time.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    }
    return true;
}
function irm_validate_datetime_format_optional( $param, $request, $key ) {
    if (empty($param)) return true; 
    return irm_validate_datetime_format($param, $request, $key);
}


function irm_validate_date_format( $param, $request, $key ) {
    if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    $pattern = '/^\d{4}-\d{2}-\d{2}$/';
    if ( ! preg_match( $pattern, $param ) ) {
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid date format (YYYY-MM-DD).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    }
    $dateparts = explode('-', $param);
    if ( !checkdate( (int)$dateparts[1], (int)$dateparts[2], (int)$dateparts[0]) ) {
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid calendar date.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    }
    return true;
}
function irm_validate_date_format_optional( $param, $request, $key ) {
    if (empty($param)) return true; 
    return irm_validate_date_format($param, $request, $key);
}


function irm_validate_time_format( $param, $request, $key ) {
     if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    $pattern = '/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/'; 
    if ( ! preg_match( $pattern, $param ) ) {
        return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid time format (HH:MM or HH:MM:SS).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
    }
    return true;
}
function irm_validate_time_format_optional( $param, $request, $key ) {
    if (empty($param)) return true; 
    return irm_validate_time_format($param, $request, $key);
}

?>
