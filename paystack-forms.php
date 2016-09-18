<?php
/*
	Plugin Name:	Payment forms for Paystack
	Plugin URI: 	https://github.com/Kendysond/Wordpress-paystack-forms
	Description: 	Payment forms for Paystack allows you create forms that will be used to bill clients for goods and services via Paystack.
	Version: 		1.0.8
	Author: 		Douglas Kendyson
	Author URI: 	http://kendyson.com
	License:        GPL-2.0+
	License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
define( 'KKD_PFF_PAYSTACK_PLUGIN_PATH', plugins_url( __FILE__ ) );
define( 'KKD_PFF_PAYSTACK_MAIN_FILE', __FILE__ );
define( 'KKD_PFF_PAYSTACK_VERSION', '1.0.4' );
define( 'KKD_PFF_PAYSTACK_TABLE', 'paystack_forms_payments' );


// fix some badly enqueued scripts with no sense of HTTPS
add_action('wp_print_scripts', 'kkd_pff_paystack_enqueueScriptsFix', 100);
add_action('wp_print_styles', 'kkd_pff_paystack_enqueueStylesFix', 100);

/**
* force plugins to load scripts with SSL if page is SSL
*/
function kkd_pff_paystack_enqueueScriptsFix() {
    if (!is_admin()) {
        if (!empty($_SERVER['HTTPS'])) {
            global $wp_scripts;
            foreach ((array) $wp_scripts->registered as $script) {
                if (stripos($script->src, 'http://', 0) !== FALSE)
                    $script->src = str_replace('http://', 'https://', $script->src);
            }
        }
    }
}

/**
* force plugins to load styles with SSL if page is SSL
*/
function kkd_pff_paystack_enqueueStylesFix() {
    if (!is_admin()) {
        if (!empty($_SERVER['HTTPS'])) {
            global $wp_styles;
            foreach ((array) $wp_styles->registered as $script) {
                if (stripos($script->src, 'http://', 0) !== FALSE)
                    $script->src = str_replace('http://', 'https://', $script->src);
            }
        }
    }
}



function kkd_pff_paystack_activate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-activator.php';
	Kkd_Pff_Paystack_Activator::activate();
}

register_activation_hook( __FILE__, 'kkd_pff_paystack_activate_paystack_forms' );


require plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms.php';

function kkd_pff_paystack_run_paystack_forms() {

	$plugin = new Kkd_Pff_Paystack();
	$plugin->run();

}
kkd_pff_paystack_run_paystack_forms();

function kkd_pff_paystack_shortcode_button_script(){
    if(wp_script_is("quicktags")){
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
              "t_shortcode",
              "Insert Text",
              insertText
          );
					function insertText(){
              QTags.insertContent('[text name="Text Title"]');
          }
					QTags.addButton(
              "ta_shortcode",
              "Insert Textarea",
              insertTextarea
          );
					function insertTextarea(){
              QTags.insertContent('[textarea name="Text Title"]');
          }
					QTags.addButton(
              "s_shortcode",
              "Insert Select Dropdown",
              insertSelectb
          );
					function insertSelectb(){
              QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 2"]');
          }
					QTags.addButton(
							"i_shortcode",
							"Insert File Upload",
							insertInput
					);
					function insertInput(){
							QTags.insertContent('[input name="File Name"]');
					}
      </script>
  <?php
    }
}
add_action( 'init', 'kkd_pff_paystack_invoice_url_rewrite' );
function kkd_pff_paystack_invoice_url_rewrite(){
    global $wp_rewrite;
    $plugin_url = plugins_url( 'includes/paystack-invoice.php', __FILE__ );
		$plugin_url = substr( $plugin_url, strlen( home_url() ) + 1 );
    $wp_rewrite->non_wp_rules['paystackinvoice.php$'] = $plugin_url;
    file_put_contents(ABSPATH.'.htaccess', $wp_rewrite->mod_rewrite_rules() );
}
