<?php


class Kkd_Pff_Paystack
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    Paystack_Forms_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct() 
    {

        $this->plugin_name = 'pff-paystack';
        $this->version = '2.0.0';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        add_action('init', array( &$this, 'setup_tinymce_plugin' ));


    }
    /**
     * Check if the current user can edit Posts or Pages, and is using the Visual Editor
     * If so, add some filters so we can register our plugin
     */
    function setup_tinymce_plugin() 
    {

        // Check if the logged in WordPress User can edit Posts or Pages
        // If not, don't register our TinyMCE plugin
        if (! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
            return;
        }

        // Check if the logged in WordPress User has the Visual Editor enabled
        // If not, don't register our TinyMCE plugin
        if (get_user_option('rich_editing') !== 'true' ) {
            return;
        }

        // Setup some filters
        add_filter('mce_external_plugins', array( &$this, 'add_tinymce_plugin' ));
        add_filter('mce_buttons', array( &$this, 'add_tinymce_toolbar_button' ));

    }

    /**
     * Adds a TinyMCE plugin compatible JS file to the TinyMCE / Visual Editor instance
     *
     * @param  array $plugin_array Array of registered TinyMCE Plugins
     * @return array Modified array of registered TinyMCE Plugins
     */
    function add_tinymce_plugin( $plugin_array ) 
    {

        $plugin_array['custom_class'] = plugin_dir_url(__FILE__) . 'tinymce-custom-class.js';
        return $plugin_array;

    }

    /**
     * Adds a button to the TinyMCE / Visual Editor which the user can click
     * to insert a custom CSS class.
     *
     * @param  array $buttons Array of registered TinyMCE Buttons
     * @return array Modified array of registered TinyMCE Buttons
     */
    function add_tinymce_toolbar_button( $buttons ) 
    {

        array_push($buttons, 'custom_class');
        return $buttons;

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Paystack_Forms_Loader. Orchestrates the hooks of the plugin.
     * - Paystack_Forms_i18n. Defines internationalization functionality.
     * - Paystack_Forms_Admin. Defines all hooks for the admin area.
     * - Paystack_Forms_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies() 
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paystack-forms-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-paystack-forms-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paystack-forms-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'public/class-paystack-forms-public.php';
        // require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/paystack-webhook.php';

        $this->loader = new Kkd_Pff_Paystack_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Paystack_Forms_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function set_locale() 
    {

        $plugin_i18n = new Kkd_Pff_Paystack_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_admin_hooks() 
    {

        $plugin_admin = new Kkd_Pff_Paystack_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add Settings link to the plugin
        $this->loader->add_filter(
            'plugin_action_links_' . KKD_PFF_PLUGIN_BASENAME,
            $plugin_admin,
            'add_action_links'
        );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_public_hooks() 
    {

        $plugin_public = new Kkd_Pff_Paystack_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run() 
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since  1.0.0
     * @return string    The name of the plugin.
     */
    public function get_plugin_name() 
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since  1.0.0
     * @return Paystack_Forms_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() 
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since  1.0.0
     * @return string    The version number of the plugin.
     */
    public function get_version() 
    {
        return $this->version;
    }

}
