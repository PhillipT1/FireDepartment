<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Equipment Status Taxonomy
 */
function irm_register_equipment_status_taxonomy() {
    $labels = array(
        'name'              => _x( 'Equipment Statuses', 'taxonomy general name', 'incident-response-manager' ),
        'singular_name'     => _x( 'Equipment Status', 'taxonomy singular name', 'incident-response-manager' ),
        'search_items'      => __( 'Search Equipment Statuses', 'incident-response-manager' ),
        'all_items'         => __( 'All Equipment Statuses', 'incident-response-manager' ),
        'parent_item'       => __( 'Parent Equipment Status', 'incident-response-manager' ),
        'parent_item_colon' => __( 'Parent Equipment Status:', 'incident-response-manager' ),
        'edit_item'         => __( 'Edit Equipment Status', 'incident-response-manager' ),
        'update_item'       => __( 'Update Equipment Status', 'incident-response-manager' ),
        'add_new_item'      => __( 'Add New Equipment Status', 'incident-response-manager' ),
        'new_item_name'     => __( 'New Equipment Status Name', 'incident-response-manager' ),
        'menu_name'         => __( 'Equipment Status', 'incident-response-manager' ),
    );

    $args = array(
        'hierarchical'      => false, // Non-hierarchical
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'equipment-status' ),
        'public'            => true, // Can be false if only for admin use
        'capability_type'   => 'equipment_status_term',
        'capabilities'      => array(
            'manage_terms'  => 'manage_equipment_statuses',
            'edit_terms'    => 'edit_equipment_statuses',
            'delete_terms'  => 'delete_equipment_statuses',
            'assign_terms'  => 'assign_equipment_statuses',
        ),
    );

    register_taxonomy( 'equipment_status', array( 'equipment' ), $args );

    // Pre-defined terms
    $terms = array(
        'Available',
        'Out of Service',
        'In Maintenance',
        'In Use',
        'Needs Repair'
    );

    foreach ( $terms as $term_name ) {
        if ( ! term_exists( $term_name, 'equipment_status' ) ) {
            wp_insert_term( $term_name, 'equipment_status' );
        }
    }
}
add_action( 'init', 'irm_register_equipment_status_taxonomy' );

?>
