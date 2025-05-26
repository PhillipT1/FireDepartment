<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Shift Custom Post Type
 */
function irm_register_shift_post_type() {
    $labels = array(
        'name'               => _x( 'Shifts', 'post type general name', 'incident-response-manager' ),
        'singular_name'      => _x( 'Shift', 'post type singular name', 'incident-response-manager' ),
        'menu_name'          => _x( 'Shifts', 'admin menu', 'incident-response-manager' ),
        'name_admin_bar'     => _x( 'Shift', 'add new on admin bar', 'incident-response-manager' ),
        'add_new'            => _x( 'Add New', 'shift', 'incident-response-manager' ),
        'add_new_item'       => __( 'Add New Shift', 'incident-response-manager' ),
        'new_item'           => __( 'New Shift', 'incident-response-manager' ),
        'edit_item'          => __( 'Edit Shift', 'incident-response-manager' ),
        'view_item'          => __( 'View Shift', 'incident-response-manager' ),
        'all_items'          => __( 'All Shifts', 'incident-response-manager' ),
        'search_items'       => __( 'Search Shifts', 'incident-response-manager' ),
        'parent_item_colon'  => __( 'Parent Shifts:', 'incident-response-manager' ),
        'not_found'          => __( 'No shifts found.', 'incident-response-manager' ),
        'not_found_in_trash' => __( 'No shifts found in Trash.', 'incident-response-manager' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Defines standard operational shifts.', 'incident-response-manager' ),
        'public'             => false, // Not public on the frontend
        'publicly_queryable' => false,
        'show_ui'            => true, // Show in admin UI
        'show_in_menu'       => true, // Show under the main plugin menu (or true for top-level)
        'query_var'          => false,
        'rewrite'            => false,
        'capability_type'    => 'shift', // Custom capability type
        'map_meta_cap'       => true,
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => 25, // Adjust as needed
        'supports'           => array( 'title', 'editor' ), // Title for Shift Name, Editor for Description/Pattern
        'show_in_rest'       => true, // Enable Gutenberg editor if desired
        'capabilities'       => array(
            'edit_post'          => 'edit_shift',
            'read_post'          => 'read_shift',
            'delete_post'        => 'delete_shift',
            'edit_posts'         => 'edit_shifts',
            'edit_others_posts'  => 'edit_others_shifts',
            'publish_posts'      => 'publish_shifts',
            'read_private_posts' => 'read_private_shifts',
            'create_posts'       => 'edit_shifts', // 'edit_shifts' typically allows creation
        ),
    );

    register_post_type( 'shift', $args );
}
add_action( 'init', 'irm_register_shift_post_type' );

/**
 * Add meta boxes for Shift CPT
 */
function irm_shift_meta_boxes() {
    add_meta_box(
        'irm_shift_details_meta_box',
        __( 'Shift Details', 'incident-response-manager' ),
        'irm_render_shift_details_meta_box',
        'shift',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'irm_shift_meta_boxes' );

/**
 * Render meta box for Shift CPT
 */
function irm_render_shift_details_meta_box( $post ) {
    wp_nonce_field( 'irm_save_shift_meta_data', 'irm_shift_meta_nonce' );

    $start_time = get_post_meta( $post->ID, '_shift_start_time', true );
    $end_time = get_post_meta( $post->ID, '_shift_end_time', true );
    // Rotation pattern can be stored in the main content editor or a dedicated field.
    // For now, using editor. If dedicated field:
    // $rotation_pattern = get_post_meta( $post->ID, '_shift_rotation_pattern', true );

    ?>
    <p>
        <label for="shift_start_time"><?php _e( 'Shift Start Time:', 'incident-response-manager' ); ?></label>
        <input type="time" id="shift_start_time" name="shift_start_time" value="<?php echo esc_attr( $start_time ); ?>" class="regular-text">
    </p>
    <p>
        <label for="shift_end_time"><?php _e( 'Shift End Time:', 'incident-response-manager' ); ?></label>
        <input type="time" id="shift_end_time" name="shift_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text">
    </p>
    <p>
        <?php _e( 'Use the main editor above to describe the shift rotation pattern (e.g., 24/48, 4 on 4 off, etc.) and any other relevant details.', 'incident-response-manager' ); ?>
    </p>
    <?php
}

/**
 * Save meta box data for Shift CPT
 */
function irm_save_shift_meta_data( $post_id ) {
    if ( ! isset( $_POST['irm_shift_meta_nonce'] ) || ! wp_verify_nonce( $_POST['irm_shift_meta_nonce'], 'irm_save_shift_meta_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_shift', $post_id ) ) { // Check against the meta capability
        return;
    }

    if ( isset( $_POST['shift_start_time'] ) ) {
        update_post_meta( $post_id, '_shift_start_time', sanitize_text_field( $_POST['shift_start_time'] ) );
    }
    if ( isset( $_POST['shift_end_time'] ) ) {
        update_post_meta( $post_id, '_shift_end_time', sanitize_text_field( $_POST['shift_end_time'] ) );
    }
}
add_action( 'save_post_shift', 'irm_save_shift_meta_data' );

?>
