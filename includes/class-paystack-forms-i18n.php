<?php


class Kkd_Pff_Paystack_i18n
{


    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() 
    {

        load_plugin_textdomain(
            'pff-paystack',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }



}
