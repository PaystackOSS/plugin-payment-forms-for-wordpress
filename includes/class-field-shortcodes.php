<?php
/**
 * The shortcodes for the frontend display
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The shortcodes for the form content, the non required fields.
 */
class Field_Shortcodes {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_shortcode( 'text', [ $this, 'text_field' ] );
	}

	public function text_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => __( 'Title', 'paystack_forms' ),
				'required' => '0',
			),
			$atts,
			'text'
		);
	
		$name     = sanitize_text_field( $atts['name'] );
		$required = $atts['required'] === 'required' ? 'required' : '';
		$id       = uniqid( 'text-' );
	
		$code = '<div class="span12 unit">
			<label for="' . esc_attr( $id ) . '" class="label">' . esc_html( $name );
	
		if ( $required ) {
			$code .= ' <span>*</span>';
		}
	
		$code .= '</label>
			<div class="input">
				<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" placeholder="' . sprintf( esc_attr__( 'Enter %s', 'text-domain' ), $name ) . '" ' . esc_attr( $required ) . ' /></div></div>';
	
		return $code;
	}	
}
