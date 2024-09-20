<?php
/**
 * The main plugin class, this will return the and instance of the class.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin class.
 */
final class Payment_Forms {

	/**
	 * Holds class isntance
	 *
	 * @var object \paystack\payment_forms\Payment_Forms
	 */
	protected static $instance = null;

	/**
	 * The package namespace for the plugin.
	 *
	 * @var string
	 */
	public $namespace = '\paystack\payment_forms\\';

	/**
	 * The plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = KKD_PFF_PLUGIN_NAME;

	/**
	 * The plugin version number.
	 *
	 * @var string
	 */
	public $version = KKD_PFF_PAYSTACK_VERSION;

	/**
	 * Holdes the array of classes key => object.
	 *
	 * @var array
	 */
	private $classes = array();

	/**
	 * Helpers functions for the custom payments.
	 *
	 * @var \paystack\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * Initialize the plugin by setting localization, filters, and
	 * administration functions.
	 *
	 * @access private
	 */
	private function __construct() {
		$this->set_variables();
		$this->include_classes();
		$this->init_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object \paystack\payment_forms\Payment_Forms
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets our plugin variables.
	 *
	 * @return void
	 */
	private function set_variables() {
		$this->classes = array(
			'activation'          => '',
			'setup'               => 'Setup',
			'helpers'             => '',
			'settings'            => 'Settings',
			'forms-list'          => 'Forms_List',
			'submissions'         => 'Submissions',
			'forms-update'        => 'Forms_Update',
			'tinymce-plugin'      => 'TinyMCE_Plugin',
			'shortcodes'          => 'Shortcodes',
		);
	}

	/**
	 * Includes our class files
	 *
	 * @return void
	 */
	private function include_classes() {
		foreach ( $this->classes as $key => $name ) {
			include_once KKD_PFF_PAYSTACK_PLUGIN_PATH . '/includes/class-' . $key . '.php';
			if ( '' !== $name ) {
				$this->classes[ $key ] = new ( $this->namespace . $name );
			}
		}
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		register_activation_hook( KKD_PFF_PAYSTACK_MAIN_FILE, array( '\paystack\payment_forms\activation', 'install' ) );
	}
}