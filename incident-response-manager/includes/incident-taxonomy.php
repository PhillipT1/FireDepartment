<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Incident Status Taxonomy
 */
function irm_register_incident_status_taxonomy() {
    $labels = array(
        'name'              => _x( 'Incident Statuses', 'taxonomy general name', 'incident-response-manager' ),
        'singular_name'     => _x( 'Incident Status', 'taxonomy singular name', 'incident-response-manager' ),
        'search_items'      => __( 'Search Incident Statuses', 'incident-response-manager' ),
        'all_items'         => __( 'All Incident Statuses', 'incident-response-manager' ),
        'parent_item'       => __( 'Parent Incident Status', 'incident-response-manager' ),
        'parent_item_colon' => __( 'Parent Incident Status:', 'incident-response-manager' ),
        'edit_item'         => __( 'Edit Incident Status', 'incident-response-manager' ),
        'update_item'       => __( 'Update Incident Status', 'incident-response-manager' ),
        'add_new_item'      => __( 'Add New Incident Status', 'incident-response-manager' ),
        'new_item_name'     => __( 'New Incident Status Name', 'incident-response-manager' ),
        'menu_name'         => __( 'Incident Status', 'incident-response-manager' ),
    );

    $args = array(
        'hierarchical'      => true, // Set to true if you want parent-child relationships (like categories)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'incident-status' ),
        'public'            => true,
        'capability_type'   => 'incident_status_term', // Custom capability type for this taxonomy
        'capabilities'      => array(
            'manage_terms'  => 'manage_incident_statuses', // Primitive cap
            'edit_terms'    => 'edit_incident_statuses',   // Primitive cap
            'delete_terms'  => 'delete_incident_statuses', // Primitive cap
            'assign_terms'  => 'assign_incident_statuses', // Primitive cap
        ),
    );

    register_taxonomy( 'incident_status', array( 'incident' ), $args );

    // Make sure these capabilities are mapped correctly in irm_map_meta_capabilities if needed,
    // or that roles directly have 'manage_incident_statuses', etc.
    // The roles_and_capabilities.php file already grants these primitive capabilities.

    // Pre-defined terms
    $terms = array(
        'Reported',
        'Dispatched',
        'On Scene',
        'Under Control',
        'Closed'
    );

    foreach ( $terms as $term_name ) {
        if ( ! term_exists( $term_name, 'incident_status' ) ) {
            wp_insert_term( $term_name, 'incident_status' );
        }
    }
}
add_action( 'init', 'irm_register_incident_status_taxonomy' );

?>
