<?php

if (!defined('ABSPATH')) {
    exit();
}

/**
 * This class overides the base class to provide upto date data
 */

class Zimrate_Woo_Multi_Currency_Data extends WOOMULTI_CURRENCY_F_Data
{
    /**
     * Get country code by currency
     *
     * @param  string   $currency_code
     * @return string
     */
    public function get_country_data($currency_code)
    {
        if (in_array($currency_code, zimrate_get_isos())) {
            $currency_code = 'ZWD';
        }

        return parent::get_country_data($currency_code);
    }

    /**
     * @param  strinf   $country_code
     * @return string
     */
    public function get_country_freebase($country_code)
    {
        if (in_array($country_code, zimrate_get_isos())) {
            $data = '/m/02c1rx';
        } else {
            $data = parent::get_country_freebase($country_code);
        }

        return $data;
    }
}
