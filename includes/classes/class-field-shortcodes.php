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
		add_shortcode( 'textarea', [ $this, 'textarea_field' ] );
		add_shortcode( 'input', [ $this, 'input_field' ] );
		add_shortcode( 'checkbox', [ $this, 'checkbox_field' ] );
		add_shortcode( 'radio', [ $this, 'radio_field' ] );
		add_shortcode( 'select', [ $this, 'select_field' ] );
		add_shortcode( 'datepicker', [ $this, 'datepicker_field' ] );
	}

	/**
	 * Generates the "text" input field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function text_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'required' => '0',
			),
			$atts,
			'text'
		);

		// sanitize name attribute before using it.
		$atts['name'] = $this->sanitize_and_escape( $atts['name'] );
		
		// translators: %s: input field name to be entered by the user
		$name     = sanitize_text_field( sprintf( esc_attr__( 'Enter %s', 'pff-paystack' ), $atts['name'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';
		$id       = uniqid( 'text-' );
	
		$code = '<div class="span12 unit">
			<label for="' . esc_attr( $id ) . '" class="label">' . esc_html( $name );
	
		if ( $required ) {
			$code .= ' <span>*</span>';
		}
	
		$code .= '</label>
			<div class="input">
				<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" placeholder="' . $name . '" ' . esc_attr( $required ) . ' /></div></div>';
	
		return $code;
	}
	/**
	 * Generates the "textarea" field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function textarea_field( $atts ) {
		
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'required' => '0',
			),
			$atts,
			'textarea'
		);
		// sanitize name attribute before using it
		$atts['name'] = $this->sanitize_and_escape( $atts['name'] );
		
		// translators: %s: textarea field to be entered by the user
		$name     = sanitize_text_field( sprintf( esc_attr__( 'Enter %s', 'pff-paystack' ), $atts['name'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';

		$id = uniqid( 'textarea-' );

		$code  = '<div class="span12 unit">';
		$code .= '<label for="' . esc_attr( $id ) . '" class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}

		$code .= '</label>';
		$code .= '<div class="input">';
		$code .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="3" placeholder="' . $name . '" ' . esc_attr( $required ) . '></textarea></div></div>';

		return $code;
	}
	/**
	 * Generates the "checkbot" input field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function checkbox_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'options'  => '',
				'required' => '0',
			),
			$atts,
			'checkbox'
		);

		$name     = sanitize_text_field( $atts['name'] );
		$options  = array_map( 'sanitize_text_field', explode( ',', $atts['options'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';

		$code  = '<div class="span12 unit">';
		$code .= '<label class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}

		$code .= '</label>';
		$code .= '<div class="inline-group">';

		foreach ( $options as $option ) {
			$id = uniqid( 'checkbox-' );
			$code .= '<label for="' . esc_attr( $id ) . '" class="checkbox">';
			$code .= '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $option ) . '" ' . esc_attr( $required ) . '>';
			$code .= '<i></i>';
			$code .= esc_html( $option );
			$code .= '</label>';
		}

		$code .= '</div></div>';

		return $code;
	}
	/**
	 * Generates the general "input" input field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function input_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'required' => '0',
			),
			$atts,
			'input'
		);

		$atts['name'] = $this->sanitize_and_escape( $atts['name'] );
		$name       = sanitize_text_field( $atts['name'] );
		
		$required   = $atts['required'] === 'required' ? 'required' : '';
		$fileInputId = uniqid( 'file-input-' );
		$textInputId = uniqid( 'text-input-' );

		$code  = '<div class="span12 unit">';
		$code .= '<label for="' . esc_attr( $fileInputId ) . '" class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}

		$code .= '</label>';
		$code .= '<div class="input append-small-btn">';
		$code .= '<div class="file-button">';
		$code .= esc_html__( 'Browse', 'pff-paystack' );
		$code .= '<input type="file" id="' . esc_attr( $fileInputId ) . '" name="' . esc_attr( $name ) . '" onchange="document.getElementById(\'' . esc_attr( $textInputId ) . '\').value = this.value;" ' . esc_attr( $required ) . '>';
		$code .= '</div>';
		$code .= '<input type="text" id="' . esc_attr( $textInputId ) . '" readonly="" placeholder="' . esc_attr__( 'No file selected', 'pff-paystack' ) . '">';
		$code .= '</div></div>';

		return $code;
	}
	/**
	 * Generates the "datepicker" input field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function datepicker_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'required' => '0',
			),
			$atts,
			'datepicker'
		);

		// sanitize name attribute before using it
        $atts['name'] = $this->sanitize_and_escape( $atts['name'] );	
		
		// translators: %s: datepicker field to be selected by the user
		$name     = sanitize_text_field( sprintf( esc_attr__( 'Enter %s', 'pff-paystack' ), $atts['name'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';
		$id       = uniqid( 'datepicker-' );
	
		$code  = '<div class="span12 unit">';
		$code .= '<label for="' . esc_attr( $id ) . '" class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}
	
		$code .= '</label>';
		$code .= '<div class="input">';
		$code .= '<input type="date" id="' . esc_attr( $id ) . '" class="date-picker" name="' . esc_attr( $name ) . '" placeholder="' . $name . '" ' . esc_attr( $required ) . ' /></div></div>';
	
		return $code;
	}
	/**
	 * Generates the "dropdown" select field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function select_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'options'  => '',
				'required' => '0',
			),
			$atts,
			'select'
		);
	
		$name     = sanitize_text_field( $atts['name'] );
		$options  = array_map( 'sanitize_text_field', explode( ',', $atts['options'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';
		$id       = uniqid( 'select-' );
	
		$code  = '<div class="span12 unit">';
		$code .= '<label for="' . esc_attr( $id ) . '" class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}
	
		$code .= '</label>';
		$code .= '<div class="input">';
		$code .= '<select id="' . esc_attr( $id ) . '" class="form-control" name="' . esc_attr( $name ) . '" ' . esc_attr( $required ) . '>';
	
		foreach ( $options as $option ) {
			$code .= '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
		}
	
		$code .= '</select><i></i></div></div>';
	
		return $code;
	}
	/**
	 * Generates the "radio" input field.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function radio_field( $atts ) {
		$atts = shortcode_atts(
			array(
				'name'     => esc_html__( 'Title', 'pff-paystack' ),
				'options'  => '',
				'required' => '0',
			),
			$atts,
			'radio'
		);
	
		$name     = sanitize_text_field( $atts['name'] );
		$options  = array_map( 'sanitize_text_field', explode( ',', $atts['options'] ) );
		$required = $atts['required'] === 'required' ? 'required' : '';
	
		$code  = '<div class="span12 unit">';
		$code .= '<label class="label">' . esc_html( $name );
		
		if ( $required ) {
			$code .= ' <span>*</span>';
		}
	
		$code .= '</label>';
		$code .= '<div class="inline-group">';
	
		foreach ( $options as $index => $option ) {
			$id        = uniqid( 'radio-' );
			$isChecked = $index == 0 ? 'checked' : '';
			$code     .= '<label for="' . esc_attr( $id ) . '" class="radio">';
			$code     .= '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option ) . '" ' . esc_attr( $isChecked ) . ' ' . esc_attr( $required ) . '>';
			$code     .= '<i></i>';
			$code     .= esc_html( $option );
			$code     .= '</label>';
		}
	
		$code .= '</div></div>';
	
		return $code;
	}

	/**
	 * Sanitize and escape a string for safe HTML output.
	 *
	 * @param string $value The input string to sanitize and escape.
	 * @return string The sanitized and escaped string.
	 */
	private function sanitize_and_escape( $value ) {
	    // Remove all HTML tags, including malformed ones
		$value = wp_kses( $value, array() );

	    // Replace backticks with single quotes
	    $value = str_replace( '`', '&#96;', $value );

	    // Sanitize the string for safe database storage
	    $value = sanitize_text_field( $value );

	    // Escape the string for safe HTML output
	    return esc_html( $value );
	}
}