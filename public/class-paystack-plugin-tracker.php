<?php
/**
 * Plugin tracker for KKD Paystack.
 *
 * Handles tracking of Paystack plugin transactions.
 */

/**
 * Class Kkd_Pff_Paystack_Plugin_Tracker
 *
 * Tracks transactions made via the Paystack payment gateway.
 */
class Kkd_Pff_Paystack_Plugin_Tracker
{
    /**
     * The public key for Paystack transactions.
     *
     * @var string
     */
    public $public_key;

    /**
     * The name of the plugin.
     *
     * @var string
     */
    public $plugin_name;

    /**
     * Constructs the plugin tracker.
     *
     * @param string $plugin The name of the plugin.
     * @param string $pk     The public key for Paystack transactions.
     */
    public function __construct($plugin, $pk)
    {
        // Configure plugin name
        // Configure public key
        $this->plugin_name = $plugin;
        $this->public_key = $pk;
    }

    /**
     * Logs a successful transaction.
     *
     * @param string $trx_ref The transaction reference.
     */
    public function logTransactionSuccess($trx_ref)
    {
        // Send reference to logger along with plugin name and public key
        $url = "https://plugin-tracker.paystackintegrations.com/log/charge_success";

        $fields = [
            'plugin_name'            => $this->plugin_name,
            'transaction_reference'  => $trx_ref,
            'public_key'             => $this->public_key,
        ];

        $fields_string = http_build_query($fields);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute post
        $result = curl_exec($ch);
    }
}
