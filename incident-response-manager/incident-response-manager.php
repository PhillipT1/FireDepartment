<?php
/**
 * Plugin Name: Incident Response Manager
 * Description: A plugin to manage incidents, personnel, vehicles, and equipment for emergency response.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin path
define( 'IRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Include custom post type definitions
include_once IRM_PLUGIN_PATH . 'includes/post-types.php';

// Include meta box definitions
include_once IRM_PLUGIN_PATH . 'includes/incident-meta-boxes.php';
include_once IRM_PLUGIN_PATH . 'includes/personnel-meta-boxes.php';
include_once IRM_PLUGIN_PATH . 'includes/vehicle-meta-boxes.php';
include_once IRM_PLUGIN_PATH . 'includes/equipment-meta-boxes.php';
include_once IRM_PLUGIN_PATH . 'includes/incident-taxonomy.php';
include_once IRM_PLUGIN_PATH . 'includes/roles-and-capabilities.php';
include_once IRM_PLUGIN_PATH . 'includes/shift-post-type.php';
include_once IRM_PLUGIN_PATH . 'includes/duty-roster-page.php'; // Corrected filename
include_once IRM_PLUGIN_PATH . 'includes/vehicle-taxonomy.php';
include_once IRM_PLUGIN_PATH . 'includes/equipment-taxonomy.php';
include_once IRM_PLUGIN_PATH . 'includes/maintenance-alerts-page.php';
include_once IRM_PLUGIN_PATH . 'includes/api-endpoints.php';
include_once IRM_PLUGIN_PATH . 'includes/dashboard-page.php';
include_once IRM_PLUGIN_PATH . 'includes/integrations-webhook-support.php';

// Activation hook for setting up roles
register_activation_hook( __FILE__, 'irm_add_roles_and_capabilities' );

/**
 * Enqueue scripts and styles for the admin area.
 */
function irm_admin_enqueue_scripts( $hook_suffix ) {
    // Only load on IRM specific pages
    $irm_pages = array(
        'toplevel_page_irm_dashboard', // Main dashboard page
        'irm-dashboard_page_irm_reports', // Reports submenu page
        // Add other IRM admin page hooks here if needed
    );

    if ( in_array( $hook_suffix, $irm_pages ) ) {
        // Enqueue Chart.js from CDN
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true );
        
        // Enqueue custom charts script
        wp_enqueue_script( 'irm-charts', plugin_dir_url( __FILE__ ) . 'assets/js/irm-charts.js', array( 'chart-js', 'jquery' ), '1.0.0', true );

        // Data for charts will be localized here by individual page/widget functions
    }
}
add_action( 'admin_enqueue_scripts', 'irm_admin_enqueue_scripts' );

/**
 * =============================================================================
 * Deployment Notes
 * =============================================================================
 * 
 * To prepare the plugin for deployment (e.g., for WordPress.org or direct installation):
 * 
 * 1. Create a ZIP file of the `incident-response-manager` directory.
 *    Ensure the main plugin file (`incident-response-manager.php`) and the `readme.txt` 
 *    are at the root of the ZIP file when the `incident-response-manager` folder itself is zipped.
 *    For example, if the plugin folder is `incident-response-manager`, you would zip this folder.
 *    The structure inside the zip should be:
 *    incident-response-manager/incident-response-manager.php
 *    incident-response-manager/readme.txt
 *    incident-response-manager/includes/...
 *    incident-response-manager/assets/...
 *    etc.
 * 
 * 2. Exclude development files and directories from the production ZIP:
 *    - The `/tests` directory (if PHPUnit tests were fully set up and run locally).
 *    - `todo.md`
 *    - `INTEGRATIONS.MD` (can be kept if desired for users, but often dev-focused docs are separate)
 *    - `API_DOCUMENTATION.md` (can be kept if desired for users, but often dev-focused docs are separate)
 *    - Any local configuration files (e.g., `.phpunit.xml.dist` if it were used).
 *    - Source control directories like `.git`.
 *    - Build tool configurations (e.g., `composer.json`, `package.json`, `Gruntfile.js` if they were used).
 * 
 * 3. Ensure all text domains are correctly loaded for internationalization.
 *    The text domain 'incident-response-manager' should be used consistently.
 * 
 * 4. Review the `readme.txt` file for accuracy, especially the "Tested up to" WordPress version
 *    and the "Stable tag".
 * 
 * 5. Consider using build tools (like Gulp, Grunt, or npm scripts with wp-cli) to automate
 *    the build and packaging process, which can handle file exclusion, version bumping, etc.
 * 
 */

?>
