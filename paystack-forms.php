<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              kendyson.com
 * @since             1.0.0
 * @package           Paystack_Forms
 *
 * @wordpress-plugin
 *Plugin Name: Paystack Forms
 *Plugin URI: http://example.com
 *Description: Make Payment Forms for Paystack
 *Author: Douglas Kendyson
 *Author URI: http://kendyson.com
 * Version:           1.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       paystack-forms
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-paystack-forms-activator.php
 */
function activate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-activator.php';
	Paystack_Forms_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-paystack-forms-deactivator.php
 */
function deactivate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-deactivator.php';
	Paystack_Forms_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_paystack_forms' );
register_deactivation_hook( __FILE__, 'deactivate_paystack_forms' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_paystack_forms() {

	$plugin = new Paystack_Forms();
	$plugin->run();

}
run_paystack_forms();

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
        'menu_icon' => plugins_url('images/logo.png', __FILE__),
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
//     title: Text input field to create a post title.
// editor: Content TinyMCE editor for writing.
// author: A select box for changing the post author.
// thumbnail: Featured image capability.
// excerpt: A textarea for writing a custom excerpt.
// trackbacks: Ability to turn trackbacks and pingbacks on/off.
// custom-fields: Custom fields input field.
// comments: Turn comments on/off.
// revisions: Allows revisions to be made of your post.
// post-formats: Add post formats, see the ‘Post Formats’ section
// page-attributes
    register_post_type( 'paystack_form', $args );
}

add_action( 'init', 'register_cpt_paystack_form' );

function genres_taxonomy() {
    register_taxonomy(
        'genres',
        'paystack_form',
        array(
            'hierarchical' => true,
            'label' => 'Genres',
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'genre',
                'with_front' => false
            )
        )
    );
}
// add_action( 'init', 'genres_taxonomy');
// Function used to automatically create Paystack Forms page.
function create_paystack_form_pages()
  {
   //post status and options
    $post = array(
          'comment_status' => 'closed',
          'ping_status' =>  'closed' ,
          'post_date' => date('Y-m-d H:i:s'),
          'post_name' => 'paystack_form',
          'post_status' => 'publish' ,
          'post_title' => 'Paystack Forms',
          'post_type' => 'page',
    );
    //insert page and save the id
    $newvalue = wp_insert_post( $post, false );
    //save the id in the database
    update_option( 'mrpage', $newvalue );
  }
  register_activation_hook( __FILE__, 'create_paystack_form_pages');
  function html_form_code() {


  //  $args = array('post_type' => 'paystack_form');

//Define the loop based on arguments

       echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
      echo '<p>';
      echo 'Your Name (required) <br />';
      echo '<input type="text" name="cf-name" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["cf-name"] ) ? esc_attr( $_POST["cf-name"] ) : '' ) . '" size="40" />';
      echo '</p>';
      echo '<p>';
      echo 'Your Email (required) <br />';
      echo '<input type="email" name="cf-email" value="' . ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) . '" size="40" />';
      echo '</p>';
      echo '<p>';
      echo 'Subject (required) <br />';
      echo '<input type="text" name="cf-subject" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) . '" size="40" />';
      echo '</p>';
      echo '<p>';
      echo 'Your Message (required) <br />';
      echo '<textarea rows="10" cols="35" name="cf-message">' . ( isset( $_POST["cf-message"] ) ? esc_attr( $_POST["cf-message"] ) : '' ) . '</textarea>';
      echo '</p>';
      echo '<p><input type="submit" name="cf-submitted" value="Send"/></p>';
      echo '</form>';
  }
  function deliver_mail() {

      // if the submit button is clicked, send the email
      if ( isset( $_POST['cf-submitted'] ) ) {

          // sanitize form values
          $name    = sanitize_text_field( $_POST["cf-name"] );
          $email   = sanitize_email( $_POST["cf-email"] );
          $subject = sanitize_text_field( $_POST["cf-subject"] );
          $message = esc_textarea( $_POST["cf-message"] );

          // get the blog administrator's email address
          $to = get_option( 'admin_email' );

          $headers = "From: $name <$email>" . "\r\n";

          // If email has been process for sending, display a success message
          if ( wp_mail( $to, $subject, $message, $headers ) ) {
              echo '<div>';
              echo '<p>Thanks for contacting me, expect a response soon.</p>';
              echo '</div>';
          } else {
              echo 'An unexpected error occurred';
          }
      }
  }
  function cf_shortcode($atts) {
      ob_start();
      extract(shortcode_atts(array(
        'id' => 0,
     ), $atts));
    //  echo "<pres>";
    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
   if ($id != 0) {
       $obj = get_post($id);
       if ($obj->post_type == 'paystack_form') {
         print_r(do_shortcode($obj->post_content));
      }
     }
    //  echo "</pres>";
    echo '</form>';


      // deliver_mail();
      // html_form_code();

      return ob_get_clean();
  }
  add_shortcode( 'paystack_form', 'cf_shortcode' );
  function shortcode_button_script(){
      if(wp_script_is("quicktags"))
      {
          ?>
              <script type="text/javascript">

                  //this function is used to retrieve the selected text from the text editor
                  function getSel()
                  {
                      var txtarea = document.getElementById("content");
                      var start = txtarea.selectionStart;
                      var finish = txtarea.selectionEnd;
                      return txtarea.value.substring(start, finish);
                  }

                  QTags.addButton(
                      "code_shortcode",
                      "Code",
                      callback
                  );

                  function callback()
                  {
                      var selected_text = getSel();
                      QTags.insertContent("[code]" +  selected_text + "[/code]");
                  }
              </script>
          <?php
      }
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
  add_action( 'manage_paystack_form_posts_custom_column', 'my_manage_movie_columns', 10, 2 );

  function my_manage_movie_columns( $column, $post_id ) {
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
  //////

  function text_shortcode($atts) {
    extract(shortcode_atts(array(
      'name' => 'Title',
   ), $atts));
   $text = '<label> '.$name.'<input type="text" name="'.to_slug($name).'" /></label><br />';
    return $text;
  }
  add_shortcode('text', 'text_shortcode');
  function email_shortcode($atts) {
    extract(shortcode_atts(array(
      'name' => 'Email',
   ), $atts));
   $text = '<label>'.$name.'<input type="email" name="'.to_slug($name).'" /></label><br />';
    return $text;
  }
  add_shortcode('email', 'email_shortcode');
  function submit_shortcode($atts) {
    extract(shortcode_atts(array(
      'name' => 'Email',
   ), $atts));
   $text = '<br /><input type="submit" value="'.$name.'"><br />';
    return $text;
  }
  //
  add_shortcode('submit', 'submit_shortcode');
  function textarea_shortcode() {
    return '<textarea></textarea><br />';
  }
  add_shortcode('textarea', 'textarea_shortcode');
  function radio_shortcode() {
    return '<textarea></textarea><br />';
  }
  add_shortcode('radio', 'radio_shortcode');

  function to_slug($text){
      $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
      // $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
      $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
      $text = trim($text, '-');

        // remove duplicate -
      $text = preg_replace('~-+~', '-', $text);

        // lowercase
      $text = strtolower($text);

      if (empty($text)) {
          return 'n-a';
      }

      return $text;
  }
