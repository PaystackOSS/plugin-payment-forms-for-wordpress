<?php
/**
 * The Invoice Email sent to the customer before payment.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Submit Class
 */
class Email_Receipt extends Email {

	/**
	 * The slug / key if the email
	 *
	 * @var string
	 */
	public $slug = 'receipt';

	/**
	 * The merchat string
	 *
	 * @var string
	 */
	public $merchant = '';

	/**
	 * The email heading
	 *
	 * @var string
	 */
	public $heading = '';

	/**
	 * The thank you message.
	 *
	 * @var string
	 */
	public $sitemessage = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'pff_paystack_send_receipt', [ $this, 'send_receipt' ], 10, 7 );
	}

	public function send_receipt( $form_id, $currency, $amount, $name, $email, $code, $metadata ) {
		
		// Default Values
		$this->slug       = 'receipt';
		$this->amount     = $amount;
		$this->currency   = $currency;
		$this->code       = $code;
		$this->name       = $name;
		$this->email      = stripslashes( $email );
		$this->metadata   = $metadata;

		// Custom Values
		$this->subject     = get_post_meta( $form_id, '_subject', true );
		$this->merchant    = get_post_meta( $form_id, '_merchant', true );
		$this->heading     = get_post_meta( $form_id, '_heading', true );
		$this->sitemessage = get_post_meta( $form_id, '_message', true );

		$this->reply_to   = get_option( 'admin_email' );
		$this->reply_name = get_option( 'blogname' );
		$this->send();
	}

	public function get_html_body() {
		?>
		<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin:0;padding:0;min-width:100%;background-color:#fff">
			<div class="email_body" style="padding:32px 6px;text-align:center;background-color:#fff">
	
				<div class="email_container" style="display:inline-block;width:100%;vertical-align:top;text-align:center;margin:0 auto;max-width:588px;font-size:0!important">
					<table class="header" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="header_cell col-bottom-0" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;border-top:4px solid;border-bottom:0 solid;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">
									<!-- Header content can be included here -->
								</td>
							</tr>
						</tbody>
					</table>

					<table class="content" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
						<tbody>
							<tr>
								<td class="content_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">

									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">

										<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">&nbsp;</p>
															<h5 style="font-family:Helvetica,Arial,sans-serif;margin-left:0;margin-right:0;margin-top:16px;margin-bottom:8px;padding:0;font-size:18px;line-height:26px;font-weight:bold;color:#383d42">
																<?php echo esc_html( $this->heading ); ?>
															</h5>
															<p align="left" style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">
																<?php 
																printf(
																	/* translators: %s: Customer's first name */
																	esc_html__( 'Hello %s,', 'pff-paystack' ),
																	esc_html( strstr( $this->name . ' ', ' ', true ) )
																); 
																?>
															</p>
															<p align="left" style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">
																<?php echo esc_html( $this->sitemessage ); ?>
															</p>
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:16px;margin-bottom:24px">&nbsp;</p>
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
								<td class="jumbotron_cell invoice_cell" align="center" valign="top" style="padding:0;text-align:center;background-color:#fafafa;font-size:0!important">

									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">

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
																		<td class="font_default" style="padding:3px 7px;font-family:Helvetica,Arial,sans-serif;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:2px;-webkit-border-radius:2px;border-radius:2px;white-space:nowrap;background-color:#666;color:#fff">
																			<?php esc_html_e( 'Your Details', 'pff-paystack' ); ?>
																		</td>
																	</tr>
																</tbody>
															</table>
															<p style="font-family:Helvetica,Arial,sans-serif;font-size:15px;line-height:23px;margin-top:8px;margin-bottom:16px">
																<?php 
																printf(
																	// translators: %1$s is the currency code, %2$s is the formatted amount
																	esc_html__( 'Amount : %1$s %2$s', 'pff-paystack' ),
																	esc_html( $this->currency ),
																	esc_html( number_format_i18n( $this->amount ) )
																);
																?><br>
																<?php 
																printf(
																	/* translators: %1$s is the Email */
																	esc_html__( 'Email : %1$s', 'pff-paystack' ),
																	esc_html( $this->email )
																);
																?><br>
																<?php
																$new = json_decode( $this->metadata );
																if ( array_key_exists( '0', $new ) ) {
																	foreach ( $new as $key => $item ) {
																		if ( 'text' === $item->type ) {
																			echo sprintf(
																				/* translators: %1$s: Display name, %2$s: Value */
																				'%1$s <strong> : %2$s</strong><br>',
																				esc_html( $item->display_name ),
																				esc_html( $item->value )
																			);
																		} else {
																			echo sprintf(
																				/* translators: %s: Display name */
																				'%1$s <strong> : <a target="_blank" href="%2$s">link</a></strong><br>',
																				esc_html( $item->display_name ),
																				esc_url( $item->value )
																			);
																		}
																	}
																} else {
																	if ( count( (array) $new ) > 0 ) {
																		foreach ( $new as $key => $item ) {
																			echo sprintf(
																				/* translators: %1$s: Key, %2$s: Item */
																				'%1$s <strong> : %2$s</strong><br>',
																				esc_html( $key ),
																				esc_html( $item )
																			);
																		}
																	}
																}
																printf(
																	/* translators: %s: Transaction code */
																	esc_html__( 'Transaction code: %s', 'pff-paystack' ),
																	esc_html( $this->code )
																);
																?><br>
															</p>
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
								<td class="jumbotron_cell product_row" align="center" valign="top" style="padding:0 0 16px;text-align:center;background-color:#f2f2f5;border-left:4px solid;border-right:4px solid;border-top:1px solid;border-color:#d8dde4;font-size:0!important">

									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">

										<div class="col-3" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px 16px 0;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:center;vertical-align:top;color:#888">
															<small style="font-size:86%;font-weight:normal">
																<strong><?php esc_html_e( 'Notice', 'pff-paystack' ); ?></strong><br>
																<?php
																printf(
																	/* translators: %s: Payment amount */
																	esc_html__( "You're getting this email because you've made a payment of %s to ", 'pff-paystack' ),
																	esc_html( $this->currency . ' ' . number_format_i18n( $this->amount ) )
																);
																?>
																<a href="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>" style="display:inline-block;text-decoration:none;font-family:Helvetica,Arial,sans-serif;color:#2f68b4">
																	<?php echo esc_html( get_option( 'blogname' ) ); ?>
																</a>.
															</small>
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
								<td class="footer_cell" align="center" valign="top" style="padding:0;text-align:center;padding-bottom:16px;border-top:1px solid;border-bottom:4px solid;background-color:#fff;border-left:4px solid;border-right:4px solid;border-color:#d8dde4;font-size:0!important">
									<div class="row" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:580px;margin:0 auto">
										<div class="col-13 col-bottom-0" style="display:inline-block;width:100%;vertical-align:top;text-align:center;max-width:390px">
											<table class="column" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;width:100%;vertical-align:top">
												<tbody>
													<tr>
														<td class="column_cell font_default" align="center" valign="top" style="padding:16px;font-family:Helvetica,Arial,sans-serif;font-size:15px;text-align:left;vertical-align:top;color:#b3b3b5;padding-bottom:0;padding-top:16px">
															<strong><?php echo esc_html( get_option( 'blogname' ) ); ?></strong><br>
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
		<?php
	}
}