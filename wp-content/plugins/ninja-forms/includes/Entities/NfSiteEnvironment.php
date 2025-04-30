<?php

namespace NinjaForms\Includes\Entities;

use JsonSerializable;

class NfSiteEnvironment implements JsonSerializable
{
    const STRUCTURE = [
        'site_id' => 'int',
        'nf_version' => 'string',
        'nf_db_version' => 'string',
        'wp_version' => 'string',
        'multisite_enabled' => 'int',
        'server_type' => 'string',
        'tls_version' => 'string',
        'php_version' => 'string',
        'mysql_version' => 'string',
        'wp_debug_mode' => 'int',
        'wp_lang' => 'string',
        'wp_max_upload_size' => 'string',
        'php_max_post_size' => 'string',
        'hostname' => 'string',
        'smtp' => 'string',
        'smtp_port' => 'string',
        'active_plugins' => 'array',
        'wp_memory_limit' => 'string',
        'deprecated_loaded' => 'bool',
        'site_timezone' => 'string',
        'nf_gatekeeper' => 'int',
        'siteTheme' => 'string',
    ];

    public int $site_id = 0;
    public string $nf_version = '';
    public string $nf_db_version = '';
    public string $wp_version = '';
    public int $multisite_enabled = 0;
    public string $server_type = '';
    public string $tls_version = '';
    public string $php_version = '';
    public string $mysql_version = '';
    public int $wp_debug_mode = 0;
    public string $wp_lang = '';
    public string $wp_max_upload_size = '';
    public string $php_max_post_size = '';
    public string $hostname = '';
    public string $smtp = '';
    public string $smtp_port = '';
    public array $active_plugins = [];
    public string $wp_memory_limit = '';
    public bool $deprecated_loaded = false;
    public string $site_timezone = '';
    public int $nf_gatekeeper = 100;
    public string $siteTheme = '';

    /**
     * Construct entity from array
     *
     * @param array $array
     * @return NfSiteEnvironment
     */
    public static function fromArray(array $array): NfSiteEnvironment
    {
        $obj = new static();

        foreach (self::STRUCTURE as $property => $propertyType) {

            $addThis = false;

            if (isset($array[$property])) {

                switch ($propertyType) {
                    case 'string':
                        if (is_string($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    case 'array':
                        if (\is_array($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    case 'bool':
                        if (\is_bool($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    case 'int':
                        if (\is_int($array[$property])) {
                            $addThis = true;
                        }
                        break;
                    default:
                        // do not add if property type is not specified    
                }
            }

            if ($addThis) {
                $obj->$property = $array[$property];
            }
        }

        return  $obj;
    }

    /** @inheritDoc */
    public function toArray()
    {
        $return = [];

        foreach (array_keys(self::STRUCTURE) as $property) {

            $return[$property] = $this->$property;
        }

        return $return;
    }

    /** @inheritDoc */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
