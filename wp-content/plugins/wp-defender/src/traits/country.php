<?php
/**
 * Country helper.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use DI\NotFoundException;
use DI\DependencyException;
use WP_Defender\Extra\GeoIp;
use MaxMind\Db\Reader\InvalidDatabaseException;
use WP_Defender\Model\Setting\Blacklist_Lockout;

trait Country {

	/**
	 * Get country of the current user.
	 *
	 * @param  string $ip  IPV4 or IPV6 address.
	 *
	 * @return array|bool
	 * @throws DependencyException                     Container exception.
	 * @throws NotFoundException                       Thrown when DI injected class not found.
	 * @throws InvalidDatabaseException Thrown for unexpected data is found in DB.
	 */
	public function get_current_country( $ip ) {
		if ( defender_is_wp_cli() ) {
			// Never catch if from cli.
			return false;
		}
		$service = wd_di()->get( \WP_Defender\Component\Blacklist_Lockout::class );
		if ( ! $service->is_geodb_downloaded() ) {
			return false;
		}
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}
		$model = wd_di()->get( Blacklist_Lockout::class );
		// Additional check if MaxMind dir is deleted.
		if ( is_null( $model->geodb_path ) || ! is_file( $model->geodb_path ) ) {
			return false;
		}
		$helper = new GeoIp( $model->geodb_path );

		// Todo: add check for unhandled exceptions.
		return $helper->ip_to_country( $ip );
	}

	/**
	 * Copy the list from https://gist.github.com/DHS/1340150.
	 *
	 * @return array
	 * @since 2.8.0 Add hook.
	 */
	public function countries_list(): array {
		return apply_filters(
			'wd_countries',
			array(
				'AD' => 'Andorra',
				'AF' => 'Afghanistan',
				'AG' => 'Antigua and Barbuda',
				'AI' => 'Anguilla',
				'AL' => 'Albania',
				'AM' => 'Armenia',
				'AO' => 'Angola',
				'AP' => 'Asia/Pacific Region',
				'AQ' => 'Antarctica',
				'AR' => 'Argentina',
				'AS' => 'American Samoa',
				'AT' => 'Austria',
				'AU' => 'Australia',
				'AW' => 'Aruba',
				'AX' => 'Aland Islands',
				'AZ' => 'Azerbaijan',
				'BA' => 'Bosnia and Herzegovina',
				'BB' => 'Barbados',
				'BD' => 'Bangladesh',
				'BE' => 'Belgium',
				'BF' => 'Burkina Faso',
				'BG' => 'Bulgaria',
				'BH' => 'Bahrain',
				'BI' => 'Burundi',
				'BJ' => 'Benin',
				'BL' => 'Saint Barthelemy',
				'BM' => 'Bermuda',
				'BN' => 'Brunei Darussalam',
				'BO' => 'Bolivia',
				'BQ' => 'Bonaire, Saint Eustatius and Saba',
				'BR' => 'Brazil',
				'BS' => 'Bahamas',
				'BT' => 'Bhutan',
				'BV' => 'Bouvet Island',
				'BW' => 'Botswana',
				'BY' => 'Belarus',
				'BZ' => 'Belize',
				'CA' => 'Canada',
				'CC' => 'Cocos (Keeling) Islands',
				'CD' => 'Congo, The Democratic Republic of the',
				'CF' => 'Central African Republic',
				'CG' => 'Congo',
				'CH' => 'Switzerland',
				'CI' => "Cote d'Ivoire",
				'CK' => 'Cook Islands',
				'CL' => 'Chile',
				'CM' => 'Cameroon',
				'CN' => 'China',
				'CO' => 'Colombia',
				'CR' => 'Costa Rica',
				'CU' => 'Cuba',
				'CV' => 'Cape Verde',
				'CW' => 'Curacao',
				'CX' => 'Christmas Island',
				'CY' => 'Cyprus',
				'CZ' => 'Czech Republic',
				'DE' => 'Germany',
				'DJ' => 'Djibouti',
				'DK' => 'Denmark',
				'DM' => 'Dominica',
				'DO' => 'Dominican Republic',
				'DZ' => 'Algeria',
				'EC' => 'Ecuador',
				'EE' => 'Estonia',
				'EG' => 'Egypt',
				'EH' => 'Western Sahara',
				'ER' => 'Eritrea',
				'ES' => 'Spain',
				'ET' => 'Ethiopia',
				'EU' => 'Europe',
				'FI' => 'Finland',
				'FJ' => 'Fiji',
				'FK' => 'Falkland Islands (Malvinas)',
				'FM' => 'Micronesia, Federated States of',
				'FO' => 'Faroe Islands',
				'FR' => 'France',
				'GA' => 'Gabon',
				'GB' => 'United Kingdom',
				'GD' => 'Grenada',
				'GE' => 'Georgia',
				'GF' => 'French Guiana',
				'GG' => 'Guernsey',
				'GH' => 'Ghana',
				'GI' => 'Gibraltar',
				'GL' => 'Greenland',
				'GM' => 'Gambia',
				'GN' => 'Guinea',
				'GP' => 'Guadeloupe',
				'GQ' => 'Equatorial Guinea',
				'GR' => 'Greece',
				'GS' => 'South Georgia and the South Sandwich Islands',
				'GT' => 'Guatemala',
				'GU' => 'Guam',
				'GW' => 'Guinea-Bissau',
				'GY' => 'Guyana',
				'HK' => 'Hong Kong',
				'HM' => 'Heard Island and McDonald Islands',
				'HN' => 'Honduras',
				'HR' => 'Croatia',
				'HT' => 'Haiti',
				'HU' => 'Hungary',
				'ID' => 'Indonesia',
				'IE' => 'Ireland',
				'IL' => 'Israel',
				'IM' => 'Isle of Man',
				'IN' => 'India',
				'IO' => 'British Indian Ocean Territory',
				'IQ' => 'Iraq',
				'IR' => 'Iran, Islamic Republic of',
				'IS' => 'Iceland',
				'IT' => 'Italy',
				'JE' => 'Jersey',
				'JM' => 'Jamaica',
				'JO' => 'Jordan',
				'JP' => 'Japan',
				'KE' => 'Kenya',
				'KG' => 'Kyrgyzstan',
				'KH' => 'Cambodia',
				'KI' => 'Kiribati',
				'KM' => 'Comoros',
				'KN' => 'Saint Kitts and Nevis',
				'KP' => "Korea, Democratic People's Republic of",
				'KR' => 'Korea, Republic of',
				'KW' => 'Kuwait',
				'KY' => 'Cayman Islands',
				'KZ' => 'Kazakhstan',
				'LA' => "Lao People's Democratic Republic",
				'LB' => 'Lebanon',
				'LC' => 'Saint Lucia',
				'LI' => 'Liechtenstein',
				'LK' => 'Sri Lanka',
				'LR' => 'Liberia',
				'LS' => 'Lesotho',
				'LT' => 'Lithuania',
				'LU' => 'Luxembourg',
				'LV' => 'Latvia',
				'LY' => 'Libyan Arab Jamahiriya',
				'MA' => 'Morocco',
				'MC' => 'Monaco',
				'MD' => 'Moldova, Republic of',
				'ME' => 'Montenegro',
				'MF' => 'Saint Martin',
				'MG' => 'Madagascar',
				'MH' => 'Marshall Islands',
				'MK' => 'Macedonia',
				'ML' => 'Mali',
				'MM' => 'Myanmar',
				'MN' => 'Mongolia',
				'MO' => 'Macao',
				'MP' => 'Northern Mariana Islands',
				'MQ' => 'Martinique',
				'MR' => 'Mauritania',
				'MS' => 'Montserrat',
				'MT' => 'Malta',
				'MU' => 'Mauritius',
				'MV' => 'Maldives',
				'MW' => 'Malawi',
				'MX' => 'Mexico',
				'MY' => 'Malaysia',
				'MZ' => 'Mozambique',
				'NA' => 'Namibia',
				'NC' => 'New Caledonia',
				'NE' => 'Niger',
				'NF' => 'Norfolk Island',
				'NG' => 'Nigeria',
				'NI' => 'Nicaragua',
				'NL' => 'Netherlands',
				'NO' => 'Norway',
				'NP' => 'Nepal',
				'NR' => 'Nauru',
				'NU' => 'Niue',
				'NZ' => 'New Zealand',
				'OM' => 'Oman',
				'PA' => 'Panama',
				'PE' => 'Peru',
				'PF' => 'French Polynesia',
				'PG' => 'Papua New Guinea',
				'PH' => 'Philippines',
				'PK' => 'Pakistan',
				'PL' => 'Poland',
				'PM' => 'Saint Pierre and Miquelon',
				'PN' => 'Pitcairn',
				'PR' => 'Puerto Rico',
				'PS' => 'Palestinian Territory',
				'PT' => 'Portugal',
				'PW' => 'Palau',
				'PY' => 'Paraguay',
				'QA' => 'Qatar',
				'RE' => 'Reunion',
				'RO' => 'Romania',
				'RS' => 'Serbia',
				'RU' => 'Russian Federation',
				'RW' => 'Rwanda',
				'SA' => 'Saudi Arabia',
				'SB' => 'Solomon Islands',
				'SC' => 'Seychelles',
				'SD' => 'Sudan',
				'SE' => 'Sweden',
				'SG' => 'Singapore',
				'SH' => 'Saint Helena',
				'SI' => 'Slovenia',
				'SJ' => 'Svalbard and Jan Mayen',
				'SK' => 'Slovakia',
				'SL' => 'Sierra Leone',
				'SM' => 'San Marino',
				'SN' => 'Senegal',
				'SO' => 'Somalia',
				'SR' => 'Suriname',
				'SS' => 'South Sudan',
				'ST' => 'Sao Tome and Principe',
				'SV' => 'El Salvador',
				'SX' => 'Sint Maarten',
				'SY' => 'Syrian Arab Republic',
				'SZ' => 'Swaziland',
				'TC' => 'Turks and Caicos Islands',
				'TD' => 'Chad',
				'TF' => 'French Southern Territories',
				'TG' => 'Togo',
				'TH' => 'Thailand',
				'TJ' => 'Tajikistan',
				'TK' => 'Tokelau',
				'TL' => 'Timor-Leste',
				'TM' => 'Turkmenistan',
				'TN' => 'Tunisia',
				'TO' => 'Tonga',
				'TR' => 'Turkey',
				'TT' => 'Trinidad and Tobago',
				'TV' => 'Tuvalu',
				'TW' => 'Taiwan',
				'TZ' => 'Tanzania, United Republic of',
				'UA' => 'Ukraine',
				'UG' => 'Uganda',
				'AE' => 'United Arab Emirates',
				'UM' => 'United States Minor Outlying Islands',
				'US' => 'United States',
				'UY' => 'Uruguay',
				'UZ' => 'Uzbekistan',
				'VA' => 'Holy See (Vatican City State)',
				'VC' => 'Saint Vincent and the Grenadines',
				'VE' => 'Venezuela',
				'VG' => 'Virgin Islands, British',
				'VI' => 'Virgin Islands, U.S.',
				'VN' => 'Vietnam',
				'VU' => 'Vanuatu',
				'WF' => 'Wallis and Futuna',
				'WS' => 'Samoa',
				'YE' => 'Yemen',
				'YT' => 'Mayotte',
				'ZA' => 'South Africa',
				'ZM' => 'Zambia',
				'ZW' => 'Zimbabwe',
				'A1' => 'Anonymous Proxy',
				'A2' => 'Satellite Provider',
				'O1' => 'Other Country',
			)
		);
	}

	/**
	 * Generic method to return country detail of an IP.
	 *
	 * @param  string $ip  IPV4 or IPV6 address.
	 *
	 * @return bool|array false on failure to fetch and array on success.
	 */
	public function ip_to_country( $ip ) {
		$country = $this->get_current_country( $ip );
		if ( ! $country ) {
			// since 3.8.0.
			$country = apply_filters( 'wd_ip_to_country_api', $country, $ip );
		}

		return $country;
	}
}