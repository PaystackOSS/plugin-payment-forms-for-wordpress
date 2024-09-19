<?php
/**
 * The class that will update the forms data.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the additional functions for the WP Dashboard Forms List
 */
class Forms_Update {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_head', [ $this, 'setup_actions' ] );
		add_filter( 'admin_head', [ $this, 'disable_wyswyg' ], 10, 1 );
	}

	public function setup_actions() {
		add_filter( 'user_can_richedit', '__return_false', 50 );
		add_filter( 'quicktags_settings', [ $this, 'remove_fullscreen' ], 10, 1 );

		remove_action( 'media_buttons', 'media_buttons' );
		remove_meta_box( 'postimagediv', 'post', 'side' );

		add_action( 'admin_print_footer_scripts', [ $this, 'shortcode_button_script' ] );
	}

	/**
	 * Outputs CSS to hide the WYSIWYG
	 *
	 * @param string $default
	 * @return string
	 */
	public function disable_wyswyg( $default ) {
		if ( 'paystack_form' === get_post_type() ) {
			?> 
			<style>#edit-slug-box,#message p > a{display:none;}</style>
			<?php
		}
		return $default;
	}

	/**
	 * Remove the fullscreen option
	 *
	 * @param array $arguments
	 * @return array
	 */
	public function remove_fullscreen( $arguments ) {
		$arguments['buttons'] = 'fullscreen';
		return $arguments;
	}

	/**
	 * Outputs the QuickTags scripts needed to generate the shortcode.
	 *
	 * @return void
	 */
	public function shortcode_button_script() {
		if ( wp_script_is( 'quicktags' ) ) {
			?>
			<script type="text/javascript">
			//this function is used to retrieve the selected text from the text editor
			function getSel() {
				var txtarea = document.getElementById( "content" );
				var start = txtarea.selectionStart;
				var finish = txtarea.selectionEnd;
				return txtarea.value.substring(start, finish);
			}
			
			QTags.addButton(
				"t_shortcode",
				"Insert Text",
				insertText
			);
			
			function insertText() {
				QTags.insertContent('[text name="Text Title"]');
			}
			QTags.addButton(
				"ta_shortcode",
				"Insert Textarea",
				insertTextarea
			);
			
			function insertTextarea() {
				QTags.insertContent('[textarea name="Text Title"]');
			}
			QTags.addButton(
				"s_shortcode",
				"Insert Select Dropdown",
				insertSelectb
			);
			
			function insertSelectb() {
				QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"r_shortcode",
				"Insert Radio Options",
				insertRadiob
			);
			
			function insertRadiob() {
				QTags.insertContent('[radio name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"cb_shortcode",
				"Insert Checkbox Options",
				insertCheckboxb
			);
			
			function insertCheckboxb() {
				QTags.insertContent('[checkbox name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"dp_shortcode",
				"Insert Datepicker",
				insertDatepickerb
			);
			
			function insertDatepickerb() {
				QTags.insertContent('[datepicker name="Datepicker Title"]');
			}
			QTags.addButton(
				"i_shortcode",
				"Insert File Upload",
				insertInput
			);
			
			function insertInput() {
				QTags.insertContent('[input name="File Name"]');
			}
			QTags.addButton(
				"ngs_shortcode",
				"Insert Nigerian States",
				insertSelectStates
			);
			
			function insertSelectStates() {
				QTags.insertContent(
					'[select name="State" options="<?php echo esc_attr( pff_paystack()->helpers->get_states( true ) ); ?>"]'
				);
			}
			QTags.addButton(
				"ctys_shortcode",
				"Insert All Countries",
				insertSelectCountries
			);
			
			function insertSelectCountries() {
				QTags.insertContent(
					'[select  name="country" options="<?php echo esc_attr( pff_paystack()->helpers->get_countries( true ) ); ?>"] '
				);
			}
			
			//
			</script>
					<?php
				}
			}
}
