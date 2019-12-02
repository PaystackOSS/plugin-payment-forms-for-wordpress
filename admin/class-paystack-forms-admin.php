<?php

class Kkd_Pff_Paystack_Admin
{
    private $plugin_name;
    private $version;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', 'kkd_pff_paystack_add_settings_page');
        add_action('admin_init', 'kkd_pff_paystack_register_setting_page');

        function kkd_pff_paystack_add_settings_page()
        {
            add_submenu_page('edit.php?post_type=paystack_form', 'Settings', 'Settings', 'edit_posts', basename(__FILE__), 'kkd_pff_paystack_setting_page');
        }
        function kkd_pff_paystack_register_setting_page()
        {
            register_setting('kkd-pff-paystack-settings-group', 'mode');
            register_setting('kkd-pff-paystack-settings-group', 'tsk');
            register_setting('kkd-pff-paystack-settings-group', 'tpk');
            register_setting('kkd-pff-paystack-settings-group', 'lsk');
            register_setting('kkd-pff-paystack-settings-group', 'lpk');

            register_setting('kkd-pff-paystack-settings-group', 'prc');
            register_setting('kkd-pff-paystack-settings-group', 'ths');
            register_setting('kkd-pff-paystack-settings-group', 'adc');
            register_setting('kkd-pff-paystack-settings-group', 'cap');
        }
        function kkd_pff_paystack_txncheck($name, $txncharge)
        {
            if ($name == $txncharge) {
                $result = "selected";
            } else {
                $result = "";
            }
            return $result;
        }
        function kkd_pff_paystack_setting_page()
        {
            ?>
            <div class="wrap">
                <h1>Paystack Forms Settings</h1>

                <!-- <h4>Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="https://dashboard.paystack.co/#/settings/developer">here</a> to the URL below<strong style="color: red"><pre><code><?php echo admin_url("admin-ajax.php") . "?action=kkd_paystack_pff"; ?></code></pre></strong></h4> -->
                <h2>API Keys Settings</h2>
                <span>Get your API Keys <a href="https://dashboard.paystack.co/#/settings/developer" target="_blank">here</a> </span>
                <form method="post" action="options.php">
                    <?php settings_fields('kkd-pff-paystack-settings-group');
                                do_settings_sections('kkd-pff-paystack-settings-group'); ?>
                    <table class="form-table paystack_setting_page">
                        <tr valign="top">
                            <th scope="row">Mode</th>

                            <td>
                                <select class="form-control" name="mode" id="parent_id">
                                    <option value="test" <?php echo kkd_pff_paystack_txncheck('test', esc_attr(get_option('mode'))) ?>>Test Mode</option>
                                    <option value="live" <?php echo kkd_pff_paystack_txncheck('live', esc_attr(get_option('mode'))) ?>>Live Mode</option>
                                </select>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Test Secret Key</th>
                            <td>
                                <input type="text" name="tsk" value="<?php echo esc_attr(get_option('tsk')); ?>" /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Test Public Key</th>
                            <td><input type="text" name="tpk" value="<?php echo esc_attr(get_option('tpk')); ?>" /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Live Secret Key</th>
                            <td><input type="text" name="lsk" value="<?php echo esc_attr(get_option('lsk')); ?>" /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">Live Public Key</th>
                            <td><input type="text" name="lpk" value="<?php echo esc_attr(get_option('lpk')); ?>" /></td>
                        </tr>

                    </table>

                    <hr>

                    <table class="form-table paystack_setting_page" id="paystack_setting_fees">
                        <h2>Fees Settings</h2>

                        <tr valign="top">
                            <th scope="row">Percentage</th>
                            <td><input type="text" name="prc" value="<?php echo esc_attr(get_option('prc', 1.5)); ?>" /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Threshold <br> <small>(amount above which Paystack adds the fixed amount below)</small></th>
                            <td><input type="text" name="ths" value="<?php echo esc_attr(get_option('ths', 2500)); ?>" /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Additional Charge <br> <small> (amount added to percentage fee when transaction amount is above threshold) </small></th>
                            <td><input type="text" name="adc" value="<?php echo esc_attr(get_option('adc', 100)); ?>" /></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">Cap <br> <small> (maximum charge paystack can charge on your transactions)</small></th>
                            <td><input type="text" name="cap" value="<?php echo esc_attr(get_option('cap', 2000)); ?>" /></td>
                        </tr>
                    </table>

                    <?php submit_button(); ?>

                </form>
            </div>
            <?php
                    }
                    add_action('init', 'register_kkd_pff_paystack');
                    function register_kkd_pff_paystack()
                    {
                        $labels = array(
                            'name' => _x('Paystack Forms', 'paystack_form'),
                            'singular_name' => _x('Paystack Form', 'paystack_form'),
                            'add_new' => _x('Add New', 'paystack_form'),
                            'add_new_item' => _x('Add Paystack Form', 'paystack_form'),
                            'edit_item' => _x('Edit Paystack Form', 'paystack_form'),
                            'new_item' => _x('Paystack Form', 'paystack_form'),
                            'view_item' => _x('View Paystack Form', 'paystack_form'),
                            'all_items' => _x('All Forms', 'paystack_form'),
                            'search_items' => _x('Search Paystack Forms', 'paystack_form'),
                            'not_found' => _x('No Paystack Forms found', 'paystack_form'),
                            'not_found_in_trash' => _x('No Paystack Forms found in Trash', 'paystack_form'),
                            'parent_item_colon' => _x('Parent Paystack Form:', 'paystack_form'),
                            'menu_name' => _x('Paystack Forms', 'paystack_form'),
                        );

                        $args = array(
                            'labels' => $labels,
                            'hierarchical' => true,
                            'description' => 'Paystack Forms filterable by genre',
                            'supports' => array('title', 'editor'),
                            'public' => true,
                            'show_ui' => true,
                            'show_in_menu' => true,
                            'menu_position' => 5,
                            'menu_icon' => plugins_url('../images/logo.png', __FILE__),
                            'show_in_nav_menus' => true,
                            'publicly_queryable' => true,
                            'exclude_from_search' => false,
                            'has_archive' => false,
                            'query_var' => true,
                            'can_export' => true,
                            'rewrite' => false,
                            'comments' => false,
                            'capability_type' => 'post'
                        );
                        register_post_type('paystack_form', $args);
                    }
                    add_filter('user_can_richedit', 'kkd_pff_paystack_disable_wyswyg');

                    function kkd_pff_paystack_add_view_payments($actions, $post)
                    {
                        if (get_post_type() === 'paystack_form') {
                            unset($actions['view']);
                            unset($actions['quick edit']);
                            $url = add_query_arg(
                                array(
                                    'post_id' => $post->ID,
                                    'action' => 'submissions',
                                )
                            );
                            $actions['export'] = '<a href="' . admin_url('admin.php?page=submissions&form=' . $post->ID) . '" >View Payments</a>';
                        }
                        return $actions;
                    }
                    add_filter('page_row_actions', 'kkd_pff_paystack_add_view_payments', 10, 2);


                    function kkd_pff_paystack_remove_fullscreen($qtInit)
                    {
                        $qtInit['buttons'] = 'fullscreen';
                        return $qtInit;
                    }
                    function kkd_pff_paystack_disable_wyswyg($default)
                    {
                        global $post_type, $_wp_theme_features;


                        if ($post_type == 'paystack_form') {
                            echo "<style>#edit-slug-box,#message p > a{display:none;}</style>";
                            add_action("admin_print_footer_scripts", "kkd_pff_paystack_shortcode_button_script");
                            add_filter('user_can_richedit', '__return_false', 50);
                            add_action('wp_dashboard_setup', 'kkd_pff_paystack_remove_dashboard_widgets');
                            remove_action('media_buttons', 'media_buttons');
                            remove_meta_box('postimagediv', 'post', 'side');
                            add_filter('quicktags_settings', 'kkd_pff_paystack_remove_fullscreen');
                        }

                        return $default;
                    }
                    function kkd_pff_paystack_remove_dashboard_widgets()
                    {
                        remove_meta_box('dashboard_right_now', 'dashboard', 'normal');   // Right Now
                        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
                        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');  // Incoming Links
                        remove_meta_box('dashboard_plugins', 'dashboard', 'normal');   // Plugins
                        remove_meta_box('dashboard_quick_press', 'dashboard', 'side');  // Quick Press
                        remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');  // Recent Drafts
                        remove_meta_box('dashboard_primary', 'dashboard', 'side');   // WordPress blog
                        remove_meta_box('dashboard_secondary', 'dashboard', 'side');   // Other WordPress News
                        // use 'dashboard-network' as the second parameter to remove widgets from a network dashboard.
                    }
                    add_filter('manage_edit-paystack_form_columns', 'kkd_pff_paystack_edit_dashboard_header_columns');

                    function kkd_pff_paystack_edit_dashboard_header_columns($columns)
                    {
                        $columns = array(
                            'cb' => '<input type="checkbox" />',
                            'title' => __('Name'),
                            'shortcode' => __('Shortcode'),
                            'payments' => __('Payments'),
                            'date' => __('Date')
                        );

                        return $columns;
                    }
                    add_action('manage_paystack_form_posts_custom_column', 'kkd_pff_paystack_dashboard_table_data', 10, 2);

                    function kkd_pff_paystack_dashboard_table_data($column, $post_id)
                    {
                        global $post, $wpdb;
                        $table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;

                        switch ($column) {
                            case 'shortcode':
                                echo '<span class="shortcode">
					<input type="text" class="large-text code" value="[pff-paystack id=&quot;' . $post_id . '&quot;]"
					readonly="readonly" onfocus="this.select();"></span>';

                                break;
                            case 'payments':

                                $count_query = 'select count(*) from ' . $table . ' WHERE post_id = "' . $post_id . '" AND paid = "1"';
                                $num = $wpdb->get_var($count_query);

                                echo '<u><a href="' . admin_url('admin.php?page=submissions&form=' . $post_id) . '">' . $num . '</a></u>';
                                break;
                            default:
                                break;
                        }
                    }
                    add_filter('default_content', 'kkd_pff_paystack_editor_content', 10, 2);

                    function kkd_pff_paystack_editor_content($content, $post)
                    {
                        switch ($post->post_type) {
                            case 'paystack_form':
                                $content = '[text name="Phone Number"]';
                                break;
                            default:
                                $content = '';
                                break;
                        }

                        return $content;
                    }
                    /////
                    function kkd_pff_paystack_editor_help_metabox($post)
                    {
                        do_meta_boxes(null, 'custom-metabox-holder', $post);
                    }
                    add_action('edit_form_after_title', 'kkd_pff_paystack_editor_help_metabox');

                    function kkd_pff_paystack_editor_help_metabox_details($post)
                    {
                        echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                            wp_create_nonce(plugin_basename(__FILE__)) . '" />'; ?>
                    <div class="awesome-meta-admin">
                        Email and Full Name field is added automatically, no need to include that.<br /><br />
                        To make an input field compulsory add <code> required="required" </code> to the shortcode <br /><br />
                        It should look like this <code> [text name="Full Name" required="required" ]</code><br /><br />

                        <b style="color:red;">Warning:</b> Using the file input field may cause data overload on your server.
                        Be sure you have enough server space before using it. You also have the ability to set file upload limits.

                    </div>

                <?php
                        }
                        function kkd_pff_paystack_editor_shortcode_details($post)
                        {
                            ?>
                    <p class="description">
                        <label for="wpcf7-shortcode">Copy this shortcode and paste it into your post, page, or text widget content:</label>
                        <span class="shortcode wp-ui-highlight">
                            <input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pff-paystack id=&quot;<?php echo $post->ID; ?>&quot;]"></span>
                    </p>

                <?php
                        }

                        add_action('add_meta_boxes', 'kkd_pff_paystack_editor_add_extra_metaboxes');
                        function kkd_pff_paystack_editor_add_extra_metaboxes()
                        {
                            if ($_GET['action'] == 'edit') {
                                add_meta_box('kkd_pff_paystack_editor_help_shortcode', 'Paste shortcode on preferred page', 'kkd_pff_paystack_editor_shortcode_details', 'paystack_form', 'custom-metabox-holder');
                            }
                            add_meta_box('kkd_pff_paystack_editor_help_data', 'Help Section', 'kkd_pff_paystack_editor_help_metabox_details', 'paystack_form', 'custom-metabox-holder');
                            add_meta_box('kkd_pff_paystack_editor_add_form_data', 'Extra Form Description', 'kkd_pff_paystack_editor_add_form_data', 'paystack_form', 'normal', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_recur_data', 'Recurring Payment', 'kkd_pff_paystack_editor_add_recur_data', 'paystack_form', 'side', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_email_data', 'Email Receipt Settings', 'kkd_pff_paystack_editor_add_email_data', 'paystack_form', 'normal', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_quantity_data', 'Quantity Payment', 'kkd_pff_paystack_editor_add_quantity_data', 'paystack_form', 'side', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_agreement_data', 'Agreement checkbox', 'kkd_pff_paystack_editor_add_agreement_data', 'paystack_form', 'side', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_subaccount_data', 'Sub Account', 'kkd_pff_paystack_editor_add_subaccount_data', 'paystack_form', 'side', 'default');
                            add_meta_box('kkd_pff_paystack_editor_add_startdateplan_data', '*Special: Subscribe to plan after time', 'kkd_pff_paystack_editor_add_startdateplan_data', 'paystack_form', 'side', 'default');
                        }


                        function kkd_pff_paystack_editor_add_form_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $amount = get_post_meta($post->ID, '_amount', true);
                            $paybtn = get_post_meta($post->ID, '_paybtn', true);
                            $successmsg = get_post_meta($post->ID, '_successmsg', true);
                            $txncharge = get_post_meta($post->ID, '_txncharge', true);
                            $loggedin = get_post_meta($post->ID, '_loggedin', true);
                            $currency = get_post_meta($post->ID, '_currency', true);
                            $filelimit = get_post_meta($post->ID, '_filelimit', true);
                            $redirect = get_post_meta($post->ID, '_redirect', true);
                            $minimum = get_post_meta($post->ID, '_minimum', true);
                            $usevariableamount = get_post_meta($post->ID, '_usevariableamount', true);
                            $variableamount = get_post_meta($post->ID, '_variableamount', true);
                            $hidetitle = get_post_meta($post->ID, '_hidetitle', true);

                            if ($amount == "") {
                                $amount = 0;
                            }
                            if ($filelimit == "") {
                                $filelimit = 2;
                            }
                            if ($paybtn == "") {
                                $paybtn = 'Pay';
                            }
                            if ($successmsg == "") {
                                $successmsg = 'Thank you for paying!';
                            }
                            if ($currency == "") {
                                $currency = 'NGN';
                            }
                            if ($txncharge == "") {
                                $txncharge = 'merchant';
                            }
                            if ($minimum == "") {
                                $minimum = 0;
                            }
                            if ($usevariableamount == "") {
                                $usevariableamount = 0;
                            }
                            if ($hidetitle == "") {
                                $hidetitle = 0;
                            }
                            if ($variableamount == "") {
                                $variableamount = '';
                            }
                            // Echo out the field


                            if ($hidetitle == 1) {
                                echo '<label><input name="_hidetitle" type="checkbox" value="1" checked> Hide the form title </label>';
                            } else {
                                echo '<label><input name="_hidetitle" type="checkbox" value="1" > Hide the form title </label>';
                            }
                            echo "<br>";
                            echo '<p>Currency:</p>';
                            echo '<select class="form-control" name="_currency" style="width:100%;">
						<option value="NGN" ' . kkd_pff_paystack_txncheck('NGN', $currency) . '>Nigerian Naira</option>
						<option value="GHS" ' . kkd_pff_paystack_txncheck('GHS', $currency) . '>Ghanaian Cedis</option>
						<option value="USD" ' . kkd_pff_paystack_txncheck('USD', $currency) . '>US Dollars</option>
				  </select>';
                            echo '<small>Ensure you are activated for the currency you are selecting. Check <a href="https://paystack.helpscoutdocs.com/article/38-can-paystack-process-payments-in-usd-international-currencies" target="_blank">here</a> for more information.</small>';
                            echo '<p>Amount to be paid(Set 0 for customer input):</p>';
                            echo '<input type="number" name="_amount" value="' . $amount  . '" class="widefat pf-number" />';
                            if ($minimum == 1) {
                                echo '<br><label><input name="_minimum" type="checkbox" value="1" checked> Make amount minimum payable </label>';
                            } else {
                                echo '<br><label><input name="_minimum" type="checkbox" value="1"> Make amount minimum payable </label>';
                            }
                            echo '<p>Variable Dropdown Amount:<code><label>Format(option:amount):  Option 1:10000,Option 2:3000 Separate options with "," </code></label></p>';
                            echo '<input type="text" name="_variableamount" value="' . $variableamount  . '" class="widefat " />';
                            if ($usevariableamount == 1) {
                                echo '<br><label><input name="_usevariableamount" type="checkbox" value="1" checked> Use dropdown amount option </label>';
                            } else {
                                echo '<br><label><input name="_usevariableamount" type="checkbox" value="1" > Use dropdown amount option </label>';
                            }
                            echo '<p>Pay button Description:</p>';
                            echo '<input type="text" name="_paybtn" value="' . $paybtn  . '" class="widefat" />';
                            echo '<p>Add Extra Charge:</p>';
                            echo '<select class="form-control" name="_txncharge" id="parent_id" style="width:100%;">
								<option value="merchant"' . kkd_pff_paystack_txncheck('merchant', $txncharge) . '>No, do not add</option>
								<option value="customer" ' . kkd_pff_paystack_txncheck('customer', $txncharge) . '>Yes, add it</option>
							</select>
                        <br><small>This allows you include an extra charge to cushion the effect of the transaction fee. <a href="';
                            echo get_admin_url() . "edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php#paystack_setting_fees";
                            echo '"><em>Configure</em></a></small>';
                            echo '<p>User logged In:</p>';
                            echo '<select class="form-control" name="_loggedin" id="parent_id" style="width:100%;">
								<option value="no" ' . kkd_pff_paystack_txncheck('no', $loggedin) . '>User must not be logged in</option>
								<option value="yes"' . kkd_pff_paystack_txncheck('yes', $loggedin) . '>User must be logged In</option>
							</select>';
                            echo '<p>Success Message after Payment</p>';
                            echo '<textarea rows="3"  name="_successmsg"  class="widefat" >' . $successmsg . '</textarea>';
                            echo '<p>File Upload Limit(MB):</p>';
                            echo '<input ttype="number" name="_filelimit" value="' . $filelimit  . '" class="widefat  pf-number" />';
                            echo '<p>Redirect to page link after payment(keep blank to use normal success message):</p>';
                            echo '<input ttype="text" name="_redirect" value="' . $redirect  . '" class="widefat" />';
                        }
                        function kkd_pff_paystack_editor_add_email_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $subject = get_post_meta($post->ID, '_subject', true);
                            $merchant = get_post_meta($post->ID, '_merchant', true);
                            $heading = get_post_meta($post->ID, '_heading', true);
                            $message = get_post_meta($post->ID, '_message', true);
                            $sendreceipt = get_post_meta($post->ID, '_sendreceipt', true);
                            $sendinvoice = get_post_meta($post->ID, '_sendinvoice', true);

                            if ($subject == "") {
                                $subject = 'Thank you for your payment';
                            }
                            if ($sendreceipt == "") {
                                $sendreceipt = 'yes';
                            }
                            if ($sendinvoice == "") {
                                $sendinvoice = 'yes';
                            }
                            if ($heading == "") {
                                $heading = "We've received your payment";
                            }
                            if ($message == "") {
                                $message = 'Your payment was received and we appreciate it.';
                            }
                            // Echo out the field
                            echo '<p>Send an invoices when a payment is attempted:</p>';
                            echo '<select class="form-control" name="_sendinvoice" id="parent_id" style="width:100%;">
			       <option value="no" ' . kkd_pff_paystack_txncheck('no', $sendinvoice) . '>Don\'t send</option>
			       <option value="yes" ' . kkd_pff_paystack_txncheck('yes', $sendinvoice) . '>Send</option>
			   </select>';
                            echo '<p>Send Email Receipt:</p>';
                            echo '<select class="form-control" name="_sendreceipt" id="parent_id" style="width:100%;">
							<option value="no" ' . kkd_pff_paystack_txncheck('no', $sendreceipt) . '>Don\'t send</option>
							<option value="yes" ' . kkd_pff_paystack_txncheck('yes', $sendreceipt) . '>Send</option>
						</select>';
                            echo '<p>Email Subject:</p>';
                            echo '<input type="text" name="_subject" value="' . $subject  . '" class="widefat" />';
                            echo '<p>Merchant Name on Receipt:</p>';
                            echo '<input type="text" name="_merchant" value="' . $merchant  . '" class="widefat" />';
                            echo '<p>Email Heading:</p>';
                            echo '<input type="text" name="_heading" value="' . $heading  . '" class="widefat" />';
                            echo '<p>Email Body/Message:</p>';
                            echo '<textarea rows="6"  name="_message"  class="widefat" >' . $message . '</textarea>';
                        }
                        function kkd_pff_paystack_editor_add_recur_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $recur = get_post_meta($post->ID, '_recur', true);
                            $recurplan = get_post_meta($post->ID, '_recurplan', true);

                            if ($recur == "") {
                                $recur = 'no';
                            }
                            if ($recurplan == "") {
                                $recurplan = '';
                            }
                            // Echo out the field
                            echo '<p>Recurring Payment:</p>';
                            echo '<select class="form-control" name="_recur" style="width:100%;">
							<option value="no" ' . kkd_pff_paystack_txncheck('no', $recur) . '>None</option>
							<option value="optional" ' . kkd_pff_paystack_txncheck('optional', $recur) . '>Optional Recurring</option>
							<option value="plan" ' . kkd_pff_paystack_txncheck('plan', $recur) . '>Paystack Plan</option>
						</select>';
                            echo '<p>Paystack Recur Plan code:</p>';
                            echo '<input type="text" name="_recurplan" value="' . $recurplan  . '" class="widefat" />
				<small>Plan amount must match amount on extra form description.</small>';
                        }
                        function kkd_pff_paystack_reset_stock()
                        { }
                        function kkd_pff_paystack_editor_add_quantity_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $usequantity = get_post_meta($post->ID, '_usequantity', true);
                            $useinventory = get_post_meta($post->ID, '_useinventory', true);
                            $inventory = get_post_meta($post->ID, '_inventory', true);
                            $sold = get_post_meta($post->ID, '_sold', true);
                            $quantity = get_post_meta($post->ID, '_quantity', true);
                            $quantityunit = get_post_meta($post->ID, '_quantityunit', true);
                            $recur = get_post_meta($post->ID, '_recur', true);

                            if ($usequantity == "") {
                                $usequantity = 'no';
                            }
                            if ($useinventory == "") {
                                $useinventory = "no";
                            }
                            if ($quantity == "") {
                                $quantity = '10';
                            }
                            if ($inventory == "") {
                                if ($sold !== "") {
                                    $inventory = $sold;
                                } else {
                                    $inventory = '1';
                                }
                            }
                            if ($sold == "") {
                                $sold = '0';
                            }
                            $stock = $inventory - $sold;
                            if ($quantityunit == "") {
                                $quantityunit = 'Quantity';
                            }

                            // Echo out the field
                            echo '<small>Allow your users pay in multiple quantity</small><p>Quantified Payment:</p>';
                            if ($recur != "no") {
                                echo '<select disabled class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . kkd_pff_paystack_txncheck('no', $usequantity) . '>No</option>
			    </select>';
                            } else {
                                echo '<select class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . kkd_pff_paystack_txncheck('no', $usequantity) . '>No</option>
				<option value="yes" ' . kkd_pff_paystack_txncheck('yes', $usequantity) . '>Yes</option>
			    </select>';
                            }
                            if ($usequantity == "yes") {

                                echo '<p>Max payable quantity:</p>';
                                echo '<input type="number" min="1"  name="_quantity" value="' . $quantity  . '" class="widefat  pf-number" />
                <small>Your users only get to pay in quantities if the from amount is not set to zero and recur is set to none.</small>';
                                echo '<p>Unit of quantity:</p>';
                                echo '<input type="text" name="_quantityunit" value="' . $quantityunit . '" class="widefat" />
                <small>What is the unit of this quantity? Default is <code>Quantity</code>.</small>';


                                echo '<p>Inventory Payment:</p>';
                                echo '
                <select class="form-control" name="_useinventory" style="width:100%;">
				<option value="no" ' . kkd_pff_paystack_txncheck('no', $useinventory) . '>No</option>
				<option value="yes" ' . kkd_pff_paystack_txncheck('yes', $useinventory) . '>Yes</option>
                </select>
                <small>Set maximum available items in stock</small>
                ';
                            }
                            if ($useinventory == "yes" && $usequantity  == "yes") {
                                echo '<p>Total Inventory</p>';
                                echo '<input type="number" min="' . $sold . '" name="_inventory" value="' . $inventory  . '" class="widefat  pf-number" />';
                                echo '<p>Already sold</p>';
                                echo '<input type="number" name="_sold" value="' . $sold  . '" class="widefat  pf-number" />
                <small></small>
				<br/>';
                            }
                        }

                        function kkd_pff_paystack_editor_add_agreement_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $useagreement = get_post_meta($post->ID, '_useagreement', true);
                            $agreementlink = get_post_meta($post->ID, '_agreementlink', true);

                            if ($useagreement == "") {
                                $useagreement = 'no';
                            }
                            if ($agreementlink  == "") {
                                $agreementlink = '';
                            }
                            // Echo out the field
                            echo '<p>Use agreement checkbox:</p>';
                            echo '<select class="form-control" name="_useagreement" style="width:100%;">
					<option value="no" ' . kkd_pff_paystack_txncheck('no', $useagreement) . '>No</option>
					<option value="yes" ' . kkd_pff_paystack_txncheck('yes', $useagreement) . '>Yes</option>
			</select>';
                            echo '<p>Agreement Page Link:</p>';
                            echo '<input type="text" name="_agreementlink" value="' . $agreementlink  . '" class="widefat" />';
                        }
                        function kkd_pff_paystack_editor_add_subaccount_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $subaccount = get_post_meta($post->ID, '_subaccount', true);
                            $txnbearer = get_post_meta($post->ID, '_txnbearer', true);
                            $merchantamount = get_post_meta($post->ID, '_merchantamount', true);


                            if ($subaccount  == "") {
                                $subaccount = '';
                            }
                            if ($merchantamount  == "") {
                                $merchantamount = '';
                            }
                            echo '<p>Sub Account code:</p>';
                            echo '<input type="text" name="_subaccount" value="' . $subaccount  . '" class="widefat" />';
                            echo '<p>Transaction Charge bearer:</p>';
                            echo '<select class="form-control" name="_txnbearer" id="parent_id" style="width:100%;">
				<option value="account" ' . kkd_pff_paystack_txncheck('account', $txnbearer) . '>Merchant (default)</option>
				<option value="subaccount" ' . kkd_pff_paystack_txncheck('subaccount', $txnbearer) . '>Sub Account</option>
			</select>';
                            echo '<p>Merchant Amount:</p>';
                            echo '<input type="text" name="_merchantamount" value="' . $merchantamount . '" class="widefat" />';
                        }
                        function kkd_pff_paystack_editor_add_startdateplan_data()
                        {
                            global $post;

                            // Noncename needed to verify where the data originated
                            echo '<p>User subscribes to plan after number of days:</p>';
                            echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
                                wp_create_nonce(plugin_basename(__FILE__)) . '" />';

                            // Get the location data if its already been entered
                            $days = get_post_meta($post->ID, '_startdate_days', true);
                            $plan = get_post_meta($post->ID, '_startdate_plan_code', true);
                            $enabled = get_post_meta($post->ID, '_startdate_enabled', true);


                            if ($days  == "") {
                                $days = '';
                            }
                            if ($plan  == "") {
                                $plan = '';
                            }
                            if ($enabled  == "") {
                                $enabled = 0;
                            }
                            echo '<p>Number of days:</p>';
                            echo '<input type="number" name="_startdate_days" value="' . $days  . '" class="widefat  pf-number" />';
                            echo '<p>Plan:</p>';
                            echo '<input type="text" name="_startdate_plan_code" value="' . $plan . '" class="widefat" />';
                            if ($enabled == 1) {
                                echo '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1" checked> Enable </label></p>';
                            } else {
                                echo '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1"> Enable </label></p>';
                            }
                        }

                        function kkd_pff_paystack_save_data($post_id, $post)
                        {
                            if (!wp_verify_nonce(@$_POST['eventmeta_noncename'], plugin_basename(__FILE__))) {
                                return $post->ID;
                            }

                            // Is the user allowed to edit the post or page?
                            if (!current_user_can('edit_post', $post->ID)) {
                                return $post->ID;
                            }
                            $form_meta['_inventory'] = $_POST['_inventory'];
                            $form_meta['_useinventory'] = $_POST['_useinventory'];
                            $form_meta['_amount'] = $_POST['_amount'];
                            $form_meta['_hidetitle'] = $_POST['_hidetitle'];
                            $form_meta['_minimum'] = $_POST['_minimum'];

                            $form_meta['_variableamount'] = $_POST['_variableamount'];
                            $form_meta['_usevariableamount'] = $_POST['_usevariableamount'];

                            $form_meta['_paybtn'] = $_POST['_paybtn'];
                            $form_meta['_currency'] = $_POST['_currency'];
                            $form_meta['_successmsg'] = $_POST['_successmsg'];
                            $form_meta['_txncharge'] = $_POST['_txncharge'];
                            $form_meta['_loggedin'] = $_POST['_loggedin'];
                            $form_meta['_filelimit'] = $_POST['_filelimit'];
                            $form_meta['_redirect'] = $_POST['_redirect'];
                            ///
                            $form_meta['_subject'] = $_POST['_subject'];
                            $form_meta['_merchant'] = $_POST['_merchant'];
                            $form_meta['_heading'] = $_POST['_heading'];
                            $form_meta['_message'] = $_POST['_message'];
                            $form_meta['_sendreceipt'] = $_POST['_sendreceipt'];
                            $form_meta['_sendinvoice'] = $_POST['_sendinvoice'];
                            ///
                            $form_meta['_recur'] = $_POST['_recur'];
                            $form_meta['_recurplan'] = $_POST['_recurplan'];
                            $form_meta['_usequantity'] = $_POST['_usequantity'];
                            $form_meta['_quantity'] = $_POST['_quantity'];
                            $form_meta['_sold'] = $_POST['_sold'];
                            $form_meta['_quantityunit'] = $_POST['_quantityunit'];

                            $form_meta['_useagreement'] = $_POST['_useagreement'];
                            $form_meta['_agreementlink'] = $_POST['_agreementlink'];
                            $form_meta['_subaccount'] = $_POST['_subaccount'];
                            $form_meta['_txnbearer'] = $_POST['_txnbearer'];
                            $form_meta['_merchantamount'] = $_POST['_merchantamount'];
                            // Add values of $form_meta as custom fields

                            //Custom Plan with Start Date
                            $form_meta['_startdate_days'] = $_POST['_startdate_days'];
                            $form_meta['_startdate_plan_code'] = $_POST['_startdate_plan_code'];
                            $form_meta['_startdate_enabled'] = $_POST['_startdate_enabled'];

                            foreach ($form_meta as $key => $value) { // Cycle through the $form_meta array!
                                if ($post->post_type == 'revision') {
                                    return; // Don't store custom data twice
                                }
                                $value = implode(',', (array) $value); // If $value is an array, make it a CSV (unlikely)
                                if (get_post_meta($post->ID, $key, false)) { // If the custom field already has a value
                                    update_post_meta($post->ID, $key, $value);
                                } else { // If the custom field doesn't have a value
                                    add_post_meta($post->ID, $key, $value);
                                }
                                if (!$value) {
                                    delete_post_meta($post->ID, $key); // Delete if blank
                                }
                            }
                        }
                        add_action('save_post', 'kkd_pff_paystack_save_data', 1, 2);
                    }

                    public function enqueue_styles($hook)
                    {
                        if ($hook != 'toplevel_page_submissions' && $hook != 'paystack_form_page_class-paystack-forms-admin') {
                            return;
                        }
                        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/paystack-forms-admin.css', array(), $this->version, 'all');
                    }
                    public function enqueue_scripts()
                    {
                        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/paystack-forms-admin.js', array('jquery'), $this->version, false);
                    }

                    /**
                     * Add settings action link to the plugins page.
                     *
                     * @since    1.0.0
                     */
                    public function add_action_links($links)
                    {
                        $settings_link = array(
                            '<a href="' . admin_url('edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php') . '">' . __('Settings', $this->plugin_name) . '</a>',
                        );
                        return array_merge($settings_link, $links);
                    }
                }

                add_action('admin_menu', 'kkd_pff_paystack_register_newpage');
                function kkd_pff_paystack_register_newpage()
                {
                    add_menu_page('paystack', 'paystack', 'administrator', 'submissions', 'kkd_pff_paystack_payment_submissions');
                    remove_menu_page('submissions');
                }

                function kkd_pff_paystack_payment_submissions()
                {
                    $id = $_GET['form'];
                    $obj = get_post($id);
                    if ($obj->post_type == 'paystack_form') {
                        $amount = get_post_meta($id, '_amount', true);
                        $thankyou = get_post_meta($id, '_successmsg', true);
                        $paybtn = get_post_meta($id, '_paybtn', true);
                        $loggedin = get_post_meta($id, '_loggedin', true);
                        $txncharge = get_post_meta($id, '_txncharge', true);

                        $exampleListTable = new Kkd_Pff_Paystack_Payments_List_Table();
                        $data = $exampleListTable->prepare_items(); ?>
                <div id="welcome-panel" class="welcome-panel">
                    <div class="welcome-panel-content">
                        <h1 style="margin: 0px;"><?php echo $obj->post_title; ?> Payments </h1>
                        <p class="about-description">All payments made for this form</p>
                        <?php if ($data > 0) {
                                    ?>

                            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                                <input type="hidden" name="action" value="kkd_pff_export_excel">
                                <input type="hidden" name="form_id" value="<?php echo $id; ?>">
                                <button type="submit" class="button button-primary button-hero load-customize">Export Data to Excel</button>
                            </form>
                        <?php
                                } ?>

                        <br><br>
                    </div>
                </div>
                <div class="wrap">
                    <div id="icon-users" class="icon32"></div>
                    <?php $exampleListTable->display(); ?>
                </div>
            <?php
                }
            }
            add_action('admin_post_kkd_pff_export_excel', 'Kkd_pff_export_excel');

            function Kkd_pff_prep_csv_data($item)
            {
                return '"' . str_replace('"', '""', $item) . '"';
            }

            function Kkd_pff_export_excel()
            {
                global $wpdb;

                $post_id = $_POST['form_id'];
                $obj = get_post($post_id);
                $csv_output = "";
                $currency = get_post_meta($post_id, '_currency', true);
                if ($currency == "") {
                    $currency = 'NGN';
                }
                $table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
                $data = array();
                $alldbdata = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '" . $post_id . "' AND paid = '1')  ORDER BY `id` ASC");
                $i = 0;

                if (count($alldbdata) > 0) {
                    $header = $alldbdata[0];
                    $csv_output .= "#,";
                    $csv_output .= "Email,";
                    $csv_output .= "Amount,";
                    $csv_output .= "Date Paid,";
                    $csv_output .= "Reference,";
                    $new = json_decode($header->metadata);
                    $text = '';
                    if (array_key_exists("0", $new)) {
                        foreach ($new as $key => $item) {
                            $csv_output .= Kkd_pff_prep_csv_data($item->display_name) . ",";
                        }
                    } else {
                        if (count($new) > 0) {
                            foreach ($new as $key => $item) {
                                $csv_output .= Kkd_pff_prep_csv_data($key) . ",";
                            }
                        }
                    }
                    $csv_output .= "\n";

                    foreach ($alldbdata as $key => $dbdata) {
                        $newkey = $key + 1;
                        if ($dbdata->txn_code_2 != "") {
                            $txn_code = $dbdata->txn_code_2;
                        } else {
                            $txn_code = $dbdata->txn_code;
                        }
                        $csv_output .= Kkd_pff_prep_csv_data($newkey) . ",";
                        $csv_output .= Kkd_pff_prep_csv_data($dbdata->email) . ",";
                        $csv_output .= Kkd_pff_prep_csv_data($currency . ' ' . $dbdata->amount) . ",";
                        $csv_output .= Kkd_pff_prep_csv_data(substr($dbdata->paid_at, 0, 10)) . ",";
                        $csv_output .= Kkd_pff_prep_csv_data($txn_code) . ",";
                        $new = json_decode($dbdata->metadata);
                        $text = '';
                        if (array_key_exists("0", $new)) {
                            foreach ($new as $key => $item) {
                                $csv_output .= Kkd_pff_prep_csv_data($item->value) . ",";
                            }
                        } else {
                            if (count($new) > 0) {
                                foreach ($new as $key => $item) {
                                    $csv_output .= Kkd_pff_prep_csv_data($item) . ",";
                                }
                            }
                        }
                        $csv_output .= "\n";
                    }


                    $filename = $obj->post_title . "_payments_" . date("Y-m-d_H-i", time());
                    header("Content-type: application/vnd.ms-excel");
                    header("Content-disposition: csv" . date("Y-m-d") . ".csv");
                    header("Content-disposition: filename=" . $filename . ".csv");
                    print $csv_output;
                    exit;
                }


                // Handle request then generate response using echo or leaving PHP and using HTML
            }

            class Kkd_Pff_Paystack_Wp_List_Table
            {
                public function __construct()
                {
                    add_action('admin_menu', array($this, 'add_menu_example_list_table_page'));
                }
                public function add_menu_example_list_table_page()
                {
                    add_menu_page('', '', 'manage_options', 'example-list-table.php', array($this, 'list_table_page'));
                }
                public function list_table_page()
                {
                    $exampleListTable = new Example_List_Table();
                    $exampleListTable->prepare_items($data); ?>
                <div class="wrap">
                    <div id="icon-users" class="icon32"></div>
                    <?php $exampleListTable->display(); ?>
                </div>
            <?php
                }
            }


            if (!class_exists('WP_List_Table')) {
                include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
            }
            function format_data($data)
            {
                $new = json_decode($data);
                $text = '';
                if (array_key_exists("0", $new)) {
                    foreach ($new as $key => $item) {
                        if ($item->type == 'text') {
                            $text .= '<b>' . $item->display_name . ": </b> " . $item->value . "<br />";
                        } else {
                            $text .= '<b>' . $item->display_name . ": </b>  <a target='_blank' href='" . $item->value . "'>link</a><br />";
                        }
                    }
                } else {
                    $text = '';
                    if (count($new) > 0) {
                        foreach ($new as $key => $item) {
                            $text .= '<b>' . $key . ": </b> " . $item . "<br />";
                        }
                    }
                }
                //
                return $text;
            }

            class Kkd_Pff_Paystack_Payments_List_Table extends WP_List_Table
            {
                public function prepare_items()
                {
                    $post_id = $_GET['form'];
                    $currency = get_post_meta($post_id, '_currency', true);

                    global $wpdb;

                    $table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
                    $data = array();
                    $alldbdata = $wpdb->get_results("SELECT * FROM $table WHERE (post_id = '" . $post_id . "' AND paid = '1')");

                    foreach ($alldbdata as $key => $dbdata) {
                        $newkey = $key + 1;
                        if ($dbdata->txn_code_2 != "") {
                            $txn_code = $dbdata->txn_code_2;
                        } else {
                            $txn_code = $dbdata->txn_code;
                        }
                        $data[] = array(
                            'id'  => $newkey,
                            'email' => '<a href="mailto:' . $dbdata->email . '">' . $dbdata->email . '</a>',
                            'amount' => $currency . '<b>' . number_format($dbdata->amount) . '</b>',
                            'txn_code' => $txn_code,
                            'metadata' => format_data($dbdata->metadata),
                            'date'  => $dbdata->created_at
                        );
                    }

                    $columns = $this->get_columns();
                    $hidden = $this->get_hidden_columns();
                    $sortable = $this->get_sortable_columns();
                    usort($data, array(&$this, 'sort_data'));
                    $perPage = 20;
                    $currentPage = $this->get_pagenum();
                    $totalItems = count($data);
                    $this->set_pagination_args(
                        array(
                            'total_items' => $totalItems,
                            'per_page'    => $perPage
                        )
                    );
                    $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
                    $this->_column_headers = array($columns, $hidden, $sortable);
                    $this->items = $data;

                    $rows = count($alldbdata);
                    return $rows;
                }

                public function get_columns()
                {
                    $columns = array(
                        'id'  => '#',
                        'email' => 'Email',
                        'amount' => 'Amount',
                        'txn_code' => 'Txn Code',
                        'metadata' => 'Data',
                        'date'  => 'Date'
                    );
                    return $columns;
                }
                /**
                 * Define which columns are hidden
                 *
                 * @return Array
                 */
                public function get_hidden_columns()
                {
                    return array();
                }
                public function get_sortable_columns()
                {
                    return array('email' => array('email', false), 'date' => array('date', false), 'amount' => array('amount', false));
                }
                /**
                 * Get the table data
                 *
                 * @return Array
                 */
                private function table_data($data)
                {
                    return $data;
                }
                /**
                 * Define what data to show on each column of the table
                 *
                 * @param Array  $item        Data
                 * @param String $column_name - Current column name
                 *
                 * @return Mixed
                 */
                public function column_default($item, $column_name)
                {
                    switch ($column_name) {
                        case 'id':
                        case 'email':
                        case 'amount':
                        case 'txn_code':
                        case 'metadata':
                        case 'date':
                            return $item[$column_name];
                        default:
                            return print_r($item, true);
                    }
                }

                /**
                 * Allows you to sort the data by the variables set in the $_GET
                 *
                 * @return Mixed
                 */
                private function sort_data($a, $b)
                {
                    $orderby = 'date';
                    $order = 'desc';
                    if (!empty($_GET['orderby'])) {
                        $orderby = $_GET['orderby'];
                    }
                    if (!empty($_GET['order'])) {
                        $order = $_GET['order'];
                    }
                    $result = strcmp($a[$orderby], $b[$orderby]);
                    if ($order === 'asc') {
                        return $result;
                    }
                    return -$result;
                }
            }