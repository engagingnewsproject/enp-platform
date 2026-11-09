<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Country List, keyed by translated name.
 *
 * Kept as it has always been, name => ISO code, for any add-on or custom code
 * that reads `Ninja_Forms()->config( 'CountryList' )`, and still filterable
 * through `ninja_forms_config_country_list`.
 *
 * WARNING: this shape can collide. The key is a translated name, so in any
 * language where two countries translate alike one entry silently overwrites
 * the other and its code disappears from the array. That happens today in
 * several of the shipped languages. Core no longer reads this file for the
 * country field; it reads `CountryListByCode.php`, where the key is the code
 * and no two countries can merge. New code should do the same.
 *
 * @since 2.2.28
 */

$countries_by_code = include __DIR__ . '/CountryListByCode.php';

$countries = array();

foreach ( $countries_by_code as $code => $name ) {
    $countries[ $name ] = $code;
}

return apply_filters( 'ninja_forms_config_country_list', $countries );
