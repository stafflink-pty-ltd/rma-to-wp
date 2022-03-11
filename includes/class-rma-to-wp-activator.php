<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.linkedin.com/in/matthew-neal-1ba40997/
 * @since      1.0.0
 *
 * @package    Rma_To_Wp
 * @subpackage Rma_To_Wp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rma_To_Wp
 * @subpackage Rma_To_Wp/includes
 * @author     Matthew Neal <mattyjneal@gmail.com>
 */
class Rma_To_Wp_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require 'db.php';
		if(!get_option('rmawp_temp_token_age')){
			add_option('rmawp_temp_token_age', 0 );
		}
		if(!get_option('rmawp_temp_token')){
			add_option('rmawp_temp_token', 0 );
		}
	}

}
