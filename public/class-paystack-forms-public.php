<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       kendyson.com
 * @since      1.0.0
 *
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/public
 * @author     kendysond <kendyson@kendyson.com>
 */
class Paystack_Forms_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/paystack-forms-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_register_script('Paystack', 'https://js.paystack.co/v1/inline.js', false, '1');
		wp_enqueue_script('Paystack');
		wp_enqueue_script( 'paystack_frontend', plugin_dir_url( __FILE__ ) . 'js/paystack-forms-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( 'paystack_frontend', 'php_vars', ['fish'=> 'OKAY!'] );

	}

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
  echo '<form class="paystack-form" action="' . admin_url('admin-ajax.php') . '" url="' . admin_url() . '" method="post">';
  echo '<input type="hidden" name="action" value="paystack_submit_action">';

  echo '<input type="hidden" name="pf-id" value="' . $id . '" />';
	echo '<p>';
  echo 'Your Email (required) <br />';
  echo '<input type="email" name="pf-pemail"  required/>';
  echo '</p>';
	echo '<p>';
  echo 'Amount <br />';
  echo '<input type="number" name="pf-amount"  required/>';
  echo '</p>';
  if ($id != 0) {
     $obj = get_post($id);
     if ($obj->post_type == 'paystack_form') {
       print_r(do_shortcode($obj->post_content));
    }
   }
  echo '</form>';


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

//////

function text_shortcode($atts) {
  extract(shortcode_atts(array(
    'name' => 'Title',
 ), $atts));
 $text = '<label> '.$name.'<input type="text" name="'.$name.'" /></label><br />';
  return $text;
}
add_shortcode('text', 'text_shortcode');
function email_shortcode($atts) {
  extract(shortcode_atts(array(
    'name' => 'Email',
 ), $atts));
 $text = '<label>'.$name.'<input type="email" name="'.$name.'" /></label><br />';
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

    extract(shortcode_atts(array(
      'name' => 'Email',
   ), $atts));
   return '<textarea name="'.$name.'"></textarea><br />';
}
add_shortcode('textarea', 'textarea_shortcode');
function radio_shortcode() {
  return '<textarea></textarea><br />';
}
add_shortcode('radio', 'radio_shortcode');

function to_slug($text){
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}


// Save the Metabox Data


add_action( 'wp_ajax_paystack_submit_action', 'paystack_submit_action' );
add_action( 'wp_ajax_nopriv_paystack_submit_action', 'paystack_submit_action' );
function generate_new_code($length = 10){
  // $characters = 'RSTUVW01234ABCDEFGHIJ56789KLMNOPQXYZ';
  $characters = '06EFGHI9KL'.time().'MNOPJRSUVW01YZ923234'.time().'ABCD5678QXT';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return time()."_".$randomString;
}
function check_code($code){
global $wpdb;
$table = $wpdb->prefix."paystack_forms_payments";
$o_exist = $wpdb->get_results("SELECT * FROM $table WHERE txn_code = '".$code."'");

  if (count($o_exist) > 0) {
      $result = true;
  } else {
      $result = false;
  }

  return $result;
}
function generate_code(){
  $code = 0;
  $check = true;
  while ($check) {
      $code = generate_new_code();
      $check = check_code($code);
  }

  return $code;
}
function get_the_user_ip() {
if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
	$ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
	$ip = $_SERVER['REMOTE_ADDR'];
}
return $ip;
}

function paystack_submit_action() {
  if (trim($_POST['pf-pemail']) == '') {
    $response['error'] = true;
  	$response['error_message'] = 'Email is required';

  	// Exit here, for not processing further because of the error
  	exit(json_encode($response));
  }
  // print_r($_POST);
  global $wpdb;
	$code = generate_code();

  $table = $wpdb->prefix."paystack_forms_payments";
	$metadata = $_POST;
	unset($metadata['action']);
	unset($metadata['pf-id']);
	unset($metadata['pf-pemail']);
	unset($metadata['pf-amount']);
	$insert =  array(
        'post_id' => strip_tags($_POST["pf-id"], ""),
				'email' => strip_tags($_POST["pf-pemail"], ""),
        'user_id' => strip_tags($_POST["pf-user_id"], ""),
        'amount' => strip_tags($_POST["pf-amount"], ""),
				'ip' => get_the_user_ip(),
				'txn_code' => $code,
				'metadata' => json_encode($metadata)
      );

	print_r($insert_array);
  $exist = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '".$insert['post_id']."'
			AND post_id = '".$insert['post_id']."'
			AND email = '".$insert['email']."'
			AND user_id = '".$insert['user_id']."'
			AND amount = '".$insert['amount']."'
			AND ip = '".$insert['ip']."'
			AND paid = '0'
			AND metadata = '". $insert['metadata'] ."')");
	 if (count($exist) > 0) {
		 $insert['txn_code'] = $exist[0]->txn_code;

   } else {
		 $wpdb->insert(
	        $table,
	        $insert
	    );
   }

	 $response = array(
     'result' => 'success',
     'code' => $insert['txn_code'],
     'email' => $insert['email'],
   	 'total' => $insert['amount']*100,
   );
  echo json_encode($response);

  die();
}
