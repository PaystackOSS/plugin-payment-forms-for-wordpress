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

	private $plugin_name;
	private $version;
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
		function txncheck($name,$txncharge){
			if ($name == $txncharge) {
				$result = "selected";
			}else{
				$result = "";
			}
			return $result;
		}
		function paystack_setting_page() {
			?>
			 <h1>Paystack Forms API KEYS Settings!</h1>
			 <form method="post" action="options.php">
				    <?php settings_fields( 'paystack-form-settings-group' ); do_settings_sections( 'paystack-form-settings-group' ); ?>
				    <table class="form-table paystack_setting_page">
								<tr valign="top">
								<th scope="row">Mode</th>

								<td>
									<select class="form-control" name="mode" id="parent_id">
										<option value="live" <?php echo txncheck('live',esc_attr( get_option('mode') )) ?>>Live Mode</option>
										<option value="test" <?php echo txncheck('test',esc_attr( get_option('mode') )) ?>>Test Mode</option>
									</select>
								</tr>
								<tr valign="top">
				        <th scope="row">Test Secret Key</th>
				        <td>

								<input type="text" name="tsk" value="<?php echo esc_attr( get_option('tsk') ); ?>" /></td>
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
				        <td><input type="text" name="lpk" value="<?php echo esc_attr( get_option('lpk') ); ?>" /></td>
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
	  		'payments' => __( 'Payments' ),
	  		'date' => __( 'Date' )
	  	);

	  	return $columns;
	  }
		add_action( 'manage_paystack_form_posts_custom_column', 'my_paystack_form_columns', 10, 2 );

		function my_paystack_form_columns( $column, $post_id ) {
			global $post,$wpdb;
			$table = $wpdb->prefix . 'paystack_forms_payments';

			switch( $column ) {
				case 'shortcode' :
					echo '<span class="shortcode">
					<input type="text" class="large-text code" value="[paystack_form id=&quot;'.$post_id.'&quot;]"
					readonly="readonly" onfocus="this.select();"></span>';

					break;
				case 'payments':

						$count_query = 'select count(*) from '.$table.' WHERE post_id = "'.$post_id.'" AND paid = "1"';
						$num = $wpdb->get_var($count_query);

						echo '<a href="'.admin_url('admin.php?page=submissions&form='.$post_id) .'">'. $num.'</a>';
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
				To make an input field compulsory add <code> required="required" </code> to the shortcode <br /><br />
				It should look like this <code> [text name="Full Name" required="required" ]</code>

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
			$events_meta['_currency'] = $_POST['_currency'];
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

add_action( 'admin_menu', 'register_newpage' );

function register_newpage(){
		add_menu_page('paystack', 'paystack', 'administrator','submissions', 'payment_submissions');
		remove_menu_page('submissions');
}
function payment_submissions(){
	$id = $_GET['form'];
	$obj = get_post($id);
	if ($obj->post_type == 'paystack_form') {
		 $amount = get_post_meta($id,'_amount',true);
		 $thankyou = get_post_meta($id,'_successmsg',true);
		 $paybtn = get_post_meta($id,'_paybtn',true);
		 $loggedin = get_post_meta($id,'_loggedin',true);
		 $txncharge = get_post_meta($id,'_txncharge',true);

		 echo "<h1>".$obj->post_title." Payments</h1>";
		 $exampleListTable = new Payments_List_Table();
		 $exampleListTable->prepare_items();
		 ?>
		 <div class="wrap">
				 <div id="icon-users" class="icon32"></div>
				 <?php $exampleListTable->display(); ?>
		 </div>
		 <?php

	}
}

class Paystack_Wp_List_Table{
    public function __construct(){
        add_action( 'admin_menu', array($this, 'add_menu_example_list_table_page' ));
    }
		public function add_menu_example_list_table_page(){
        add_menu_page( '', '', 'manage_options', 'example-list-table.php', array($this, 'list_table_page') );
    }
		public function list_table_page(){
        $exampleListTable = new Example_List_Table();
				$exampleListTable->prepare_items($data);
        ?>
					<div class="wrap">
              <div id="icon-users" class="icon32"></div>
              <?php $exampleListTable->display(); ?>
          </div>
        <?php
    }
}


if( ! class_exists( 'WP_List_Table' ) ) {
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
function format_data($data){
	$new = json_decode($data);
	$text = '';
	if (count($new) > 0) {
		foreach ($new as $key => $item) {
			$text.= '<b>'.$key."</b> :".$item."<br />";
		}
	}
	return $text;
}

class Payments_List_Table extends WP_List_Table{
   public function prepare_items(){
		 	$post_id = $_GET['form'];
			$currency = get_post_meta($post_id,'_currency',true);

			global $wpdb;

		  $table = $wpdb->prefix."paystack_forms_payments";
			$data = array();
			$alldbdata = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '".$post_id."' AND paid = '1')");

			foreach ($alldbdata as $key => $dbdata) {
				$newkey = $key+1;
				$data[] = array(
							'id'  => $newkey,
							'email' => $dbdata->email,
		          'amount' => $currency.'<b>'.number_format($dbdata->amount).'</b>',
		          'txn_code' => $dbdata->txn_code,
		          'metadata' => format_data($dbdata->metadata),
		          'date'  => $dbdata->created_at
				);
			}

       $columns = $this->get_columns();
       $hidden = $this->get_hidden_columns();
       $sortable = $this->get_sortable_columns();
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
	 public function get_columns(){
       $columns = array(
           'id'  => '#',
           'email' => 'Email',
           'amount' => 'Amount',
           'txn_code' => 'Txn Code',
           'metadata' => 'Data',
           'date'  => 'Date'
       );
       return $columns;
   }
   /**
    * Define which columns are hidden
    *
    * @return Array
    */
   public function get_hidden_columns(){
       return array();
   }
  	public function get_sortable_columns(){
       return array('email' => array('email', false),'date' => array('date', false),'amount' => array('amount', false));
   	}
   /**
    * Get the table data
    *
    * @return Array
    */
   private function table_data($data){

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
   public function column_default( $item, $column_name ){
       switch( $column_name ) {
           case 'id':
           case 'email':
           case 'amount':
           case 'txn_code':
           case 'metadata':
           case 'date':
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
   private function sort_data( $a, $b ){
       $orderby = 'date';
       $order = 'asc';
       if(!empty($_GET['orderby'])){
           $orderby = $_GET['orderby'];
       }
       if(!empty($_GET['order'])){
           $order = $_GET['order'];
       }
       $result = strcmp( $a[$orderby], $b[$orderby] );
       if($order === 'asc'){
           return $result;
       }
       return -$result;
   }
}


?>
