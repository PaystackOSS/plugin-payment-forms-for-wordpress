<?php
/*
	Plugin Name:	Payment forms for Paystack
	Plugin URI: 	https://github.com/Kendysond/Wordpress-paystack-forms
	Description: 	Payment forms for Paystack allows you create forms that will be used to bill clients for goods and services via Paystack.
	Version: 		2.0.6
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
define( 'KKD_PFF_PAYSTACK_VERSION', '2.0.4' );
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
              QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 3"]');
          }
          QTags.addButton(
              "r_shortcode",
              "Insert Radio Options",
              insertRadiob
          );
          function insertRadiob(){
              QTags.insertContent('[radio name="Text Title" options="option 1,option 2,option 3"]');
          }
					QTags.addButton(
							"i_shortcode",
							"Insert File Upload",
							insertInput
					);
					function insertInput(){
							QTags.insertContent('[input name="File Name"]');
					}
          QTags.addButton(
              "ngs_shortcode",
              "Insert Nigerian States",
              insertSelectStates
          );
          function insertSelectStates(){
              QTags.insertContent('[select name="State" options="Abia,Adamawa,Akwa Ibom,Anambra,Bauchi,Bayelsa,Benue,Borno,Cross River,Delta,Ebonyi,Edo,Ekiti,Enugu,FCT,Gombe,Imo,Jigawa,Kaduna,Kano,Katsina,Kebbi,Kogi,Kwara,Lagos,Nasarawa,Niger,Ogun,Ondo,Osun,Oyo,Plateau,Rivers,Sokoto,Taraba,Yobe,Zamfara"]');
          }
          QTags.addButton(
              "ctys_shortcode",
              "Insert All Countries",
              insertSelectCountries
          );
          function insertSelectCountries(){
              QTags.insertContent('[select  name="country" options="Algeria,Angola,Benin,Botswana,Burkina Faso,Burundi,Cabo Verde,Cameroon,Central African Republic (CAR),Chad,Comoros,Democratic Republic of the Congo,Republic of the Congo,Cote d\'Ivoire,Djibouti,Egypt,Equatorial Guinea,Eritrea,Ethiopia,Gabon,Gambia,Ghana,Guinea,Guinea-Bissau,Kenya,  Lesotho,Liberia,Libya,Madagascar,Malawi,Mali,Mauritania,Mauritius,Morocco,Mozambique,Namibia,Niger,Nigeria,   Rwanda,Sao Tome and Principe,Senegal,Seychelles,Sierra Leone,Somalia,South Africa,South Sudan,Sudan,   Swaziland,Tanzania,Togo,Tunisia,Uganda,Zambia,Zimbabwe"] ');
          }
          
          //
      </script>
  <?php
    }
}
// add_action( 'init', 'kkd_pff_paystack_invoice_url_rewrite' );
// function kkd_pff_paystack_invoice_url_rewrite(){
//     global $wp_rewrite;
//     $plugin_url = plugins_url( 'includes/paystack-invoice.php', __FILE__ );
// 		$plugin_url = substr( $plugin_url, strlen( home_url() ) + 1 );
//     $wp_rewrite->non_wp_rules['paystackinvoice$'] = $plugin_url; WP_PLUGIN_URL . '/data-fetcher/list-data.php'
//     $wp_rewrite->non_wp_rules['paystackinvoice/$'] = $plugin_url;
//     file_put_contents(ABSPATH.'.htaccess', $wp_rewrite->mod_rewrite_rules() );
// }
// add_action( 'init', 'my_rewrite' );
// function my_rewrite() {
//     global $wp_rewrite;
//     add_rewrite_rule('paystackinvoice$', KKD_PFF_PAYSTACK_PLUGIN_PATH. 'includes/paystack-invoice.php', 'top');
//     add_rewrite_rule('paystackinvoice/$', KKD_PFF_PAYSTACK_PLUGIN_PATH. 'includes/paystack-invoice.php', 'top');
//     // add_rewrite_rule('paystackinvoice/$', plugins_url( 'includes/paystack-invoice.php', __FILE__ ), 'top');
//     $wp_rewrite->flush_rules(true);  // This should really be done in a plugin activation
// }

add_action( 'init', 'kkd_pff_init' );
function kkd_pff_init() {
    add_rewrite_rule( '^paystackinvoice$', 'index.php?kkd_pff_stats=true', 'top' );
}

// But WordPress has a whitelist of variables it allows, so we must put it on that list
add_action( 'query_vars', 'kkd_pff_query_vars' );
function kkd_pff_query_vars( $query_vars )
{
    $query_vars[] = 'kkd_pff_stats';
    return $query_vars;
}

// If this is done, we can access it later
// This example checks very early in the process:
// if the variable is set, we include our page and stop execution after it
add_action( 'parse_request', 'kkd_pff_parse_request' );
function kkd_pff_parse_request( &$wp )
{
    if ( array_key_exists( 'kkd_pff_stats', $wp->query_vars ) ) {
        include( dirname( __FILE__ ) . '/includes/paystack-invoice.php' );
        exit();
    }
}
// add_action( 'init', 'kkd_pff_paystack_webhook_url_rewrite' );
// function kkd_pff_paystack_webhook_url_rewrite(){
//     global $wp_rewrite;
//     $plugin_url = plugins_url( 'includes/paystack-webhook.php', __FILE__ );
//     $plugin_url = substr( $plugin_url, strlen( home_url() ) + 1 );
//     $wp_rewrite->non_wp_rules['kkd_wppf_webhoook$'] = $plugin_url;
//     $wp_rewrite->non_wp_rules['kkd_wppf_webhoook/$'] = $plugin_url;
//     file_put_contents(ABSPATH.'.htaccess', $wp_rewrite->mod_rewrite_rules() );
// }