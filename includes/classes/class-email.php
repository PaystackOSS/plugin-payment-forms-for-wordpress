<?php
/**
 * The email template all of the email will extend from.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Email template
 */
class Email {

	/**
	 * The slug / key of the email
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * The name to whom you are sending the email.
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The email address you are sending it to.
	 *
	 * @var string
	 */
	public $email = '';

	/**
	 * The reply to email address.
	 *
	 * @var string
	 */
	public $reply_to = '';

	/**
	 * The reply to name or website name.
	 *
	 * @var string
	 */
	public $reply_name = '';

	/**
	 * The subject of the email.
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * The current currency
	 *
	 * @var string
	 */
	public $currency = '';

	/**
	 * The amount to be displayed
	 *
	 * @var string
	 */
	public $amount = '';

	/**
	 * The currency payment code.
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Holds the Form ID
	 *
	 * @var integer
	 */
	public $form_id = 0;

	/**
	 * Holds the meta data.
	 *
	 * @var string
	 */
	public $metadata = '';

	/**
	 * Constructor
	 */
	public function __construct() {
	}

	public function get_email_body() {
		ob_start();
		$this->get_html_header();
		$this->get_html_body();
		$this->get_html_footer();
		$message = ob_get_contents();
		ob_end_clean();
		return $message;
	}

	public function get_html_header() {
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
				<?php 
					// This is for an email and cant run with wp_enqueue_scripts()
					// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
				<link href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700" rel="stylesheet" type="text/css">
				<style type="text/css">
					<?php include( PFF_PAYSTACK_PLUGIN_PATH . '/assets/css/email-' . $this->slug . '.css' ); ?>
				</style>
			</head>
		<?php	
	}

	public function get_html_body() {
		?>
		<body>
		</body>
		<?php
	}

	public function get_html_footer() {
		?>
		</html>
		<?php
	}

	public function get_headers() {
		return array(
			"MIME-Version: 1.0",
			"Content-type:text/html;charset=UTF-8",
			"Reply-To: {$this->reply_to}",
			"From: {$this->reply_name} <{$this->reply_to}>"
		);
	}

	public function send() {
		wp_mail( $this->email, $this->subject, $this->get_email_body(), $this->get_headers() );
	}
}