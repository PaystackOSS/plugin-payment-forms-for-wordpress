<?php
/**
 * The Payment Object for the Paystack Charge
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Transaction_Fee for the Paystack Charge.
 */
class Transaction_Fee {

	public $percentage;
	public $additional_charge;
	public $crossover_total;
	public $cap;

	public $charge_divider;
	public $crossover;
	public $flatline_plus_charge;
	public $flatline;

	/**
	 * Constructor.
	 *
	 * @param float $percentage        Percentage charge. Default 0.015.
	 * @param int   $additional_charge Additional charge in kobo. Default 10000.
	 * @param int   $crossover_total   Crossover total in kobo. Default 250000.
	 * @param int   $cap               Cap in kobo. Default 200000.
	 */
	public function __construct( $percentage = 0.015, $additional_charge = 10000, $crossover_total = 250000, $cap = 200000 ) {
		$this->percentage        = $percentage;
		$this->additional_charge = $additional_charge;
		$this->crossover_total   = $crossover_total;
		$this->cap               = $cap;
		$this->__setup();
	}

	/**
	 * Setup method to initialize calculated values.
	 */
	private function __setup() {
		$this->charge_divider       = $this->__charge_divider();
		$this->crossover            = $this->__crossover();
		$this->flatline_plus_charge = $this->__flatline_plus_charge();
		$this->flatline             = $this->__flatline();
	}

	/**
	 * Calculate charge divider.
	 *
	 * @return float Charge divider.
	 */
	private function __charge_divider() {
		return floatval( 1 - $this->percentage );
	}

	/**
	 * Calculate crossover value.
	 *
	 * @return float Crossover value.
	 */
	private function __crossover() {
		return ceil( ( $this->crossover_total * $this->charge_divider ) - $this->additional_charge );
	}

	/**
	 * Calculate flatline plus charge.
	 *
	 * @return float Flatline plus charge.
	 */
	private function __flatline_plus_charge() {
		return floor( ( $this->cap - $this->additional_charge ) / $this->percentage );
	}

	/**
	 * Calculate flatline value.
	 *
	 * @return float Flatline value.
	 */
	private function __flatline() {
		return $this->flatline_plus_charge - $this->cap;
	}

	/**
	 * Add charge for amount in kobo.
	 *
	 * @param int $amountinkobo Amount in kobo.
	 * @return float Charged amount.
	 */
	public function add_for_kobo( $amountinkobo ) {
		if ( $amountinkobo > $this->flatline ) {
			return $amountinkobo + $this->cap;
		} elseif ( $amountinkobo > $this->crossover ) {
			return ceil( ( $amountinkobo + $this->additional_charge ) / $this->charge_divider );
		} else {
			return ceil( $amountinkobo / $this->charge_divider );
		}
	}

	/**
	 * Add charge for amount in NGN.
	 *
	 * @param int $amountinngn Amount in NGN.
	 * @return float Charged amount.
	 */
	public function add_for_ngn( $amountinngn ) {
		return $this->add_for_kobo( ceil( $amountinngn * 100 ) ) / 100;
	}
}
