<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/matthew-neal-1ba40997/
 * @since      1.0.0
 *
 * @package    Rma_To_Wp
 * @subpackage Rma_To_Wp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rma_To_Wp
 * @subpackage Rma_To_Wp/admin
 * @author     Matthew Neal <mattyjneal@gmail.com>
 */
class Rma_To_Wp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rma_To_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rma_To_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rma-to-wp-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rma_To_Wp_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rma_To_Wp_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rma-to-wp-admin.js', array( 'jquery' ), $this->version, false );

	}

}

class register_custom_content {

	public function __construct() {
		$this->init();
	}

	public function register_cpt() {

		/**
		 * Post Type: RMA Reviews.
		 */
	
		$labels = [
			"name" => __( "RMA Reviews", "custom-post-type-ui" ),
			"singular_name" => __( "RMA Review", "custom-post-type-ui" ),
		];
	
		$args = [
			"label" => __( "RMA Reviews", "custom-post-type-ui" ),
			"labels" => $labels,
			"description" => "",
			"public" => true,
			"publicly_queryable" => true,
			"show_ui" => true,
			"show_in_rest" => true,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"delete_with_user" => false,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => [ "slug" => "rma-reviews", "with_front" => true ],
			"query_var" => true,
			"supports" => [ "title", "editor", "thumbnail" ],
			"show_in_graphql" => false,
		];
	
		register_post_type( "rma-reviews", $args );
	}
	
	public function register_taxonomy() {
	
		/**
		 * Taxonomy: RMA Agents.
		 */
	
		$labels = [
			"name" => __( "RMA Agents", "custom-post-type-ui" ),
			"singular_name" => __( "RMA Agent", "custom-post-type-ui" ),
		];
	
		
		$args = [
			"label" => __( "RMA Agents", "custom-post-type-ui" ),
			"labels" => $labels,
			"public" => false,
			"publicly_queryable" => false,
			"hierarchical" => false,
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"query_var" => true,
			"rewrite" => [ 'slug' => 'rma_agent', 'with_front' => true, ],
			"show_admin_column" => false,
			"show_in_rest" => true,
			"show_tagcloud" => false,
			"rest_base" => "rma_agent",
			"rest_controller_class" => "WP_REST_Terms_Controller",
			"show_in_quick_edit" => false,
			"show_in_graphql" => false,
		];
		register_taxonomy( "rma_agent", [ "rma-reviews" ], $args );
	}
	
	
	public function init() {
		add_action( 'init', [ $this, 'register_taxonomy' ] );
		add_action( 'init', [ $this, 'register_cpt'] );
	}
}

new register_custom_content();

/**
 * custom option and settings
 */
function rma_wp_settings_init() {

    register_setting( 'rmawp', 'rmawp_options' );

    add_settings_section(
        'rmawp_section_developers',
        __( 'Add your Rate My Agent API details here.', 'rmawp' ), 'rmawp_section_developers_callback',
        'rmawp'
    );

    add_settings_field(
        'rmawp_client_id',
        'Rate My Agent API Client ID',
        'rmawp_client_id_callback',
        'rmawp',
        'rmawp_section_developers',
        array(
            'label_for'         => 'rmawp_client_id',
            'class'             => 'rmawp_row'
        )
    );

	add_settings_field(
        'rmawp_secret_key',
        'Rate My Agent API Secret Key',
        'rmawp_secret_key_callback',
        'rmawp',
        'rmawp_section_developers',
        array(
            'label_for'         => 'rmawp_secret_key',
            'class'             => 'rmawp_row'
        )
    );

	add_settings_field(
        'rmawp_agent_id',
        'Rate My Agent/Agency ID',
        'rmawp_agent_id_callback',
        'rmawp',
        'rmawp_section_developers',
        array(
            'label_for'         => 'rmawp_agent_id',
            'class'             => 'rmawp_row'
        )
    );
	
}
add_action( 'admin_init', 'rma_wp_settings_init' );

/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function rmawp_section_developers_callback( $args ) {
    ?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><a href="https://go.ratemyagent.com.au/api" target="_blank">Don't have an API key yet? Click here.</a></p>
    <?php
}

function rmawp_client_id_callback( $args ) {
    $option = get_option( 'rmawp_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="rmawp_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function rmawp_secret_key_callback( $args ) {
    $option = get_option( 'rmawp_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="rmawp_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function rmawp_agent_id_callback( $args ) {
    $option = get_option( 'rmawp_options' );
    ?>
	<input 
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		type="text" 
		class="regular-text" 
		name="rmawp_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
		size="50" 
		value="<?php echo $option[$args['label_for']]; ?>">
    <?php
}

function rmawp_options_page() {
    add_submenu_page(
		'options-general.php',
        'Rate My Agent To WordPress',
        'RMA to WP',
        'manage_options',
        'rmawp',
        'rmawp_options_page_html'
    );
}
/**
 * Register our rmawp_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'rmawp_options_page' );

function rmawp_options_page_html() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'rmawp_messages', 'rmawp_message', __( 'Settings Saved', 'rmawp' ), 'updated' );
    }

    settings_errors( 'rmawp_messages' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'rmawp' );
            do_settings_sections( 'rmawp' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}


abstract class rmawp_meta_box {

    /**
     * Set up and add the meta box.
     */
    public static function add() {

        add_meta_box(
            'rmawp_box_id',
            'Rate My Agent To WordPress Fields',
            [ self::class, 'html' ],
            ['rma-reviews']
        );
    }

    /**
     * Save the meta box selections.
     *
     * @param int $post_id  The post ID.
     */
    public static function save( int $post_id ) {
        if ( array_key_exists( 'reviewAddress', $_POST ) ) {
            update_post_meta($post_id, '_reviewAddress_meta_key', $_POST['reviewAddress']);
        }
		if ( array_key_exists( 'reviewSubmittedBy', $_POST ) ) {
            update_post_meta($post_id, '_reviewSubmittedBy_meta_key', $_POST['reviewSubmittedBy']);
        }
		if ( array_key_exists( 'reviewRating', $_POST ) ) {
            update_post_meta($post_id, '_reviewRating_meta_key', $_POST['reviewRating']);
        }
		if ( array_key_exists( 'reviewImageURL', $_POST ) ) {
            update_post_meta($post_id, '_reviewImageURL_meta_key', $_POST['reviewImageURL']);
        }
    }

    /**
     * Display the meta box HTML to the user.
     *
	 * @param \WP_Post $post   Post object.
     */
    public static function html( $post ) {
        $reviewAddress = get_post_meta( $post->ID, '_reviewAddress_meta_key', true );
		$reviewSubmittedBy = get_post_meta( $post->ID, '_ReviewSubmittedBy_meta_key', true );
		$reviewRating = get_post_meta( $post->ID, '_reviewRating_meta_key', true );
		$reviewImageURL = get_post_meta( $post->ID, '_reviewImageURL_meta_key', true );
        ?>
		<table>
			<tbody>
				<tr>
				<th><label for="reviewAddress">Review Address</label></th>
					<td><input type="text" name="reviewAddress" value="<?php echo $reviewAddress; ?>"></td>
				</tr>
				<th><label for="reviewSubmittedBy">Review Submitted By</label></th>
					<td><input type="text" name="reviewSubmittedBy" value="<?php echo $reviewSubmittedBy; ?>"></td>
				</tr>

				<tr>
        			<th><label for="reviewRating">Review Rating</label></th>
					<td><input type="text" name="reviewRating" value="<?php echo $reviewRating; ?>"></td>
				</tr>
				<tr>
					<th><label for="reviewImageURL">Review Image URL</label></th>
					<td><input type="text" name="reviewImageURL" value="<?php echo $reviewImageURL; ?>"></td>
				</tr>
			</tbody>
		</table>
        <?php
    }

}
add_action( 'add_meta_boxes', [ 'rmawp_meta_box', 'add' ] );
add_action( 'save_post', [ 'rmawp_meta_box', 'save' ] );