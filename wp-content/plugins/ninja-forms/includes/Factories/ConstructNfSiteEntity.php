<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Entities\NfSite;

class ConstructNfSiteEntity
{

    /**
     * Return constructed site entity
     *
     * @return NfSite
     */
    public function handle(): NfSite
    {
        $array = $this->constructSiteVariableArray();

        $return = NfSite::fromArray($array);

        return $return;
    }

    /**
     * Construct site variable array
     *
     * @return array
     */
    protected function constructSiteVariableArray(): array
    {
        $ip_address = '';
        if ( array_key_exists( 'SERVER_ADDR', $_SERVER ) ) {
            $ip_address = $_SERVER[ 'SERVER_ADDR' ];
        } else if ( array_key_exists( 'LOCAL_ADDR', $_SERVER ) ) {
            $ip_address = $_SERVER[ 'LOCAL_ADDR' ];
        }

        $return = array(
            'url'           => site_url(),
            'ip_address'    => $ip_address
        );

        return $return;
    }
}
