<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Custom Post Types
 */
function irm_register_post_types() {

    // Incidents
    register_post_type( 'incident',
        array(
            'labels' => array(
                'name' => __( 'Incidents' ),
                'singular_name' => __( 'Incident' ),
                'edit_item' => __( 'Edit Incident' ),
                'add_new_item' => __( 'Add New Incident' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'incidents'),
            'supports' => array('title', 'editor', 'custom-fields', 'comments'),
            'capability_type' => 'incident', // Use 'incident' as the capability type
            'map_meta_cap' => true, // Required for custom capability mapping
            'capabilities' => array(
                // Meta capabilities
                'edit_post' => 'edit_incident',
                'read_post' => 'read_incident',
                'delete_post' => 'delete_incident',
                // Primitive capabilities used by roles
                'edit_posts' => 'edit_incidents',
                'edit_others_posts' => 'edit_others_incidents',
                'publish_posts' => 'publish_incidents',
                'read_private_posts' => 'read_private_incidents',
                'delete_posts' => 'delete_incidents',
                'delete_private_posts' => 'delete_private_incidents',
                'delete_published_posts' => 'delete_published_incidents',
                'delete_others_posts' => 'delete_others_incidents',
                'edit_private_posts' => 'edit_private_incidents',
                'edit_published_posts' => 'edit_published_incidents',
            ),
        )
    );

    // Personnel
    register_post_type( 'personnel',
        array(
            'labels' => array(
                'name' => __( 'Personnel' ),
                'singular_name' => __( 'Personnel' ),
                'edit_item' => __( 'Edit Personnel' ),
                'add_new_item' => __( 'Add New Personnel' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'personnel'),
            'supports' => array('title', 'editor', 'custom-fields', 'comments'), // Added comments for potential future use
            'capability_type' => 'personnel',
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'edit_personnel',
                'read_post' => 'read_personnel',
                'delete_post' => 'delete_personnel',
                'edit_posts' => 'edit_personnels',
                'edit_others_posts' => 'edit_others_personnels',
                'publish_posts' => 'publish_personnels',
                'read_private_posts' => 'read_private_personnels',
                'delete_posts' => 'delete_personnels',
                'delete_private_posts' => 'delete_private_personnels',
                'delete_published_posts' => 'delete_published_personnels',
                'delete_others_posts' => 'delete_others_personnels',
                'edit_private_posts' => 'edit_private_personnels',
                'edit_published_posts' => 'edit_published_personnels',
            ),
        )
    );

    // Vehicles
    register_post_type( 'vehicle',
        array(
            'labels' => array(
                'name' => __( 'Vehicles' ),
                'singular_name' => __( 'Vehicle' ),
                'edit_item' => __( 'Edit Vehicle' ),
                'add_new_item' => __( 'Add New Vehicle' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'vehicles'),
            'supports' => array('title', 'editor', 'custom-fields', 'comments'), // Added comments for maintenance logs
            'capability_type' => 'vehicle',
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'edit_vehicle',
                'read_post' => 'read_vehicle',
                'delete_post' => 'delete_vehicle',
                'edit_posts' => 'edit_vehicles',
                'edit_others_posts' => 'edit_others_vehicles',
                'publish_posts' => 'publish_vehicles',
                'read_private_posts' => 'read_private_vehicles',
                'delete_posts' => 'delete_vehicles',
                'delete_private_posts' => 'delete_private_vehicles',
                'delete_published_posts' => 'delete_published_vehicles',
                'delete_others_posts' => 'delete_others_vehicles',
                'edit_private_posts' => 'edit_private_vehicles',
                'edit_published_posts' => 'edit_published_vehicles',
            ),
        )
    );

    // Equipment
    register_post_type( 'equipment',
        array(
            'labels' => array(
                'name' => __( 'Equipment' ),
                'singular_name' => __( 'Equipment' ),
                'edit_item' => __( 'Edit Equipment' ),
                'add_new_item' => __( 'Add New Equipment' ),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'equipment'),
            'supports' => array('title', 'editor', 'custom-fields', 'comments'), // Added comments for maintenance logs
            'capability_type' => 'equipment',
            'map_meta_cap' => true,
            'capabilities' => array(
                'edit_post' => 'edit_equipment',
                'read_post' => 'read_equipment',
                'delete_post' => 'delete_equipment',
                'edit_posts' => 'edit_equipments',
                'edit_others_posts' => 'edit_others_equipments',
                'publish_posts' => 'publish_equipments',
                'read_private_posts' => 'read_private_equipments',
                'delete_posts' => 'delete_equipments',
                'delete_private_posts' => 'delete_private_equipments',
                'delete_published_posts' => 'delete_published_equipments',
                'delete_others_posts' => 'delete_others_equipments',
                'edit_private_posts' => 'edit_private_equipments',
                'edit_published_posts' => 'edit_published_equipments',
            ),
        )
    );
}
add_action( 'init', 'irm_register_post_types' );

?>
