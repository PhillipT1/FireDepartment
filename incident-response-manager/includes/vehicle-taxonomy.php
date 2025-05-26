<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Vehicle Status Taxonomy
 */
function irm_register_vehicle_status_taxonomy() {
    $labels = array(
        'name'              => _x( 'Vehicle Statuses', 'taxonomy general name', 'incident-response-manager' ),
        'singular_name'     => _x( 'Vehicle Status', 'taxonomy singular name', 'incident-response-manager' ),
        'search_items'      => __( 'Search Vehicle Statuses', 'incident-response-manager' ),
        'all_items'         => __( 'All Vehicle Statuses', 'incident-response-manager' ),
        'parent_item'       => __( 'Parent Vehicle Status', 'incident-response-manager' ),
        'parent_item_colon' => __( 'Parent Vehicle Status:', 'incident-response-manager' ),
        'edit_item'         => __( 'Edit Vehicle Status', 'incident-response-manager' ),
        'update_item'       => __( 'Update Vehicle Status', 'incident-response-manager' ),
        'add_new_item'      => __( 'Add New Vehicle Status', 'incident-response-manager' ),
        'new_item_name'     => __( 'New Vehicle Status Name', 'incident-response-manager' ),
        'menu_name'         => __( 'Vehicle Status', 'incident-response-manager' ),
    );

    $args = array(
        'hierarchical'      => false, // Non-hierarchical like post tags
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'vehicle-status' ),
        'public'            => true, // Can be false if only for admin use
        'capability_type'   => 'vehicle_status_term',
        'capabilities'      => array(
            'manage_terms'  => 'manage_vehicle_statuses',
            'edit_terms'    => 'edit_vehicle_statuses',
            'delete_terms'  => 'delete_vehicle_statuses',
            'assign_terms'  => 'assign_vehicle_statuses',
        ),
    );

    register_taxonomy( 'vehicle_status', array( 'vehicle' ), $args );

    // Pre-defined terms
    $terms = array(
        'Available',
        'Out of Service',
        'In Maintenance',
        'Deployed'
    );

    foreach ( $terms as $term_name ) {
        if ( ! term_exists( $term_name, 'vehicle_status' ) ) {
            wp_insert_term( $term_name, 'vehicle_status' );
        }
    }
}
add_action( 'init', 'irm_register_vehicle_status_taxonomy' );

?>
