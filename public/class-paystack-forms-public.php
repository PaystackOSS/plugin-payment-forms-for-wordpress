<?php

require_once(ABSPATH . "wp-admin" . '/includes/image.php');
require_once(ABSPATH . "wp-admin" . '/includes/file.php');
require_once(ABSPATH . "wp-admin" . '/includes/media.php');

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
		// settings_fields( 'paystack-form-settings-group' ); do_settings_sections( 'paystack-form-settings-group' );
		$mode =  esc_attr( get_option('mode') );
		if ($mode == 'test') {
			$key = esc_attr( get_option('tpk') );
		}else{
			$key = esc_attr( get_option('lpk') );
		}
		wp_enqueue_script( 'blockUI', plugin_dir_url( __FILE__ ) . 'js/jquery.blockUI.min.js', array( 'jquery' ), $this->version, false );
		wp_register_script('Paystack', 'https://js.paystack.co/v1/inline.js', false, '1');
		wp_enqueue_script('Paystack');
		wp_enqueue_script( 'paystack_frontend', plugin_dir_url( __FILE__ ) . 'js/paystack-forms-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( 'paystack_frontend', 'settings', ['key'=> $key]);

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
add_filter ("wp_mail_content_type", "my_awesome_mail_content_type");
function my_awesome_mail_content_type() {
	return "text/html";
}
add_filter ("wp_mail_from_name", "my_awesome_mail_from_name");
function my_awesome_email_from_name() {
	$name = get_option( 'blogname' );
	return $name;
}


function send_invoice($currency,$amount,$name,$email,$code){
	//  echo date('F j,Y');
	$user_email = stripslashes($email);

	$email_subject = "Payment Invoice for ".$currency.' '.number_format($amount);

		ob_start();
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<meta name="format-detection" content="address=no">
	<meta name="format-detection" content="email=no">
	<title></title>
	<link href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700" rel="stylesheet" type="text/css">
	<style type="text/css">body{Margin:0;padding:0;min-width:100%}a,#outlook a{display:inline-block}a,a span{text-decoration:none}img{line-height:1;outline:0;border:0;text-decoration:none;-ms-interpolation-mode:bicubic;mso-line-height-rule:exactly}table{border-spacing:0;mso-table-lspace:0;mso-table-rspace:0}td{padding:0}.email_summary{display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden}.font_default,h1,h2,h3,h4,h5,h6,p,a{font-family:Helvetica,Arial,sans-serif}small{font-size:86%;font-weight:normal}.pricing_box_cell small{font-size:74%}.font_default,p{font-size:15px}p{line-height:23px;Margin-top:16px;Margin-bottom:24px}.lead{font-size:19px;line-height:27px;Margin-bottom:16px}.header_cell .column_cell{font-size:20px;font-weight:bold}.header_cell p{margin-bottom:0}h1,h2,h3,h4,h5,h6{Margin-left:0;Margin-right:0;Margin-top:16px;Margin-bottom:8px;padding:0}.line-through{text-decoration:line-through}h1,h2{font-size:26px;line-height:36px;font-weight:bold}.pricing_box h1,.pricing_box h2,.primary_pricing_box h1,.primary_pricing_box h2{line-height:20px;Margin-top:16px;Margin-bottom:0}h3,h4{font-size:22px;line-height:30px;font-weight:bold}h5{font-size:18px;line-height:26px;font-weight:bold}h6{font-size:16px;line-height:24px;font-weight:bold}.primary_btn td,.secondary_btn td{font-size:16px;mso-line-height-rule:exactly}.primary_btn a,.secondary_btn a{font-weight:bold}.email_body{padding:32px 10px;text-align:center}.email_container,.row,.col-1,.col-13,.col-2,.col-3{display:inline-block;width:100%;vertical-align:top;text-align:center}.email_container{width:100%;margin:0 auto}.email_container,.row,.col-3{max-width:580px}.col-1{max-width:190px}.col-2{max-width:290px}.col-13{max-width:390px}.row{margin:0 auto}.column{width:100%;vertical-align:top}.column_cell{padding:16px;text-align:center;vertical-align:top}.col-bottom-0 .column_cell{padding-bottom:0}.col-top-0 .column_cell{padding-top:0}.email_container,.header_cell,.jumbotron_cell,.content_cell,.footer_cell,.image_responsive{font-size:0!important;text-align:center}.header_cell,.footer_cell{padding-bottom:16px}.header_cell .column_cell,.footer_cell .col-13 .column_cell,.footer_cell .col-1 .column_cell{text-align:left;padding-top:16px}.header_cell{-webkit-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0}.header_cell img{max-width:156px;height:auto}.footer_cell{text-align:center;-webkit-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px}.footer_cell p{Margin:16px 0}.invoice_cell .column_cell{text-align:left;padding-top:0;padding-bottom:0}.invoice_cell p{margin-top:8px;margin-bottom:16px}.pricing_box{border-collapse:separate;padding:10px 16px;-webkit-border-radius:4px;border-radius:4px}.primary_pricing_box{border-collapse:separate;padding:18px 16px;-webkit-border-radius:4px;border-radius:4px}.text_quote .column_cell{border-left:4px solid;text-align:left;padding-right:0;padding-top:0;padding-bottom:0}.primary_btn,.secondary_btn{clear:both;margin:0 auto}.primary_btn td,.secondary_btn td{text-align:center;vertical-align:middle;padding:12px 24px;-webkit-border-radius:4px;border-radius:4px}.primary_btn a,.primary_btn span,.secondary_btn a,.secondary_btn span{text-align:center;display:block}.label .font_default{font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;padding:3px 7px;-webkit-border-radius:2px;border-radius:2px;white-space:nowrap}.icon_holder,.hruler{width:62px;margin-left:auto;margin-right:auto;clear:both}.icon_holder{width:48px}.hspace,.hruler_cell{font-size:0;height:8px;overflow:hidden}.hruler_cell{height:4px;line-height:4px}.icon_cell{font-size:0;line-height:1;-webkit-border-radius:80px;border-radius:80px;padding:8px;height:48px}.product_row{padding:0 0 16px}.product_row .column_cell{padding:16px 16px 0}.image_thumb img{-webkit-border-radius:4px;border-radius:4px}.product_row .col-13 .column_cell{text-align:left}.product_row h6{Margin-top:0}.product_row p{Margin-top:8px;Margin-bottom:8px}.order_total_right .column_cell{text-align:right}.order_total_left .column_cell{text-align:left}.order_total p{Margin:8px 0}.order_total h2{Margin:8px 0}.image_responsive img{display:block;width:100%;height:auto;max-width:580px;margin-left:auto;margin-right:auto}body,.email_body{background-color:#f2f2f2}.header_cell,.footer_cell,.content_cell{background-color:#fff}.secondary_btn td,.icon_primary .icon_cell,.primary_pricing_box{background-color:#ffb26b}.jumbotron_cell,.pricing_box{background-color:#fafafa}.primary_btn td,.label .font_default{background-color:#666}.icon_secondary .icon_cell{background-color:#dbdbdb}.label_1 .font_default{background-color:#62a9dd}.label_2 .font_default{background-color:#8965ad}.label_3 .font_default{background-color:#df6164}.primary_btn a,.primary_btn span,.secondary_btn a,.secondary_btn span,.label .font_default,.primary_pricing_box,.primary_pricing_box h1,.primary_pricing_box small{color:#fff}h2,h4,h5,h6{color:#666}.column_cell{color:#888}h1,h3,a,a span,.text-secondary,.column_cell .text-secondary,.content_cell h2 .text-secondary{color:#ffb26b}.footer_cell a,.footer_cell a span{color:#7a7a7a}.text-muted,.footer_cell .column_cell,.content h4 span,.content h3 span{color:#b3b3b5}.footer_cell,.product_row,.order_total{border-top:1px solid}.product_row,.order_total,.icon_secondary .icon_cell,.footer_cell,.content .product_row,.content .order_total,.pricing_box,.text_quote .column_cell{border-color:#f2f2f2}@media screen{h1,h2,h3,h4,h5,h6,p,a,.font_default{font-family:"Noto Sans",Helvetica,Arial,sans-serif!important}.primary_btn td,.secondary_btn td{padding:0!important}.primary_btn a,.secondary_btn a{padding:12px 24px!important}}@media screen and (min-width:631px) and (max-width:769px){.col-1,.col-2,.col-3,.col-13{float:left!important}.col-1{width:200px!important}.col-2{width:300px!important}}@media screen and (max-width:630px){.jumbotron_cell{background-size:cover!important}.row,.col-1,.col-13,.col-2,.col-3{max-width:100%!important}}</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;min-width:100%;background-color:#f2f2f2">
	<div class="email_body" style="padding:32px 10px;text-align:center;background-color:#f2f2f2">
	<div class="email_container" style="display:inline-block;width:100%;vertical-align:top;text-align:center;margin:0 auto;max-width:580px;font-size:0!important">
	<table class="header" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="header_cell col-bottom-0" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;-webkit-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0;background-color:#fff;font-size:0!important">
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:20px;text-align:left;vertical-align:top;color:#ffb26b;font-weight:bold;padding-bottom:0;padding-top:16px">
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;font-size:0!important">
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:28px;line-height:23px;margin-top:16px;margin-bottom:24px"><small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5">
	<a href="#" style="display:inline-block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#ffb26b"><strong class="text-muted" style="color:#b3b3b5">Invoice #<?php  echo $code; ?></strong></a></p>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	<table class="jumbotron" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="jumbotron_cell product_row" align="center" valign="top" style="padding:0 0 16px;text-align:center;background-color:#fff;border-top:1px solid;border-color:#f2f2f2;font-size:0!important">
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<div class="col-13" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#888">
	<small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5"><?php echo date('F j,Y');?></small>
	<h6 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:0;margin-bottom:8px;padding:0;font-size:16px;line-height:24px;font-weight:bold;color:#666"><?php  echo $name; ?></h6>
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:8px;margin-bottom:8px"><?php  echo $email; ?></p>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<div class="col-1" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:190px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="left" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
	<h1 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:16px;margin-bottom:8px;padding:0;font-size:26px;line-height:36px;font-weight:bold;color:#ffb26b"><?php  echo $currency.' '.number_format($amount); ?></h1></td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;font-size:0!important">
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">You're getting this email because <br />you tried making a payment to <?php echo get_option( 'blogname' );?>.</p>
	<table class="primary_btn" align="center" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;clear:both;margin:0 auto">
	<tbody>
	<tr>
	<!-- <p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px"><small class="text-muted" style="font-size:86%;font-weight:normal;color:#b3b3b5">Use this link below to try again, if you encountered <br />any issue while trying to make the payment.</small><br>
	</p>
	<td class="font_default" style="padding:12px 24px;font-family:Helvetica,Arial,sans-serif;font-size:16px;mso-line-height-rule:exactly;text-align:center;vertical-align:middle;-webkit-border-radius:4px;border-radius:4px;background-color:#666">
	<a href="<?php  echo $code; ?>" style="display:block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#fff;font-weight:bold;text-align:center">
	<span style="text-decoration:none;color:#fff;text-align:center;display:block">Try Again</span>
	</a>
	</td> -->
	</tr>
	</tbody>
	</table>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	<table class="footer" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="footer_cell" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;-webkit-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px;background-color:#fff;border-top:1px solid;border-color:#f2f2f2;font-size:0!important">
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<div class="col-13 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
	<strong><?php echo get_option( 'blogname' );?></strong><br>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<div class="col-1 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:190px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	</body>
	</html>
	<?php

	$message = ob_get_contents();
	ob_end_clean();
	// $admin_email = get_option( 'admin_email' );

	$admin_email = get_option( 'admin_email' );
	$website = get_option( 'blogname' );
	$headers = array( 'Reply-To: ' . $admin_email,"From: $website <$admin_email>" . "\r\n");
	$headers = "From: ".$website."<$admin_email>" . "\r\n";
	wp_mail($user_email, $email_subject, $message,$headers);

}
// send_receipt($currency,$amount_paid,$fullname,$payment_array->email,$paystack_ref,$payment_array->metadata);

function send_receipt($id,$currency,$amount,$name,$email,$code,$metadata){
	//  echo date('F j,Y');
	$user_email = stripslashes($email);
	$subject = get_post_meta($id, '_subject', true);
	$heading = get_post_meta($id, '_heading', true);
	$sitemessage = get_post_meta($id, '_message', true);

	$email_subject =$subject;// "Payment Invoice for ".$currency.' '.number_format($amount);

		ob_start();
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="date=no">
	<meta name="format-detection" content="address=no">
	<meta name="format-detection" content="email=no">
	<title></title>
	<link href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700" rel="stylesheet" type="text/css">
	<style type="text/css">body{Margin:0;padding:0;min-width:100%}a,#outlook a{display:inline-block}a,a span{text-decoration:none}img{line-height:1;outline:0;border:0;text-decoration:none;-ms-interpolation-mode:bicubic;mso-line-height-rule:exactly}table{border-spacing:0;mso-table-lspace:0;mso-table-rspace:0}td{padding:0}.email_summary{display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden}.font_default,h1,h2,h3,h4,h5,h6,p,a{font-family:Helvetica,Arial,sans-serif}small{font-size:86%;font-weight:normal}.pricing_box_cell small{font-size:74%}.font_default,p{font-size:15px}p{line-height:23px;Margin-top:16px;Margin-bottom:24px}.lead{font-size:19px;line-height:27px;Margin-bottom:16px}.header_cell .column_cell{font-size:20px;font-weight:bold}.header_cell p{margin-bottom:0}h1,h2,h3,h4,h5,h6{Margin-left:0;Margin-right:0;Margin-top:16px;Margin-bottom:8px;padding:0}.line-through{text-decoration:line-through}h1,h2{font-size:26px;line-height:36px;font-weight:bold}.pricing_box h1,.pricing_box h2,.primary_pricing_box h1,.primary_pricing_box h2{line-height:20px;Margin-top:16px;Margin-bottom:0}h3,h4{font-size:22px;line-height:30px;font-weight:bold}h5{font-size:18px;line-height:26px;font-weight:bold}h6{font-size:16px;line-height:24px;font-weight:bold}.primary_btn td,.secondary_btn td{font-size:16px;mso-line-height-rule:exactly}.primary_btn a,.secondary_btn a{font-weight:bold}.email_body{padding:32px 6px;text-align:center}.email_container,.row,.col-1,.col-13,.col-2,.col-3{display:inline-block;width:100%;vertical-align:top;text-align:center}.email_container{width:100%;margin:0 auto}.email_container{max-width:588px}.row,.col-3{max-width:580px}.col-1{max-width:190px}.col-2{max-width:290px}.col-13{max-width:390px}.row{margin:0 auto}.column{width:100%;vertical-align:top}.column_cell{padding:16px;text-align:center;vertical-align:top}.col-bottom-0 .column_cell{padding-bottom:0}.col-top-0 .column_cell{padding-top:0}.email_container,.header_cell,.jumbotron_cell,.content_cell,.footer_cell,.image_responsive{font-size:0!important;text-align:center}.header_cell,.footer_cell{padding-bottom:16px}.header_cell .column_cell,.footer_cell .col-13 .column_cell,.footer_cell .col-1 .column_cell{text-align:left;padding-top:16px}.header_cell img{max-width:156px;height:auto}.footer_cell{text-align:center}.footer_cell p{Margin:16px 0}.invoice_cell .column_cell{text-align:left;padding-top:0;padding-bottom:0}.invoice_cell p{margin-top:8px;margin-bottom:16px}.pricing_box{border-collapse:separate;padding:10px 16px}.primary_pricing_box{border-collapse:separate;padding:18px 16px}.text_quote .column_cell{border-left:4px solid;text-align:left;padding-right:0;padding-top:0;padding-bottom:0}.primary_btn,.secondary_btn{clear:both;margin:0 auto}.primary_btn td,.secondary_btn td{text-align:center;vertical-align:middle;padding:12px 24px}.primary_btn a,.primary_btn span,.secondary_btn a,.secondary_btn span{text-align:center;display:block}.label .font_default{font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;padding:3px 7px;white-space:nowrap}.icon_holder,.hruler{width:62px;margin-left:auto;margin-right:auto;clear:both}.icon_holder{width:48px}.hspace,.hruler_cell{font-size:0;height:8px;overflow:hidden}.hruler_cell{height:4px;line-height:4px}.icon_cell{font-size:0;line-height:1;padding:8px;height:48px}.product_row{padding:0 0 16px}.product_row .column_cell{padding:16px 16px 0}.product_row .col-13 .column_cell{text-align:left}.product_row h6{Margin-top:0}.product_row p{Margin-top:8px;Margin-bottom:8px}.order_total_right .column_cell{text-align:right}.order_total_left .column_cell{text-align:left}.order_total p{Margin:8px 0}.order_total h2{Margin:8px 0}.image_responsive img{display:block;width:100%;height:auto;max-width:580px;margin-left:auto;margin-right:auto}body,.email_body,.header_cell,.content_cell,.footer_cell{background-color:#fff}.secondary_btn td,.icon_primary .icon_cell,.primary_pricing_box{background-color:#2f68b4}.jumbotron_cell,.pricing_box{background-color:#f2f2f5}.primary_btn td,.label .font_default{background-color:#22aaa0}.icon_secondary .icon_cell{background-color:#e1e3e7}.label_1 .font_default{background-color:#62a9dd}.label_2 .font_default{background-color:#8965ad}.label_3 .font_default{background-color:#df6164}.primary_btn a,.primary_btn span,.secondary_btn a,.secondary_btn span,.label .font_default,.primary_pricing_box,.primary_pricing_box h1,.primary_pricing_box small{color:#fff}h2,h4,h5,h6{color:#383d42}.column_cell{color:#888}.header_cell .column_cell,.header_cell a,.header_cell a span,h1,h3,a,a span,.text-secondary,.column_cell .text-secondary,.content_cell h2 .text-secondary{color:#2f68b4}.footer_cell a,.footer_cell a span{color:#7a7a7a}.text-muted,.footer_cell .column_cell,.content h4 span,.content h3 span{color:#b3b3b5}.header_cell,.footer_cell{border-top:4px solid;border-bottom:4px solid}.header_cell,.footer_cell,.jumbotron_cell,.content_cell{border-left:4px solid;border-right:4px solid}.footer_cell,.product_row,.order_total{border-top:1px solid}.header_cell,.footer_cell,.jumbotron_cell,.content_cell,.product_row,.order_total,.icon_secondary .icon_cell,.footer_cell,.content .product_row,.content .order_total,.pricing_box,.text_quote .column_cell{border-color:#d8dde4}@media screen{h1,h2,h3,h4,h5,h6,p,a,.font_default{font-family:"Noto Sans",Helvetica,Arial,sans-serif!important}.primary_btn td,.secondary_btn td{padding:0!important}.primary_btn a,.secondary_btn a{padding:12px 24px!important}}@media screen and (min-width:631px) and (max-width:769px){.col-1,.col-2,.col-3,.col-13{float:left!important}.col-1{width:200px!important}.col-2{width:300px!important}}@media screen and (max-width:630px){.jumbotron_cell{background-size:cover!important}.row,.col-1,.col-13,.col-2,.col-3{max-width:100%!important}}</style>
	</head>
	<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;min-width:100%;background-color:#fff">
	<div class="email_body" style="padding:32px 6px;text-align:center;background-color:#fff">
	<!--[if (gte mso 9)|(IE)]>
	<table width="588" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="588" align="center" valign="top">
	<![endif]-->
	<div class="email_container" style="display:inline-block;width:100%;vertical-align:top;text-align:center;margin:0 auto;max-width:588px;font-size:0!important">
	<table class="header" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="header_cell col-bottom-0" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;border-top:4px solid;border-bottom:0 solid;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</td>
	</tr>
	</tbody>
	</table>
	<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">&nbsp; </p>
	<h5 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:16px;margin-bottom:8px;padding:0;font-size:18px;line-height:26px;font-weight:bold;color:#383d42"><?php echo $heading; ?></h5>
	<p align="left" style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">Hello <?php echo strstr($name." ", " ", true); ?>,</p>
	<p align="left" style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px"><?php echo $sitemessage; ?></p>
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">&nbsp; </p>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</td>
	</tr>
	</tbody>
	</table>
	<table class="jumbotron" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="jumbotron_cell invoice_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fafafa;font-size:0!important">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="190" align="center" valign="top">
	<![endif]-->
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:left">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#888;padding-top:0;padding-bottom:0">
	<table class="label" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="hspace" style="padding:0;font-size:0;height:8px;overflow:hidden">&nbsp;</td>
	</tr>
	<tr>
	<td class="hspace" style="padding:0;font-size:0;height:8px;overflow:hidden">&nbsp;</td>
	</tr>
	<tr>
	<td class="font_default" style="padding:3px 7px;font-family:Helvetica,Arial,sans-serif;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;-webkit-border-radius:2px;border-radius:2px;white-space:nowrap;background-color:#666;color:#fff">Your Details</td>
	</tr>
	</tbody>
	</table>
	<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:8px;margin-bottom:16px">
		Amount <strong> : <?php echo $currency.' '.number_format($amount); ?></strong><br>
		Email <strong> :  <?php echo $user_email; ?></strong><br>
		<?php
		$new = json_decode($metadata);
		if (array_key_exists("0", $new)) {
			foreach ($new as $key => $item) {
				if ($item->type == 'text') {
					echo $item->display_name."<strong>  :".$item->value."</strong><br>";
				}else{
					echo $item->display_name."<strong>  : <a target='_blank' href='".$item->value."'>link</a></strong><br>";
				}

			}
		}else{
			$text = '';
			if (count($new) > 0) {
				foreach ($new as $key => $item) {
					echo $key."<strong>  :".$item."</strong><br />";
				}
			}
		}
		?>
		Transaction code: <strong> <?php echo $code; ?></strong><br>
	</p>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</td>
	</tr>
	</tbody>
	</table>
	<table class="jumbotron" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="jumbotron_cell product_row" align="center" valign="top" style="padding:0 0 16px;text-align:center;background-color:#f2f2f5;border-left:4px solid;border-right:4px solid;border-top:1px solid;border-color:#d8dde4;font-size:0!important">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
	<small style="font-size:86%;font-weight:normal"><strong>Notice</strong><br>
	You're getting this email because you've made a payment of <?php $currency.' '.number_format($amount); ?> to <a href="<?php echo get_bloginfo('url') ?>" style="display:inline-block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#2f68b4"><?php echo get_option( 'blogname' );?></a>.</small>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</td>
	</tr>
	</tbody>
	</table>
	<table class="footer" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
	<tbody>
	<tr>
	<td class="footer_cell" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;border-top:1px solid;border-bottom:4px solid;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="580" align="center" valign="top">
	<![endif]-->
	<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
	<!--[if (gte mso 9)|(IE)]>
	<table width="580" border="0" cellspacing="0" cellpadding="0" align="center" style="vertical-align:top">
	<tbody>
	<tr>
	<td width="390" align="center" valign="top">
	<![endif]-->
	<div class="col-13 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
	<strong><?php echo get_option( 'blogname' );?></strong><br>
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	<td width="190" align="center" valign="top">
	<![endif]-->
	<div class="col-1 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:190px">
	<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
	<tbody>
	<tr>
	<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</td>
	</tr>
	</tbody>
	</table>
	</div>
	<!--[if (gte mso 9)|(IE)]>
	</td>
	</tr>
	</tbody>
	</table>
	<![endif]-->
	</div>
	</body>
	</html>

	<?php

	$message = ob_get_contents();
	ob_end_clean();
	$admin_email = get_option('admin_email');
	$website = get_option('blogname');
	$headers = array( 'Reply-To: ' . $admin_email,"From: $website <$admin_email>" . "\r\n");
	$headers = "From: ".$website."<$admin_email>" . "\r\n";
	wp_mail($user_email, $email_subject, $message,$headers);

}
function cf_shortcode($atts) {
    ob_start();
		if ( is_user_logged_in() ) {
	    $user_id = get_current_user_id();
		}else{
			$user_id = 0;
		}

    extract(shortcode_atts(array(
      'id' => 0,
   ), $atts));
  if ($id != 0) {
     $obj = get_post($id);
		 if ($obj->post_type == 'paystack_form') {
			 $amount = get_post_meta($id,'_amount',true);
			 $thankyou = get_post_meta($id,'_successmsg',true);
			 $paybtn = get_post_meta($id,'_paybtn',true);
			 $loggedin = get_post_meta($id,'_loggedin',true);
			 $txncharge = get_post_meta($id,'_txncharge',true);
			 $currency = get_post_meta($id,'_currency',true);
			//  print_r($loggedin);
			 if ($loggedin == 'no') {
			 echo "<h1 id='pf-form".$id."'>".$obj->post_title."</h1>";
			 echo '<form enctype="multipart/form-data" class="paystack-form" action="' . admin_url('admin-ajax.php') . '" url="' . admin_url() . '" method="post">';
		   echo '<input type="hidden" name="action" value="paystack_submit_action">';
			 echo '<input type="hidden" name="pf-id" value="' . $id . '" />';
			 echo '<input type="hidden" name="pf-user_id" value="' . $user_id. '" />';
		 	 echo '<p>';
			 echo 'Full Name  (required)<br />';
		   echo '<input type="text" name="pf-fname" class="form-control" required/>';
			 echo '</p>';
 			echo 'Email (required)<br />';
		   echo '<input type="email" name="pf-pemail" class="form-control"  id="pf-email" required/>';
		   echo '</p>';
		 	 echo '<p>';
		   echo 'Amount ('.$currency.') <br />';
			 if ($amount == 0) {
				 echo '<input type="number" name="pf-amount" class="form-control pf-number" id="pf-amount" required/>';
			 }else{
				 echo '<input type="number" name="pf-amount" value="'.$amount.'" id="pf-amount" readonly required/>';
			 }
			  echo '</p>';
		   echo(do_shortcode($obj->post_content));

			//  echo '<br /><p>Transaction charge:'.$currency.'<b class="txn_charge">13,000</b></p>';
			//  echo '<p>Total charge:'.$currency.'<b class="total_charge">13,000</b></p>';
			 echo '<p> <br /><input type="submit" class="btn btn-danger" value="'.$paybtn.'" ></p>';

			 echo '<img src="'. plugins_url( '../images/logos@2x.png' , __FILE__ ) .'" alt="cardlogos"  class="paystack-cardlogos size-full wp-image-1096" />';
		   echo '</form>';
			 # code...
		 }else{
			 echo "<h5>You must be logged in to make payment</h5>";
		 }

    }
   }



    return ob_get_clean();
}
add_shortcode( 'paystack_form', 'cf_shortcode' );


//////

function text_shortcode($atts) {
  extract(shortcode_atts(array(
		'name' => 'Title',
    'required' => '0',
 	), $atts));
	$code = '<label> '.$name.'<input  class="form-control"  type="text" name="'.$name.'"';
	if ($required == 'required') {
		 $code.= ' required="required" ';
	}
	$code.= '" /></label><br />';
  return $code;
}
add_shortcode('text', 'text_shortcode');
function select_shortcode($atts) {
	extract(shortcode_atts(array(
		'name' => 'Title',
		'options' => '',
    'required' => '0',
 	), $atts));
	$code = '<label> '.$name.'<br /><select class="form-control" name="'.$name.'"';

	if ($required == 'required') {
		 $code.= ' required="required" ';
	}
	$code.=" style='width:100%;'>";
	$soptions = explode(',', $options);
	if (count($soptions) > 0) {
		foreach ($soptions as $key => $option) {
			$code.= '<option  value="'.$option.'" >'.$option.'</option>';
		}
	}
	$code.= '" </select></label><br />';
  return $code;
}
add_shortcode('select', 'select_shortcode');
function textarea_shortcode($atts) {
	extract(shortcode_atts(array(
      'name' => 'Title',
			'required' => '0',
	 ), $atts));
	 $code = '<label> '.$name.'<textarea class="form-control"  rows="3" name="'.$name.'"';
 	if ($required == 'required') {
 		 $code.= ' required="required" ';
 	}
 	$code.= '" ></textarea></label><br />';
   return $code;
}
add_shortcode('textarea', 'textarea_shortcode');
function input_shortcode($atts) {
  extract(shortcode_atts(array(
		'name' => 'Title',
    'required' => '0',
 	), $atts));
	$code = '<label> '.$name.'<br /><input  class="form-control"  type="file" name="'.$name.'"';
	if ($required == 'required') {
		 $code.= ' required="required" ';
	}
	$code.= '" /></label><br />';
  return $code;
}
add_shortcode('input', 'input_shortcode');
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

add_action( 'wp_ajax_paystack_submit_action', 'paystack_submit_action' );
add_action( 'wp_ajax_nopriv_paystack_submit_action', 'paystack_submit_action' );
function paystack_submit_action() {
  if (trim($_POST['pf-pemail']) == '') {
    $response['result'] = 'failed';
  	$response['message'] = 'Email is required';

  	// Exit here, for not processing further because of the error
  	exit(json_encode($response));
  }

  global $wpdb;
	$code = generate_code();

  $table = $wpdb->prefix."paystack_forms_payments";
	$metadata = $_POST;
	$fullname = $_POST['pf-fname'];
	unset($metadata['action']);
	unset($metadata['pf-id']);
	unset($metadata['pf-pemail']);
	unset($metadata['pf-amount']);
	unset($metadata['pf-user_id']);

			// echo '<pre>';

	$fixedmetadata = paystack_meta_as_custom_fields($metadata);

	$filelimit = get_post_meta($_POST["pf-id"],'_filelimit',true);
	$currency = get_post_meta($_POST["pf-id"],'_currency',true);

	$maxFileSize = $filelimit * 1024 * 1024;

	if(!empty($_FILES)){
		foreach ($_FILES as $keyname => $value) {
			if ($value['size'] > 0) {
				if ($value['size'] > $maxFileSize) {
					$response['result'] = 'failed';
			  	$response['message'] = 'Max upload size is '.$filelimit."MB";
					exit(json_encode($response));
				}else{
					$attachment_id = media_handle_upload($keyname, $_POST["pf-id"]);
					$url = wp_get_attachment_url( $attachment_id);
					$fixedmetadata[] = [
						'display_name' => ucwords(str_replace("_", " ", $keyname)),
						'variable_name' => $keyname,
			      'type' => 'link',
			      'value' => $url
					];
				}
			}else{
				$fixedmetadata[] = [
					'display_name' => ucwords(str_replace("_", " ", $keyname)),
					'variable_name' => $keyname,
		      'type' => 'text',
		      'value' => 'No file Uploaded'
				];
			}

		}
	}

	$insert =  array(
        'post_id' => strip_tags($_POST["pf-id"], ""),
				'email' => strip_tags($_POST["pf-pemail"], ""),
        'user_id' => strip_tags($_POST["pf-user_id"], ""),
        'amount' => strip_tags($_POST["pf-amount"], ""),
				'ip' => get_the_user_ip(),
				'txn_code' => $code,
				'metadata' => json_encode($fixedmetadata)
      );
	// print_r($fixedmetadata);
	// print_r($_FILES);
	// die();

	$exist = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '".$insert['post_id']."'
			AND email = '".$insert['email']."'
			AND user_id = '".$insert['pf-user_id']."'
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
			send_invoice($currency,$insert['amount'],$fullname,$insert['email'],$code);
	}

	 $response = array(
     'result' => 'success',
     'code' => $insert['txn_code'],
		 'email' => $insert['email'],
     'name' => $fullname,
   	 'total' => $insert['amount']*100,
		 'custom_fields' => $fixedmetadata
   );
  echo json_encode($response);

  die();
}

function paystack_meta_as_custom_fields($metadata){
	$custom_fields = [];
	foreach ($metadata as $key => $value) {
		if ($key == 'pf-fname') {
			$custom_fields[] = [
				'display_name' => 'Full Name',
				'variable_name' => 'Full_Name',
	      'type' => 'text',
	      'value' => $value
			];
		}else{
			$custom_fields[] = [
				'display_name' => ucwords(str_replace("_", " ", $key)),
				'variable_name' => $key,
	      'type' => 'text',
	      'value' => $value
			];
		}

	}
	return $custom_fields;
}

add_action( 'wp_ajax_paystack_confirm_payment', 'paystack_confirm_payment' );
add_action( 'wp_ajax_nopriv_paystack_confirm_payment', 'paystack_confirm_payment' );
function paystack_confirm_payment() {
  if (trim($_POST['code']) == '') {
    $response['error'] = true;
  	$response['error_message'] = "Did you make a payment?";

  	exit(json_encode($response));
  }
  global $wpdb;
	$table = $wpdb->prefix."paystack_forms_payments";
	$code = $_POST['code'];
	$record = $wpdb->get_results("SELECT * FROM $table WHERE (txn_code = '".$code."')");
	if (array_key_exists("0", $record)) {

		$payment_array = $record[0];
		$amount = get_post_meta($payment_array->post_id,'_amount',true);
		$currency = get_post_meta($payment_array->post_id,'_currency',true);


		$mode =  esc_attr( get_option('mode') );
		if ($mode == 'test') {
			$key = esc_attr( get_option('tsk') );
		}else{
			$key = esc_attr( get_option('lsk') );
		}
		$paystack_url = 'https://api.paystack.co/transaction/verify/' . $code;
		$headers = array(
			'Authorization' => 'Bearer ' . $key
		);
		$args = array(
			'headers'	=> $headers,
			'timeout'	=> 60
		);
		$request = wp_remote_get( $paystack_url, $args );
		if( ! is_wp_error( $request ) && 200 == wp_remote_retrieve_response_code( $request ) ) {
			$paystack_response = json_decode( wp_remote_retrieve_body( $request ) );
			if ( 'success' == $paystack_response->data->status ) {
						$amount_paid	= $paystack_response->data->amount / 100;
						$paystack_ref 	= $paystack_response->data->reference;

						if ($amount == 0) {
							$wpdb->update( $table, array( 'paid' => 1,'amount' =>$amount_paid),array('txn_code'=>$paystack_ref));
							$thankyou = get_post_meta($payment_array->post_id,'_successmsg',true);
							$message = $thankyou;
							$result = "success";
							// send_receipt($currency,$amount,$name,$payment_array->email,$code,$metadata)
						}else{
							if( $amount !=  $amount_paid ) {
								$message = "Invalid amount Paid. Amount required is ".$currency."<b>".number_format($amount)."</b>";
								$result = "failed";
							}else{

								$wpdb->update( $table, array( 'paid' => 1),array('txn_code'=>$paystack_ref));
								$thankyou = get_post_meta($payment_array->post_id,'_successmsg',true);
								$message = $thankyou;
								$result = "success";
							}
						}
			}else {
				$message = "Transaction Failed/Invalid Code";
				$result = "failed";
			}

		}
	}else{
		$message = "Payment Verification Failed.";
		$result = "failed";

	}

	if ($result == 'success') {
		$sendreceipt = get_post_meta($payment_array->post_id, '_sendreceipt', true);
		if($sendreceipt == 'yes'){
			$decoded = json_decode($payment_array->metadata);
			$fullname = $decoded[0]->value;
			send_receipt($payment_array->post_id,$currency,$amount_paid,$fullname,$payment_array->email,$paystack_ref,$payment_array->metadata);

		}

	}

	 $response = array(
     'result' => $result,
     'message' => $message,
   );
  echo json_encode($response);

  die();
}
