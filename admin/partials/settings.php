<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Paystack Forms Settings', 'payment-forms-for-paystack' ); ?></h1>
	<h2><?php esc_html_e( 'API Keys Settings', 'payment-forms-for-paystack' ); ?></h2>

	<p>
		<?php
		echo wp_kses(
			__( 'Get your API Keys <a href="https://dashboard.paystack.co/#/settings/developer" target="_blank">here</a>.', 'payment-forms-for-paystack' ),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		)
		?>
	</p>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'kkd-pff-paystack-settings-group' );
		do_settings_sections( 'kkd-pff-paystack-settings-group' );
		?>
		<table class="form-table paystack_setting_page">
			<tr>
				<th scope="row">
					<label for="paystack_settings_mode"><?php esc_html_e( 'Mode', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<select class="form-control" name="mode" id="paystack_settings_mode">
						<option value="test" <?php selected( 'test', paystack_forms_get_option( 'mode' ) ); ?>>Test Mode</option>
						<option value="live" <?php selected( 'live', paystack_forms_get_option( 'mode' ) ); ?>>Live Mode</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_test_secret_key"><?php esc_html_e( 'Test Secret Key', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="password" name="tsk" id="paystack_settings_test_secret_key" value="<?php echo esc_attr( paystack_forms_get_option( 'tsk' ) ); ?>"/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_test_public_key"><?php esc_html_e( 'Test Public Key', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="password" name="tpk" id="paystack_settings_test_public_key" value="<?php echo esc_attr( paystack_forms_get_option( 'tpk' ) ); ?>"/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_live_secret_key"><?php esc_html_e( 'Live Secret Key', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="password" name="lsk" id="paystack_settings_live_secret_key" value="<?php echo esc_attr( paystack_forms_get_option( 'lsk' ) ); ?>"/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_live_public_key"><?php esc_html_e( 'Live Public Key', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="password" name="lpk" id="paystack_settings_live_public_key" value="<?php echo esc_attr( paystack_forms_get_option( 'lpk' ) ); ?>"/>
				</td>
			</tr>
		</table>

		<hr>

		<table class="form-table paystack_setting_page" id="paystack_setting_fees">
			<h2><?php esc_html_e( 'Fees Settings', 'payment-forms-for-paystack' ); ?></h2>

			<tr>
				<th scope="row">
					<label for="paystack_settings_percentage"><?php esc_html_e( 'Percentage', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="text" name="prc" id="paystack_settings_percentage" value="<?php echo esc_attr( paystack_forms_get_option( 'prc', 1.5 ) ); ?>"/>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_threshold"><?php esc_html_e( 'Threshold', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="text" name="ths" id="paystack_settings_threshold" value="<?php echo esc_attr( paystack_forms_get_option( 'ths', 2500 ) ); ?>"/>
					<p class="description"><?php esc_html_e( 'Amount above which Paystack adds the fixed amount below', 'payment-forms-for-paystack' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_additional_charge"><?php esc_html_e( 'Additional Charge', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="text" name="adc" id="paystack_settings_additional_charge" value="<?php echo esc_attr( paystack_forms_get_option( 'adc', 100 ) ); ?>"/>
					<p class="description"><?php esc_html_e( 'Amount added to percentage fee when transaction amount is above threshold', 'payment-forms-for-paystack' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="paystack_settings_cap"><?php esc_html_e( 'Cap', 'payment-forms-for-paystack' ); ?></label>
				</th>
				<td>
					<input type="text" name="cap" id="paystack_settings_cap" value="<?php echo esc_attr( paystack_forms_get_option( 'cap', 2000 ) ); ?>"/>
					<p class="description"><?php esc_html_e( 'Maximum charge Paystack can charge on your transactions', 'payment-forms-for-paystack' ); ?></p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>

	</form>
</div>
