<?php

global $post;

$usequantity  = get_post_meta( $post->ID, '_usequantity', true );
$useinventory = get_post_meta( $post->ID, '_useinventory', true );
$inventory    = get_post_meta( $post->ID, '_inventory', true );
$sold         = get_post_meta( $post->ID, '_sold', true );
$quantity     = get_post_meta( $post->ID, '_quantity', true );
$quantityunit = get_post_meta( $post->ID, '_quantityunit', true );
$recur        = get_post_meta( $post->ID, '_recur', true );

if ( empty( $usequantity ) ) {
	$usequantity = 'no';
}
if ( empty( $useinventory ) ) {
	$useinventory = 'no';
}
if ( empty( $quantity ) ) {
	$quantity = '1';
}
if ( empty( $inventory ) ) {
	if ( '' !== $sold ) {
		$inventory = $sold;
	} else {
		$inventory = '1';
	}
}
if ( empty( $sold ) ) {
	$sold = '0';
}
$stock = $inventory - $sold;
if ( empty( $quantityunit ) ) {
	$quantityunit = __( 'Quantity', 'payment-forms-for-paystack' );
}
?>

<div>
	<p>
		<label for="quantity_payment_use_quantity" class="post-attributes-label"><?php esc_html_e( 'Quantified Payment', 'payment-forms-for-paystack' ); ?></label>
	</p>
		<?php if ( 'no' !== $recur ) : ?>
			<select disabled class="form-control widefat" name="_usequantity" id="quantity_payment_use_quantity">
				<option value="no" <?php selected( 'no', $usequantity ); ?>>
					<?php esc_html_e( 'No', 'payment-forms-for-paystack' ); ?>
				</option>
			</select>
		<?php else : ?>
			<select class="form-control widefat" name="_usequantity" id="quantity_payment_use_quantity">
				<option value="no" <?php selected( 'no', $usequantity ); ?>>
					<?php esc_html_e( 'No', 'payment-forms-for-paystack' ); ?>
				</option>
				<option value="yes" <?php selected( 'yes', $usequantity ); ?>>
					<?php esc_html_e( 'Yes', 'payment-forms-for-paystack' ); ?>
				</option>
			</select>
		<?php endif; ?>
	<p class="description"><small><?php esc_html_e( 'Allow your users pay in multiple quantity', 'payment-forms-for-paystack' ); ?></small>
</div>

<?php if ( 'yes' === $usequantity ) : ?>

	<div>
		<p>
			<label for="quantity_payment_max_payable_quantity" class="post-attributes-label"><?php esc_html_e( 'Max Payable Quantity', 'payment-forms-for-paystack' ); ?></label>
		</p>
		<input type="number" min="1" name="_quantity" id="quantity_payment_max_payable_quantity" value="<?php echo esc_attr( $quantity ); ?>" class="widefat"/>
		<p class="description"><small><?php esc_html_e( 'Your users only get to pay in quantities if the from amount is not set to zero and recur is set to none.', 'payment-forms-for-paystack' ); ?></small>
		</p>
	</div>

	<div>
		<p>
			<label for="quantity_payment_quantity_unit" class="post-attributes-label"><?php esc_html_e( 'Unit of Quantity', 'payment-forms-for-paystack' ); ?></label>
		</p>
		<input type="text" name="_quantityunit" id="quantity_payment_quantity_unit" value="<?php echo esc_attr( $quantityunit ); ?>" class="widefat"/>
		<p class="description">
			<small>
				<?php
						echo wp_kses(
							__( 'What is the unit of this quantity? Default is <code>Quantity</code>', 'payment-forms-for-paystack' ),
							array(
								'code' => array(),
							)
						)
				?>
			</small>
		</p>
	</div>

	<div>
		<p>
			<label for="quantity_payment_inventory_payment" class="post-attributes-label"><?php esc_html_e( 'Inventory Payment', 'payment-forms-for-paystack' ); ?></label>
		</p>
		<select class="form-control widefat" name="_useinventory" id="quantity_payment_inventory_payment">
			<option value="no" <?php selected( 'no', $useinventory ); ?>>
				<?php esc_html_e( 'No', 'payment-forms-for-paystack' ); ?>
			</option>
			<option value="yes" <?php selected( 'yes', $useinventory ); ?>>
				<?php esc_html_e( 'Yes', 'payment-forms-for-paystack' ); ?>
			</option>
		</select>
	</div>

<?php endif; ?>

<?php if ( 'yes' === $useinventory && 'yes' === $usequantity ) : ?>

	<div>
		<p>
			<label for="quantity_payment_total_inventory" class="post-attributes-label"><?php esc_html_e( 'Total Inventory', 'payment-forms-for-paystack' ); ?></label>
		</p>
		<input type="number" min="<?php echo esc_attr( $sold ); ?>" name="_inventory" id="quantity_payment_total_inventory" value="<?php echo esc_attr( $inventory ); ?>" class="widefat"/>
	</div>

	<div>
		<p>
			<label for="quantity_payment_already_sold" class="post-attributes-label"><?php esc_html_e( 'Already Sold', 'payment-forms-for-paystack' ); ?></label>
		</p>
		<input type="number" name="_sold" id="quantity_payment_already_sold" value="<?php echo esc_attr( $sold ); ?>" class="widefat"/>
	</div>

<?php endif; ?>
