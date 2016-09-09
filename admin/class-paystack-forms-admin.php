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

		add_action( 'admin_menu', 'register_newpage' );

		function register_newpage(){
		    add_menu_page('paystack', 'paystack', 'administrator','submissions', 'custom');
		    remove_menu_page('custom');
		}

		function custom(){
			$id = $_GET['form'];
			$obj = get_post($id);
 		 	if ($obj->post_type == 'paystack_form') {
	 			 $amount = get_post_meta($id,'_amount',true);
	 			 $thankyou = get_post_meta($id,'_successmsg',true);
	 			 $paybtn = get_post_meta($id,'_paybtn',true);
	 			 $loggedin = get_post_meta($id,'_loggedin',true);
	 			 $txncharge = get_post_meta($id,'_txncharge',true);
	 			 $currency = get_post_meta($id,'_currency',true);

				 echo "<h1>Paystack Forms API KEYS Settings!</h1>";
				 $exampleListTable = new Example_List_Table();
         $exampleListTable->prepare_items();
         ?>
             <div class="wrap">
                 <div id="icon-users" class="icon32"></div>
                 <h2>Example List Table Page</h2>
                 <?php $exampleListTable->display(); ?>
             </div>
         <?php

	 		}
		}
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
		        'supports' => array( 'title', 'editor'),
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

		function pform_add_action_button($actions, $post){

		    if(get_post_type() === 'paystack_form'){
					unset($actions['view']);
					unset($actions['quick edit']);
		        $url = add_query_arg(
		            array(
		              'post_id' => $post->ID,
		              'action' => 'submissions',
		            )
		          );
		    $actions['export'] = '<a href="' . admin_url('admin.php?page=submissions&form='.$post->ID) . '" target="_blank" >View Payments</a>';
		    }
		    return $actions;
		}



		add_filter( 'page_row_actions', 'pform_add_action_button', 10, 2 );

		add_action( 'admin_init', 'custom_export_function' );

		function custom_export_function(){

		  if ( isset( $_REQUEST['action'] ) && 'view_submissions' == $_REQUEST['action']  ) {
		    $data = array(
		      'hello' => 'world'
		      );

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

				<?php
		  }

			// exit;
		}
		function wpa_47010( $qtInit ) {
				$qtInit['buttons'] = 'fullscreen';
				return $qtInit;
		}
		function disable_wyswyg_for_custom_post_type( $default ){
	    global $post_type, $_wp_theme_features;;


	    if ($post_type == 'paystack_form') {
	        echo "<style>#edit-slug-box,#message p > a{display:none;}</style>";
	      add_action("admin_print_footer_scripts", "shortcode_button_script");
	      add_filter( 'user_can_richedit' , '__return_false', 50 );
	      add_action( 'wp_dashboard_setup', 'remove_dashboard_widgets' );
				remove_action( 'media_buttons', 'media_buttons' );
				remove_meta_box( 'postimagediv','post','side' );
				add_filter('quicktags_settings', 'wpa_47010');
			}

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
		function help_metabox( $post ) {

		    do_meta_boxes( null, 'custom-metabox-holder', $post );
		}
		add_action( 'edit_form_after_title', 'help_metabox' );
		function add_help_metabox() {

				add_meta_box(
					'awesome_metabox_id',
					'Help Section',
					'help_metabox_details',
					'paystack_form',
					'custom-metabox-holder'	//Look what we have here, a new context
				);

		}
		add_action( 'add_meta_boxes', 'add_help_metabox' );

		function help_metabox_details( $post ) {
			echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
	  	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

			?>
			<div class="awesome-meta-admin">
				To make an input compulsory add <code> required="required" </code> to the shortcode <br />

			</div>

		<?php
		}

		add_action( 'add_meta_boxes', 'add_extra_metaboxes' );
	  function add_extra_metaboxes() {

			add_meta_box('wpt_form_data', 'Extra Form Description', 'wpt_form_data', 'paystack_form', 'normal', 'default');

	  }

	  function wpt_form_data() {
	  	global $post;

	  	// Noncename needed to verify where the data originated
	  	echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
	  	wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

	  	// Get the location data if its already been entered
			$amount = get_post_meta($post->ID, '_amount', true);
	  	$paybtn = get_post_meta($post->ID, '_paybtn', true);
			$successmsg = get_post_meta($post->ID, '_successmsg', true);
			$txncharge = get_post_meta($post->ID, '_txncharge', true);
			$loggedin = get_post_meta($post->ID, '_loggedin', true);
	    $currency = get_post_meta($post->ID, '_currency', true);
			function txncheck($name,$txncharge){

				if ($name == $txncharge) {
					$result = "selected";
				}else{
					$result = "";
				}
				return $result;
			}
			if ($amount == "") {$amount = 0;}
			if ($paybtn == "") {$paybtn = 'Pay';}
			if ($successmsg == "") {$successmsg = 'Thank you for paying!';}
			if ($currency == "") {$currency = 'NGN';}
	  	// Echo out the field
			echo '<p>Currency:</p>';
	  	echo '<input type="text" name="_currency" value="' . $currency  . '" class="widefat" />';
			echo '<p>Amount to be paid(Set 0 for customer input):</p>';
	  	echo '<input type="number" name="_amount" value="' . $amount  . '" class="widefat pf-number" />';
			echo '<p>Pay button Description:</p>';
	  	echo '<input type="text" name="_paybtn" value="' . $paybtn  . '" class="widefat" />';
			// echo '<p>Transaction Charges:</p>';
			// echo '<select class="form-control" name="_txncharge" id="parent_id" style="width:100%;">
			// 				<option value="merchant"'.txncheck('merchant',$txncharge).'>Merchant Pays(Include in fee)</option>
			// 				<option value="customer" '.txncheck('customer',$txncharge).'>Client Pays(Extra Fee added)</option>
			// 			</select>';
			echo '<p>User logged In:</p>';
			echo '<select class="form-control" name="_loggedin" id="parent_id" style="width:100%;">
							<option value="no" '.txncheck('no',$loggedin).'>User must not be logged in</option>
							<option value="yes"'.txncheck('yes',$loggedin).'>User must be logged In</option>
						</select>';
	  	echo '<p>Success Message after Payment</p>';
	    echo '<textarea rows="3"  name="_successmsg"  class="widefat" >'.$successmsg.'</textarea>';

	  }

		function wpt_form_data_meta($post_id, $post) {

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
			$events_meta['_txncharge'] = $_POST['_txncharge'];
			$events_meta['_loggedin'] = $_POST['_loggedin'];

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

		add_action('save_post', 'wpt_form_data_meta', 1, 2); // save the custom fields

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
if(is_admin())
{
}

/**
 * Paulund_Wp_List_Table class will create the page to load the table
 */
class Paulund_Wp_List_Table
{
    /**
     * Constructor will create the menu item
     */
    public function __construct()
    {
        add_action( 'admin_menu', array($this, 'add_menu_example_list_table_page' ));
    }

    /**
     * Menu item will allow us to load the page to display the table
     */
    public function add_menu_example_list_table_page()
    {
        add_menu_page( 'Example List Table', 'Example List Table', 'manage_options', 'example-list-table.php', array($this, 'list_table_page') );
    }

    /**
     * Display the list table page
     *
     * @return Void
     */
    public function list_table_page()
    {
        $exampleListTable = new Example_List_Table();
        $exampleListTable->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2>Example List Table Page</h2>
                <?php $exampleListTable->display(); ?>
            </div>
        <?php
    }
}

// WP_List_Table is not loaded automatically so we need to load it in our application
 // WP_List_Table is not loaded automatically so we need to load it in our application
 if( ! class_exists( 'WP_List_Table' ) ) {
     require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
 }
 /**
  * Create a new table class that will extend the WP_List_Table
  */
 class Example_List_Table extends WP_List_Table
 {
     /**
      * Prepare the items for the table to process
      *
      * @return Void
      */
     public function prepare_items()
     {
         $columns = $this->get_columns();
         $hidden = $this->get_hidden_columns();
         $sortable = $this->get_sortable_columns();
         $data = $this->table_data();
         usort( $data, array( &$this, 'sort_data' ) );
         $perPage = 20;
         $currentPage = $this->get_pagenum();
         $totalItems = count($data);
         $this->set_pagination_args( array(
             'total_items' => $totalItems,
             'per_page'    => $perPage
         ) );
         $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
         $this->_column_headers = array($columns, $hidden, $sortable);
         $this->items = $data;
     }
     /**
      * Override the parent columns method. Defines the columns to use in your listing table
      *
      * @return Array
      */
     public function get_columns()
     {
         $columns = array(
             'id'          => 'ID',
             'title'       => 'Title',
             'description' => 'Description',
             'year'        => 'Year',
             'director'    => 'Director',
             'rating'      => 'Rating'
         );
         return $columns;
     }
     /**
      * Define which columns are hidden
      *
      * @return Array
      */
     public function get_hidden_columns()
     {
         return array();
     }
     /**
      * Define the sortable columns
      *
      * @return Array
      */
     public function get_sortable_columns()
     {
         return array('title' => array('title', false));
     }
     /**
      * Get the table data
      *
      * @return Array
      */
     private function table_data()
     {
         $data = array();
         $data[] = array(
                     'id'          => 1,
                     'title'       => 'The Shawshank Redemption',
                     'description' => 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.',
                     'year'        => '1994',
                     'director'    => 'Frank Darabont',
                     'rating'      => '9.3'
                     );
         $data[] = array(
                     'id'          => 2,
                     'title'       => 'The Godfather',
                     'description' => 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.',
                     'year'        => '1972',
                     'director'    => 'Francis Ford Coppola',
                     'rating'      => '9.2'
                     );
         $data[] = array(
                     'id'          => 3,
                     'title'       => 'The Godfather: Part II',
                     'description' => 'The early life and career of Vito Corleone in 1920s New York is portrayed while his son, Michael, expands and tightens his grip on his crime syndicate stretching from Lake Tahoe, Nevada to pre-revolution 1958 Cuba.',
                     'year'        => '1974',
                     'director'    => 'Francis Ford Coppola',
                     'rating'      => '9.0'
                     );
         $data[] = array(
                     'id'          => 4,
                     'title'       => 'Pulp Fiction',
                     'description' => 'The lives of two mob hit men, a boxer, a gangster\'s wife, and a pair of diner bandits intertwine in four tales of violence and redemption.',
                     'year'        => '1994',
                     'director'    => 'Quentin Tarantino',
                     'rating'      => '9.0'
                     );
         $data[] = array(
                     'id'          => 5,
                     'title'       => 'The Good, the Bad and the Ugly',
                     'description' => 'A bounty hunting scam joins two men in an uneasy alliance against a third in a race to find a fortune in gold buried in a remote cemetery.',
                     'year'        => '1966',
                     'director'    => 'Sergio Leone',
                     'rating'      => '9.0'
                     );
         $data[] = array(
                     'id'          => 6,
                     'title'       => 'The Dark Knight',
                     'description' => 'When Batman, Gordon and Harvey Dent launch an assault on the mob, they let the clown out of the box, the Joker, bent on turning Gotham on itself and bringing any heroes down to his level.',
                     'year'        => '2008',
                     'director'    => 'Christopher Nolan',
                     'rating'      => '9.0'
                     );
         $data[] = array(
                     'id'          => 7,
                     'title'       => '12 Angry Men',
                     'description' => 'A dissenting juror in a murder trial slowly manages to convince the others that the case is not as obviously clear as it seemed in court.',
                     'year'        => '1957',
                     'director'    => 'Sidney Lumet',
                     'rating'      => '8.9'
                     );
         $data[] = array(
                     'id'          => 8,
                     'title'       => 'Schindler\'s List',
                     'description' => 'In Poland during World War II, Oskar Schindler gradually becomes concerned for his Jewish workforce after witnessing their persecution by the Nazis.',
                     'year'        => '1993',
                     'director'    => 'Steven Spielberg',
                     'rating'      => '8.9'
                     );
         $data[] = array(
                     'id'          => 9,
                     'title'       => 'The Lord of the Rings: The Return of the King',
                     'description' => 'Gandalf and Aragorn lead the World of Men against Sauron\'s army to draw his gaze from Frodo and Sam as they approach Mount Doom with the One Ring.',
                     'year'        => '2003',
                     'director'    => 'Peter Jackson',
                     'rating'      => '8.9'
                     );
         $data[] = array(
                     'id'          => 10,
                     'title'       => 'Fight Club',
                     'description' => 'An insomniac office worker looking for a way to change his life crosses paths with a devil-may-care soap maker and they form an underground fight club that evolves into something much, much more...',
                     'year'        => '1999',
                     'director'    => 'David Fincher',
                     'rating'      => '8.8'
                     );
         return $data;
     }
     /**
      * Define what data to show on each column of the table
      *
      * @param  Array $item        Data
      * @param  String $column_name - Current column name
      *
      * @return Mixed
      */
     public function column_default( $item, $column_name )
     {
         switch( $column_name ) {
             case 'id':
             case 'title':
             case 'description':
             case 'year':
             case 'director':
             case 'rating':
                 return $item[ $column_name ];
             default:
                 return print_r( $item, true ) ;
         }
     }
     /**
      * Allows you to sort the data by the variables set in the $_GET
      *
      * @return Mixed
      */
     private function sort_data( $a, $b )
     {
         // Set defaults
         $orderby = 'title';
         $order = 'asc';
         // If orderby is set, use this as the sort column
         if(!empty($_GET['orderby']))
         {
             $orderby = $_GET['orderby'];
         }
         // If order is set use this as the order
         if(!empty($_GET['order']))
         {
             $order = $_GET['order'];
         }
         $result = strcmp( $a[$orderby], $b[$orderby] );
         if($order === 'asc')
         {
             return $result;
         }
         return -$result;
     }
 }
 ?>
