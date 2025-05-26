<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Add custom roles and capabilities.
 * This function should be called on plugin activation.
 */
function irm_add_roles_and_capabilities() {
    // Remove existing roles to ensure a clean setup (optional, use with caution)
    // remove_role( 'firefighter' );
    // remove_role( 'captain' );
    // remove_role( 'chief' );

    // --- Define Capabilities ---
    // Note: WordPress automatically maps meta capabilities (e.g., edit_post) to primitive capabilities 
    // (e.g., edit_posts, edit_others_posts) if CPTs are registered with 'capability_type' => 'post' or a custom type.
    // We define custom primitive capabilities for our CPTs for clarity and specific control.
    // Example: 'incident' CPT will use 'incident' as capability_type. Primitives: edit_incidents, read_incidents, delete_incidents etc.

    $cpts = array( 'incident', 'personnel', 'vehicle', 'equipment', 'shift' ); // Added 'shift' CPT
    $generic_caps = array('read', 'upload_files', 'edit_posts', 'edit_published_posts', 'delete_posts'); // Basic WP caps

    // --- Firefighter Role ---
    // Can view all CPTs, update own profile, add logs to assigned incidents.
    $firefighter_caps = array_fill_keys($generic_caps, true);
    $firefighter_caps['edit_profile'] = true; // Standard WP capability
    $firefighter_caps['level_0'] = true;

    // Read capabilities for all plugin CPTs
    foreach ($cpts as $cpt) {
        $firefighter_caps["read_{$cpt}s"] = true; // e.g. read_incidents
    }
    // Capability to add comments (logs) - typically tied to 'edit_posts' on the CPT.
    // If an incident is published, and they have 'edit_published_posts' and 'read_incident' (for that post ID), they can comment.
    // We will refine "assigned to" logic in the AJAX handler for adding logs.
    // For now, 'edit_published_posts' is a base.
    // The actual check `current_user_can('edit_comment', $comment_id)` or `current_user_can('edit_post', $post_id_of_comment)` is key.
    $firefighter_caps['edit_comment'] = true; // General capability to edit their own comments.

    add_role( 'firefighter', 'Firefighter', $firefighter_caps );

    // --- Captain Role ---
    // All Firefighter capabilities + create/update incidents, assign personnel/vehicles, update incident status.
    $captain_caps = $firefighter_caps; // Start with firefighter caps

    // Incident Management
    $captain_caps['edit_incidents'] = true;
    $captain_caps['edit_others_incidents'] = true; // Can edit any incident
    $captain_caps['publish_incidents'] = true;
    $captain_caps['read_private_incidents'] = true;
    // 'delete_incidents' is intentionally false unless specified otherwise

    // Assignment capabilities (custom meta caps, mapped later)
    $captain_caps['assign_personnel_to_incidents'] = true;
    $captain_caps['assign_vehicles_to_incidents'] = true;
    
    // Incident Status Taxonomy Management
    $captain_caps['manage_incident_status'] = true; // Custom primitive cap for taxonomy
    $captain_caps['edit_incident_statuses'] = true;   // Corresponds to edit_terms
    $captain_caps['assign_incident_statuses'] = true; // Corresponds to assign_terms
    // 'delete_incident_statuses' is false for Captains

    // Personnel CPT - Can view all. "Manage subordinate personnel" is complex for static caps.
    // For now, they don't get edit rights over other personnel records beyond viewing.
    // If 'personnel' was a CPT they could edit, we'd add:
    // $captain_caps['edit_personnels'] = true;
    // $captain_caps['edit_others_personnels'] = true; // If they can edit others

    add_role( 'captain', 'Captain', $captain_caps );

    // --- Chief Role ---
    // All Captain capabilities + manage all personnel, vehicles, equipment, reports, settings.
    $chief_caps = $captain_caps;

    // Full CPT Management (Incidents, Personnel, Vehicles, Equipment)
    foreach ($cpts as $cpt) {
        $chief_caps["edit_{$cpt}s"] = true;
        $chief_caps["edit_others_{$cpt}s"] = true;
        $chief_caps["publish_{$cpt}s"] = true;
        $chief_caps["delete_{$cpt}s"] = true;
        $chief_caps["delete_others_{$cpt}s"] = true;
        $chief_caps["read_private_{$cpt}s"] = true;
        $chief_caps["edit_published_{$cpt}s"] = true;
        $chief_caps["delete_published_{$cpt}s"] = true;
        $chief_caps["edit_private_{$cpt}s"] = true;
        $chief_caps["delete_private_{$cpt}s"] = true;
    }

    // User Management (for managing Firefighters and Captains)
    $chief_caps['create_users'] = true;
    $chief_caps['delete_users'] = true;
    $chief_caps['edit_users'] = true; // Note: this is powerful. Usually for admins.
    $chief_caps['list_users'] = true;
    $chief_caps['promote_users'] = true;
    $chief_caps['remove_users'] = true;

    // Taxonomy Management (full control for incident_status, vehicle_status, equipment_status)
    $taxonomies_managed_by_chief = array('incident_status', 'vehicle_status', 'equipment_status');
    foreach ($taxonomies_managed_by_chief as $tax_slug) {
        $chief_caps["manage_{$tax_slug}es"] = true; // e.g. manage_incident_statuses
        $chief_caps["edit_{$tax_slug}es"] = true;
        $chief_caps["delete_{$tax_slug}es"] = true;
        $chief_caps["assign_{$tax_slug}es"] = true;
    }

    // Captains should be able to assign statuses
    $taxonomies_assigned_by_captain = array('incident_status', 'vehicle_status', 'equipment_status');
    foreach ($taxonomies_assigned_by_captain as $tax_slug) {
        if (!isset($captain_caps["manage_{$tax_slug}es"])) $captain_caps["manage_{$tax_slug}es"] = false; // ensure it exists if not set by chief copy
        if (!isset($captain_caps["edit_{$tax_slug}es"])) $captain_caps["edit_{$tax_slug}es"] = false;
        if (!isset($captain_caps["delete_{$tax_slug}es"])) $captain_caps["delete_{$tax_slug}es"] = false;
        $captain_caps["assign_{$tax_slug}es"] = true;
    }


    // Other specific chief capabilities
    $chief_caps['access_reports'] = true; // Custom cap for reports page
    $chief_caps['manage_irm_settings'] = true; // Custom cap for settings page

    add_role( 'chief', 'Chief', $chief_caps );

    // Ensure Administrator has all capabilities defined for Chief
    $admin_role = get_role( 'administrator' );
    if ( $admin_role ) {
        foreach ( $chief_caps as $cap_name => $is_granted ) {
            if ( $is_granted ) {
                $admin_role->add_cap( $cap_name );
            }
        }
        // Add any caps that might not be in chief_caps but admin should have
        $admin_role->add_cap('manage_options'); // Standard admin cap
    }
}

/**
 * This function will map meta capabilities for CPTs and taxonomies.
 * This allows more granular control, e.g., checking `current_user_can('edit_incident', $post_id)`.
 */
function irm_map_meta_capabilities( $caps, $cap, $user_id, $args ) {
    $cpt_capability_types = array('incident', 'personnel', 'vehicle', 'equipment', 'shift');
    $taxonomy_capability_types = array('incident_status', 'vehicle_status', 'equipment_status'); // Added new taxonomies

    // --- CPT Meta Capability Mapping ---
    // Example: $cap could be 'edit_incident', 'read_incident', 'delete_incident'
    // $args[0] would be $post_id for these cases.
    foreach ($cpt_capability_types as $cpt_type) {
        if ( $cap === "edit_{$cpt_type}" || $cap === "read_{$cpt_type}" || $cap === "delete_{$cpt_type}" ) {
            $post = isset($args[0]) ? get_post( $args[0] ) : null;
            if ( ! $post || $post->post_type !== $cpt_type ) {
                return $caps; // Not our CPT or no post object
            }

            // Prime the required capabilities array.
            // WordPress will check if the user has AT LEAST ONE of these.
            $required_caps = array();

            switch ( $cap ) {
                case "edit_{$cpt_type}":
                    if ( $user_id == $post->post_author ) {
                        $required_caps[] = "edit_{$cpt_type}s"; // Can they edit their own CPT items?
                    } else {
                        $required_caps[] = "edit_others_{$cpt_type}s"; // Can they edit others' CPT items?
                    }
                    break;
                case "read_{$cpt_type}":
                    if ( 'private' === $post->post_status && ! current_user_can( "read_private_{$cpt_type}s", $user_id ) ) {
                         // If it's private and user doesn't have read_private_{cpt}s, deny.
                        $required_caps[] = 'do_not_allow'; // Effectively blocks access
                    } else {
                        // For public posts or if user has read_private cap for private posts
                        $required_caps[] = "read_{$cpt_type}s"; // Can they read CPT items? (This could be just 'read')
                        // WordPress also checks 'read' by default for public CPTs.
                        // If CPT is public, 'read' is usually enough.
                        // If CPT has `publicly_queryable = false`, then specific read_{cpt}s is needed.
                        // For simplicity, we assume "read_{cpt}s" is the base read cap for the CPT.
                    }
                    break;
                case "delete_{$cpt_type}":
                    if ( $user_id == $post->post_author ) {
                        $required_caps[] = "delete_{$cpt_type}s";
                    } else {
                        $required_caps[] = "delete_others_{$cpt_type}s";
                    }
                    break;
            }
            return $required_caps;
        }
    }

    // --- Taxonomy Meta Capability Mapping ---
    // Example: $cap could be 'manage_incident_status', 'edit_incident_status', 'delete_incident_status', 'assign_incident_status'
    // $args[0] would be $term_id for edit/delete, $taxonomy_slug for manage/assign.
    foreach ($taxonomy_capability_types as $tax_type) {
        $primitive_map = array(
            "manage_{$tax_type}" => "manage_{$tax_type}es", // e.g. manage_incident_statuses (custom, or map to manage_categories)
            "edit_{$tax_type}"   => "edit_{$tax_type}es",   // e.g. edit_incident_statuses (custom, or map to manage_categories)
            "delete_{$tax_type}" => "delete_{$tax_type}es", // e.g. delete_incident_statuses (custom, or map to manage_categories)
            "assign_{$tax_type}" => "assign_{$tax_type}es", // e.g. assign_incident_statuses (custom, or map to edit_posts)
        );

        if ( isset( $primitive_map[$cap] ) ) {
            // For taxonomies, WordPress maps manage_terms, edit_terms, delete_terms, assign_terms.
            // Our custom roles are given 'manage_incident_status', 'edit_incident_statuses', etc.
            // So, if the CPT registration uses 'capability_type' => 'incident_status_term', these would map.
            // Or, we ensure our roles get the WP default ones like 'manage_categories' if taxonomy is simple.
            // For this plugin, we've defined 'manage_incident_status' as a custom primitive cap for the roles.
            // Let's map to these custom primitive caps.
            return array( $primitive_map[$cap] );
        }
    }
    
    // Custom meta capabilities not related to CPTs/Taxonomies directly
    if ( $cap === 'assign_personnel_to_incidents' || $cap === 'assign_vehicles_to_incidents' || $cap === 'access_reports' || $cap === 'manage_irm_settings' ) {
        // These are custom meta capabilities. The check `current_user_can('assign_personnel_to_incidents')`
        // will directly check if the role has this capability. No further mapping needed if it's a simple boolean grant.
        // So, map it to itself, meaning the user must have this exact capability string.
        return array( $cap );
    }

    // "Add logs to incidents they are assigned to" (Firefighter)
    // This is checked in `irm_handle_add_incident_log_entry` via `current_user_can( 'edit_post', $post_id )`
    // and then additional logic for "assigned to". `edit_post` for incident post type will map to `edit_incidents` or `edit_others_incidents`.
    // Firefighters are given `edit_published_posts` which is a general WP cap.
    // If their role also has `edit_incidents` (even if only for their own), then `edit_post` on an incident they own would pass.
    // For commenting, WP checks `current_user_can('edit_post', $post->ID)`.
    // Firefighters need `edit_incidents` (primitive) if they are to add comments to incidents they "own"
    // Or, if we want them to comment on *any* incident they are assigned to (even if not post author),
    // they need `edit_others_incidents` or a more specific check.
    // For now, the `edit_published_posts` and the AJAX check logic will handle this.

    return $caps; // Return original $caps if no mapping occurred
}
add_filter( 'map_meta_cap', 'irm_map_meta_capabilities', 10, 4 );

// Note: The irm_add_cpt_capabilities function is removed as its logic is integrated into irm_add_roles_and_capabilities.
// The roles are now directly assigned the primitive CPT and taxonomy capabilities.
// Ensure CPTs and Taxonomies are registered with 'capability_type' => 'cpt_slug' and 'capability_type' => 'taxonomy_slug_term' respectively
// OR that their 'capabilities' argument in register_post_type/register_taxonomy correctly lists these primitive capabilities.
// For example, register_post_type('incident', array( 'capability_type' => 'incident', 'capabilities' => array( 'edit_post' => 'edit_incident', ...)))
// This will ensure WordPress uses 'edit_incident' as the meta cap and 'edit_incidents' as the primitive cap.
// The current `post-types.php` uses default capabilities, so `edit_post` is the meta cap.
// The map_meta_cap filter above handles `edit_incident` style meta caps if CPTs are registered to use them.
// If CPTs use 'post' as capability_type, then map_meta_cap needs to handle 'edit_post' for $post->post_type == 'incident'.

?>
