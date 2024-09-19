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

		// Define the meta boxes.
		add_action( 'edit_form_after_title', [ $this, 'metabox_action' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
	}

	public function setup_actions() {
		add_filter( 'user_can_richedit', '__return_false', 50 );
		add_filter( 'quicktags_settings', [ $this, 'remove_fullscreen' ], 10, 1 );

		remove_action( 'media_buttons', 'media_buttons' );
		remove_meta_box( 'postimagediv', 'post', 'side' );

		add_action( 'admin_print_footer_scripts', [ $this, 'shortcode_buttons_script' ] );
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
	 * Outputs the QuickTags scripts needed to generate the field shortcodes.
	 *
	 * @return void
	 */
	public function shortcode_buttons_script() {
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

	/**
	 * Adds in a custom action to allow us to hook into just under the forms title.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function metabox_action( $post ) {
		do_meta_boxes( null, 'pff-paystack-metabox-holder', $post );
	}

	/**
	 * Registers our custom metaboxes.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		if ( isset( $_GET['action'] ) ) {
			add_meta_box( 'pff_paystack_editor_help_shortcode', __( 'Paste shortcode on preferred page', 'paystack_form' ), [ $this, 'shortcode_details' ], 'paystack_form', 'pff-paystack-metabox-holder' );
		}
		add_meta_box( 'pff_paystack_editor_help_data', __( 'Help Section', 'paystack_forms' ), [ $this, 'help_details' ], 'paystack_form', 'pff-paystack-metabox-holder' );

	}
	
	/**
	 * Output the shortcode details
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function shortcode_details( $post ) {
		?>
		<p class="description">
			<label for="wpcf7-shortcode"><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'paystack_forms' ); ?></label>
			<span class="shortcode wp-ui-highlight">
				<input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pff-paystack id=&quot;<?php echo esc_html( $post->ID ); ?>&quot;]">
			</span>
		</p>
		<?php
	}

	/**
	 * Outputs the help details below the title.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function help_details( $post ) {
			// We shall output 1 Nonce Field for all of our metaboxes.
			wp_nonce_field( 'pff-paystack-save-form', 'pff_paystack_save' );
			?>
			<div class="awesome-meta-admin">
				<?php echo wp_kses_post( __( 'Email and Full Name field is added automatically, no need to include that.<br /><br />
				To make an input field compulsory add <code> required="required" </code> to the shortcode <br /><br />
				It should look like this <code> [text name="Full Name" required="required" ]</code><br /><br />' ) ) ; ?>

				<?php echo wp_kses_post( __( '<b style="color:red;">Warning:</b> Using the file input field may cause data overload on your server.
				Be sure you have enough server space before using it. You also have the ability to set file upload limits.' ) ) ; ?>
			</div>
		<?php
	}
}
