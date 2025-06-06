<?php

namespace NF_FU_LIB\Aws\S3\RegionalEndpoint;

use NF_FU_LIB\Aws\AbstractConfigurationProvider;
use NF_FU_LIB\Aws\CacheInterface;
use NF_FU_LIB\Aws\ConfigurationProviderInterface;
use NF_FU_LIB\Aws\S3\RegionalEndpoint\Exception\ConfigurationException;
use NF_FU_LIB\GuzzleHttp\Promise;
use NF_FU_LIB\GuzzleHttp\Promise\Create;
/**
 * A configuration provider is a function that returns a promise that is
 * fulfilled with a {@see \Aws\S3\RegionalEndpoint\ConfigurationInterface}
 * or rejected with an {@see \Aws\S3\RegionalEndpoint\Exception\ConfigurationException}.
 *
 * <code>
 * use Aws\S3\RegionalEndpoint\ConfigurationProvider;
 * $provider = ConfigurationProvider::defaultProvider();
 * // Returns a ConfigurationInterface or throws.
 * $config = $provider()->wait();
 * </code>
 *
 * Configuration providers can be composed to create configuration using
 * conditional logic that can create different configurations in different
 * environments. You can compose multiple providers into a single provider using
 * {@see \Aws\S3\RegionalEndpoint\ConfigurationProvider::chain}. This function
 * accepts providers as variadic arguments and returns a new function that will
 * invoke each provider until a successful configuration is returned.
 *
 * <code>
 * // First try an INI file at this location.
 * $a = ConfigurationProvider::ini(null, '/path/to/file.ini');
 * // Then try an INI file at this location.
 * $b = ConfigurationProvider::ini(null, '/path/to/other-file.ini');
 * // Then try loading from environment variables.
 * $c = ConfigurationProvider::env();
 * // Combine the three providers together.
 * $composed = ConfigurationProvider::chain($a, $b, $c);
 * // Returns a promise that is fulfilled with a configuration or throws.
 * $promise = $composed();
 * // Wait on the configuration to resolve.
 * $config = $promise->wait();
 * </code>
 */
class ConfigurationProvider extends AbstractConfigurationProvider implements ConfigurationProviderInterface
{
    const ENV_ENDPOINTS_TYPE = 'AWS_S3_US_EAST_1_REGIONAL_ENDPOINT';
    const INI_ENDPOINTS_TYPE = 's3_us_east_1_regional_endpoint';
    const DEFAULT_ENDPOINTS_TYPE = 'legacy';
    public static $cacheKey = 'aws_s3_us_east_1_regional_endpoint_config';
    protected static $interfaceClass = ConfigurationInterface::class;
    protected static $exceptionClass = ConfigurationException::class;
    /**
     * Create a default config provider that first checks for environment
     * variables, then checks for a specified profile in ~/.aws/config, then
     * checks for the "default" profile in ~/.aws/config, and failing those uses
     * a default fallback set of configuration options.
     *
     * This provider is automatically wrapped in a memoize function that caches
     * previously provided config options.
     *
     * @param array $config
     *
     * @return callable
     */
    public static function defaultProvider(array $config = [])
    {
        $configProviders = [self::env(), self::ini(), self::fallback()];
        $memo = self::memoize(\call_user_func_array(self::class.'::chain', $configProviders));
        if (isset($config['s3_us_east_1_regional_endpoint']) && $config['s3_us_east_1_regional_endpoint'] instanceof CacheInterface) {
            return self::cache($memo, $config['s3_us_east_1_regional_endpoint'], self::$cacheKey);
        }
        return $memo;
    }
    public static function env()
    {
        return function () {
            // Use config from environment variables, if available
            $endpointsType = \getenv(self::ENV_ENDPOINTS_TYPE);
            if (!empty($endpointsType)) {
                return Create::promiseFor(new Configuration($endpointsType));
            }
            return self::reject('Could not find environment variable config' . ' in ' . self::ENV_ENDPOINTS_TYPE);
        };
    }
    /**
     * Config provider that creates config using an ini file stored
     * in the current user's home directory.
     *
     * @param string|null $profile  Profile to use. If not specified will use
     *                              the "default" profile in "~/.aws/config".
     * @param string|null $filename If provided, uses a custom filename rather
     *                              than looking in the home directory.
     *
     * @return callable
     */
    public static function ini($profile = null, $filename = null)
    {
        $filename = $filename ?: self::getHomeDir() . '/.aws/config';
        $profile = $profile ?: (\getenv(self::ENV_PROFILE) ?: 'default');
        return function () use($profile, $filename) {
            if (!\is_readable($filename)) {
                return self::reject("Cannot read configuration from {$filename}");
            }
            $data = \NF_FU_LIB\Aws\parse_ini_file($filename, \true);
            if ($data === \false) {
                return self::reject("Invalid config file: {$filename}");
            }
            if (!isset($data[$profile])) {
                return self::reject("'{$profile}' not found in config file");
            }
            if (!isset($data[$profile][self::INI_ENDPOINTS_TYPE])) {
                return self::reject("Required S3 regional endpoint config values \n                    not present in INI profile '{$profile}' ({$filename})");
            }
            return Create::promiseFor(new Configuration($data[$profile][self::INI_ENDPOINTS_TYPE]));
        };
    }
    /**
     * Fallback config options when other sources are not set.
     *
     * @return callable
     */
    public static function fallback()
    {
        return function () {
            return Create::promiseFor(new Configuration(self::DEFAULT_ENDPOINTS_TYPE));
        };
    }
    /**
     * Unwraps a configuration object in whatever valid form it is in,
     * always returning a ConfigurationInterface object.
     *
     * @param  mixed $config
     * @return ConfigurationInterface
     * @throws \InvalidArgumentException
     */
    public static function unwrap($config)
    {
        if (\is_callable($config)) {
            $config = $config();
        }
        if ($config instanceof Promise\PromiseInterface) {
            $config = $config->wait();
        }
        if ($config instanceof ConfigurationInterface) {
            return $config;
        }
        if (\is_string($config)) {
            return new Configuration($config);
        }
        if (\is_array($config) && isset($config['endpoints_type'])) {
            return new Configuration($config['endpoints_type']);
        }
        throw new \InvalidArgumentException('Not a valid S3 regional endpoint ' . 'configuration argument.');
    }
}
