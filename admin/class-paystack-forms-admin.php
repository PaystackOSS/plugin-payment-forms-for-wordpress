<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       kendyson.com
 * @since      1.0.0
 *
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/admin
 * @author     kendysond <kendyson@kendyson.com>
 */
class Paystack_Forms_Admin {

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
		add_action('admin_menu' , 'add_settings_page');
		add_action( 'admin_init', 'register_paystack_setting_page' );

		function add_settings_page() {
			add_submenu_page('edit.php?post_type=paystack_form', 'Api Keys Settings', 'Api Keys Settings', 'edit_posts', basename(__FILE__), 'paystack_setting_page');
		}
		function register_paystack_setting_page() {
			register_setting( 'paystack-form-settings-group', 'mode' );
			register_setting( 'paystack-form-settings-group', 'tsk' );
			register_setting( 'paystack-form-settings-group', 'tpk' );
			register_setting( 'paystack-form-settings-group', 'lsk' );
			register_setting( 'paystack-form-settings-group', 'lpk' );
		}
		function paystack_setting_page() {
			?>
			 <h1>Paystack Forms API KEYS Settings!</h1>
			 <form method="post" action="options.php">
	    <?php settings_fields( 'paystack-form-settings-group' ); do_settings_sections( 'paystack-form-settings-group' ); ?>
	    <table class="form-table paystack_setting_page">
					<tr valign="top">
					<th scope="row">Mode</th>
					<td><input type="text" name="mode" value="<?php echo esc_attr( get_option('mode') ); ?>" /></td>
					</tr>
					<tr valign="top">
	        <th scope="row">Test Secret Key</th>
	        <td><input type="text" name="tsk" value="<?php echo esc_attr( get_option('tsk') ); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Test Public Key</th>
	        <td><input type="text" name="tpk" value="<?php echo esc_attr( get_option('tpk') ); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Live Secret Key</th>
	        <td><input type="text" name="lsk" value="<?php echo esc_attr( get_option('lsk') ); ?>" /></td>
	        </tr>
					<tr valign="top">
	        <th scope="row">Live Public Key</th>
	        <td><input type="text" name="lsk" value="<?php echo esc_attr( get_option('lpk') ); ?>" /></td>
	        </tr>
	    </table>

    <?php submit_button(); ?>

</form>
			 <?php
		}
		add_action( 'init', 'register_cpt_paystack_form' );
		function register_cpt_paystack_form() {

		    $labels = array(
		        'name' => _x( 'Paystack Forms', 'paystack_form' ),
		        'singular_name' => _x( 'Paystack Form', 'paystack_form' ),
		        'add_new' => _x( 'Add New', 'paystack_form' ),
		        'add_new_item' => _x( 'Add Paystack Form', 'paystack_form' ),
		        'edit_item' => _x( 'Edit Paystack Form', 'paystack_form' ),
		        'new_item' => _x( 'Paystack Form', 'paystack_form' ),
		        'view_item' => _x( 'View Paystack Form', 'paystack_form' ),
		        'search_items' => _x( 'Search Paystack Forms', 'paystack_form' ),
		        'not_found' => _x( 'No Paystack Forms found', 'paystack_form' ),
		        'not_found_in_trash' => _x( 'No Paystack Forms found in Trash', 'paystack_form' ),
		        'parent_item_colon' => _x( 'Parent Paystack Form:', 'paystack_form' ),
		        'menu_name' => _x( 'Paystack Forms', 'paystack_form' ),
		    );

		    $args = array(
		        'labels' => $labels,
		        'hierarchical' => true,
		        'description' => 'Paystack Forms filterable by genre',
		        'supports' => array( 'title', 'editor',  'thumbnail'),
		        'public' => true,
		        'show_ui' => true,
		        'show_in_menu' => true,
		        'menu_position' => 5,
		        'menu_icon' => plugins_url('../images/logo.png', __FILE__),
		        'show_in_nav_menus' => true,
		        'publicly_queryable' => true,
		        'exclude_from_search' => false,
		        'has_archive' => false,
		        'query_var' => true,
		        'can_export' => true,
		        'rewrite' => false,
		        'comments' => false,
		        'capability_type' => 'post'
		    );
		    register_post_type( 'paystack_form', $args );
		}
		add_filter('user_can_richedit', 'disable_wyswyg_for_custom_post_type');
	  function disable_wyswyg_for_custom_post_type( $default ){
	    global $post_type;

	    if ($post_type == 'paystack_form') {
	        echo "<style>#edit-slug-box,#message p > a{display:none;}</style>";
	      add_action("admin_print_footer_scripts", "shortcode_button_script");
	      add_filter( 'user_can_richedit' , '__return_false', 50 );
	      add_action( 'wp_dashboard_setup', 'remove_dashboard_widgets' );

	    };

	    return $default;
	  }
	  function remove_dashboard_widgets() {
	  	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );   // Right Now
	  	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' ); // Recent Comments
	  	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );  // Incoming Links
	  	remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );   // Plugins
	  	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );  // Quick Press
	  	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );  // Recent Drafts
	  	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );   // WordPress blog
	  	remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );   // Other WordPress News
	  	// use 'dashboard-network' as the second parameter to remove widgets from a network dashboard.
	  }
	  add_filter( 'manage_edit-paystack_form_columns', 'my_edit_paystack_form_columns' ) ;

	  function my_edit_paystack_form_columns( $columns ) {

	  	$columns = array(
	  		'cb' => '<input type="checkbox" />',
	  		'title' => __( 'Name' ),
	  		'shortcode' => __( 'Shortcode' ),
	  		'date' => __( 'Date' )
	  	);

	  	return $columns;
	  }
		add_action( 'manage_paystack_form_posts_custom_column', 'my_paystack_form_columns', 10, 2 );

		function my_paystack_form_columns( $column, $post_id ) {
			global $post;

			switch( $column ) {
				case 'shortcode' :
					echo '<span class="shortcode">
					<input type="text" class="large-text code" value="[paystack_form id=&quot;'.$post_id.'&quot;]"
					readonly="readonly" onfocus="this.select();"></span>';

					break;
				default :
					break;
			}
		}
		add_filter( 'default_content', 'my_editor_content', 10, 2 );

		function my_editor_content( $content, $post ) {

		    switch( $post->post_type ) {
		        case 'paystack_form':
		            $content = '[text name="Full Name"]';
		        break;
		        default:
		            $content = '';
		        break;
		    }

		    return $content;
		}
		/////
		add_action( 'add_meta_boxes', 'add_events_metaboxes' );
	  function add_events_metaboxes() {

	      add_meta_box('wpt_events_location', 'Extra Form Description', 'wpt_events_location', 'paystack_form', 'normal', 'default');

	  }

	  function wpt_events_location() {
	  	global $post;

	  	// Noncename needed to verify where the data originated
	  	echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
	  	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

	  	// Get the location data if its already been entered
			$amount = get_post_meta($post->ID, '_amount', true);
	  	$paybtn = get_post_meta($post->ID, '_paybtn', true);
	    $successmsg = get_post_meta($post->ID, '_successmsg', true);

	  	// Echo out the field
	    echo '<p>Amount to be paid(Set 0 for customer input):</p>';
	  	echo '<input type="number" name="_amount" value="' . $amount  . '" class="widefat" />';
			echo '<p>Pay button Description:</p>';
	  	echo '<input type="text" name="_paybtn" value="' . $paybtn  . '" class="widefat" />';
	    echo '<p>Success Message after Payment</p>';
	    echo '<textarea rows="3"  name="_successmsg"  class="widefat" >'.$successmsg.'</textarea>';

	  }

		function wpt_save_events_meta($post_id, $post) {

			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times
			if ( !wp_verify_nonce( @$_POST['eventmeta_noncename'], plugin_basename(__FILE__) )) {
			return $post->ID;
			}

			// Is the user allowed to edit the post or page?
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;

			// OK, we're authenticated: we need to find and save the data
			// We'll put it into an array to make it easier to loop though.

		  $events_meta['_amount'] = $_POST['_amount'];
			$events_meta['_paybtn'] = $_POST['_paybtn'];
			$events_meta['_successmsg'] = $_POST['_successmsg'];

			// Add values of $events_meta as custom fields

			foreach ($events_meta as $key => $value) { // Cycle through the $events_meta array!
				if( $post->post_type == 'revision' ) return; // Don't store custom data twice
				$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
				if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				} else { // If the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);
				}
				if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
			}

		}

		add_action('save_post', 'wpt_save_events_meta', 1, 2); // save the custom fields

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
		 * defined in Paystack_Forms_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paystack_Forms_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/paystack-forms-admin.css', array(), $this->version, 'all' );

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
		 * defined in Paystack_Forms_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Paystack_Forms_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/paystack-forms-admin.js', array( 'jquery' ), $this->version, false );

	}

}
