<?php

/*
	Plugin Name:	Payment forms for Paystack
	Plugin URI: 	https://github.com/Kendysond/Wordpress-paystack-forms
	Description: 	Payment forms for Paystack allows you create forms that will be used to bill clients for goods and services via Paystack.
	Version: 		1.0.0
	Author: 		Douglas Kendyson
	Author URI: 	http://kendyson.com
	License:        GPL-2.0+
	License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-paystack-forms-activator.php
 */
function activate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-activator.php';
	Paystack_Forms_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-paystack-forms-deactivator.php
 */
function deactivate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-deactivator.php';
	Paystack_Forms_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_paystack_forms' );
register_deactivation_hook( __FILE__, 'deactivate_paystack_forms' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_paystack_forms() {

	$plugin = new Paystack_Forms();
	$plugin->run();

}
run_paystack_forms();

function shortcode_button_script(){
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
                    insertSelect
                );
								function insertSelect(){
                    QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 2"]');
                }
								QTags.addButton(
										"s_shortcode",
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
