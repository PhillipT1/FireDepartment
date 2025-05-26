<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add meta boxes for Personnel
 */
function irm_personnel_meta_boxes() {
    add_meta_box(
        'irm_personnel_details',
        __( 'Personnel Details', 'incident-response-manager' ),
        'irm_render_personnel_meta_box',
        'personnel',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'irm_personnel_meta_boxes' );

/**
 * Render meta box for Personnel
 */
function irm_render_personnel_meta_box( $post ) {
    // Add nonce for security
    wp_nonce_field( 'irm_save_personnel_meta_data', 'irm_personnel_meta_nonce' );

    // Get existing values
    $rank = get_post_meta( $post->ID, '_rank', true );
    // $shift = get_post_meta( $post->ID, '_shift', true ); // Old shift text field
    $assigned_shift_id = get_post_meta( $post->ID, '_assigned_shift_id', true );
    $qualifications = get_post_meta( $post->ID, '_qualifications', true );
    $contact_information = get_post_meta( $post->ID, '_contact_information', true );

    ?>
    <p>
        <label for="rank"><?php _e( 'Rank:', 'incident-response-manager' ); ?></label>
        <input type="text" id="rank" name="rank" value="<?php echo esc_attr( $rank ); ?>" class="widefat">
    </p>
    
    <?php
    // Shift Assignment Dropdown
    $all_shifts = get_posts( array(
        'post_type' => 'shift',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    ) );

    if ( ! empty( $all_shifts ) ) {
        ?>
        <p>
            <label for="assigned_shift_id"><?php _e( 'Assign to Shift:', 'incident-response-manager' ); ?></label>
            <select id="assigned_shift_id" name="assigned_shift_id" class="widefat">
                <option value=""><?php _e( '-- Select Shift --', 'incident-response-manager' ); ?></option>
                <?php foreach ( $all_shifts as $shift_post ) : ?>
                    <option value="<?php echo esc_attr( $shift_post->ID ); ?>" <?php selected( $assigned_shift_id, $shift_post->ID ); ?>>
                        <?php echo esc_html( $shift_post->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    } else {
        echo '<p>' . __( 'No shifts defined. Please create shifts first.', 'incident-response-manager' ) . '</p>';
    }
    ?>

    <p>
        <label for="qualifications"><?php _e( 'Qualifications:', 'incident-response-manager' ); ?></label>
        <textarea id="qualifications" name="qualifications" class="widefat"><?php echo esc_textarea( $qualifications ); ?></textarea>
    </p>
    <p>
        <label for="contact_information"><?php _e( 'Contact Information:', 'incident-response-manager' ); ?></label>
        <textarea id="contact_information" name="contact_information" class="widefat"><?php echo esc_textarea( $contact_information ); ?></textarea>
    </p>
    <?php
}

/**
 * Save meta box data for Personnel
 */
function irm_save_personnel_meta_data( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['irm_personnel_meta_nonce'] ) || ! wp_verify_nonce( $_POST['irm_personnel_meta_nonce'], 'irm_save_personnel_meta_data' ) ) {
        return;
    }

    // Check if current user can edit post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save fields
    if ( isset( $_POST['rank'] ) ) {
        update_post_meta( $post_id, '_rank', sanitize_text_field( $_POST['rank'] ) );
    }
    // if ( isset( $_POST['shift'] ) ) { // Old shift text field
    //     update_post_meta( $post_id, '_shift', sanitize_text_field( $_POST['shift'] ) );
    // }
    if ( isset( $_POST['assigned_shift_id'] ) ) {
        $shift_id = sanitize_text_field( $_POST['assigned_shift_id'] );
        if ( empty( $shift_id ) ) {
            delete_post_meta( $post_id, '_assigned_shift_id' );
        } else {
            update_post_meta( $post_id, '_assigned_shift_id', $shift_id );
        }
    }
    if ( isset( $_POST['qualifications'] ) ) {
        update_post_meta( $post_id, '_qualifications', sanitize_textarea_field( $_POST['qualifications'] ) );
    }
    if ( isset( $_POST['contact_information'] ) ) {
        update_post_meta( $post_id, '_contact_information', sanitize_textarea_field( $_POST['contact_information'] ) );
    }
}
add_action( 'save_post_personnel', 'irm_save_personnel_meta_data' );

?>
