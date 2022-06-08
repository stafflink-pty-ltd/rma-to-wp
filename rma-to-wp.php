<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.linkedin.com/in/matthew-neal-1ba40997/
 * @since             1.0.1
 * @package           Rma_To_Wp
 *
 * @wordpress-plugin
 * Plugin Name:       Rate My Agent To WordPress By Stafflink
 * Plugin URI:        https://stafflink.com.au/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           0.0.1
 * Author:            Matthew Neal
 * Author URI:        https://www.linkedin.com/in/matthew-neal-1ba40997/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rma-to-wp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RMA_TO_WP_VERSION', '0.0.1' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rma-to-wp-activator.php
 */
function activate_rma_to_wp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rma-to-wp-activator.php';
	Rma_To_Wp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rma-to-wp-deactivator.php
 */
function deactivate_rma_to_wp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rma-to-wp-deactivator.php';
	Rma_To_Wp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rma_to_wp' );
register_deactivation_hook( __FILE__, 'deactivate_rma_to_wp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rma-to-wp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rma_to_wp() {

	$plugin = new Rma_To_Wp();
	$plugin->run();

}
run_rma_to_wp();
