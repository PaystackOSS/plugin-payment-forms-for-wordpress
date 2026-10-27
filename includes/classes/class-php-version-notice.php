<?php
/**
 * Warns administrators when the server runs a vulnerable PHP version.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PHP version notice class.
 */
class PHP_Version_Notice {

	/**
	 * First patched release per supported minor branch.
	 *
	 * CVE-2026-109618 / CVE-2026-109619; 8.1 and older are end of life with no fix.
	 *
	 * @var array
	 */
	const FIXED_PATCH = array(
		'8.2' => 35,
		'8.3' => 36,
		'8.4' => 27,
		'8.5' => 12,
	);

	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_filter( 'site_status_tests', array( $this, 'add_site_health_test' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Check whether a PHP version is affected.
	 *
	 * @param string $version PHP version string.
	 *
	 * @return bool
	 */
	public static function is_affected( $version ) {
		if ( ! preg_match( '/^(\d+)\.(\d+)(?:\.(\d+))?/', (string) $version, $matches ) ) {
			return false;
		}

		$major = (int) $matches[1];
		$minor = (int) $matches[2];
		$patch = isset( $matches[3] ) ? (int) $matches[3] : 0;

		if ( $major < 8 || ( 8 === $major && $minor < 2 ) ) {
			return true;
		}

		$key = $major . '.' . $minor;

		return isset( self::FIXED_PATCH[ $key ] ) && $patch < self::FIXED_PATCH[ $key ];
	}

	/**
	 * Get the running PHP release, without any distro suffix.
	 *
	 * @return string
	 */
	private static function current_version() {
		return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
	}

	/**
	 * Whether this is a distro build whose security fixes may be backported.
	 *
	 * @return bool
	 */
	private static function is_distro_build() {
		return '' !== PHP_EXTRA_VERSION;
	}

	/**
	 * Whether the running version is affected, ignoring distro backports.
	 *
	 * @return bool
	 */
	private function is_vulnerable() {
		return self::is_affected( self::current_version() ) && apply_filters( 'pff_paystack_php_version_warning', true );
	}

	/**
	 * Whether the admin notice should be shown.
	 *
	 * @return bool
	 */
	private function should_warn() {
		return $this->is_vulnerable() && ! self::is_distro_build();
	}

	/**
	 * Build the warning message.
	 *
	 * @return string
	 */
	private function message() {
		$fixed = array();
		foreach ( self::FIXED_PATCH as $branch => $patch ) {
			$fixed[] = $branch . '.' . $patch;
		}

		return sprintf(
			/* translators: 1: PHP version, 2: list of fixed PHP versions. */
			esc_html__( 'Payment Forms for Paystack: this server runs PHP %1$s, which is affected by critical security vulnerabilities (CVE-2026-109618, CVE-2026-109619) that allow remote code execution. Ask your hosting provider to upgrade to PHP %2$s or later. If your host backports security fixes to older PHP builds, this warning may not apply.', 'pff-paystack' ),
			esc_html( PHP_VERSION ),
			esc_html( implode( ', ', $fixed ) )
		);
	}

	/**
	 * Add the Site Health test.
	 *
	 * @param array $tests Site Health tests.
	 *
	 * @return array
	 */
	public function add_site_health_test( $tests ) {
		$tests['direct']['pff_paystack_php_version'] = array(
			'label' => esc_html__( 'Payment Forms for Paystack PHP version', 'pff-paystack' ),
			'test'  => array( $this, 'site_health_result' ),
		);

		return $tests;
	}

	/**
	 * Site Health test callback.
	 *
	 * @return array
	 */
	public function site_health_result() {
		$affected = $this->is_vulnerable();
		if ( ! $affected ) {
			$status = 'good';
		} else {
			$status = self::is_distro_build() ? 'recommended' : 'critical';
		}
		$colors = array(
			'good'        => 'blue',
			'recommended' => 'orange',
			'critical'    => 'red',
		);

		return array(
			'label'       => $affected
				? esc_html__( 'PHP version has known critical vulnerabilities', 'pff-paystack' )
				: esc_html__( 'PHP version is not affected by CVE-2026-109618 or CVE-2026-109619', 'pff-paystack' ),
			'status'      => $status,
			'badge'       => array(
				'label' => esc_html__( 'Security', 'pff-paystack' ),
				'color' => $colors[ $status ],
			),
			'description' => '<p>' . ( $affected
				? $this->message()
				: esc_html__( 'This server\'s PHP version is not affected by CVE-2026-109618 or CVE-2026-109619.', 'pff-paystack' ) ) . '</p>',
			'actions'     => sprintf(
				'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_url( 'https://www.php.net/supported-versions.php' ),
				esc_html__( 'View supported PHP versions', 'pff-paystack' )
			),
			'test'        => 'pff_paystack_php_version',
		);
	}

	/**
	 * Display the admin notice.
	 *
	 * @return void
	 */
	public function admin_notice() {
		if ( ! current_user_can( Settings::get_capability() ) || ! $this->should_warn() ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( 'plugins' !== $screen->id && 'paystack_form' !== $screen->post_type ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . $this->message() . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in message().
	}
}
