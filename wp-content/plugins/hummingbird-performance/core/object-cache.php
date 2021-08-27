<?php
/**
 * Hummingbird Redis Object Cache
 *
 * @link    https://wpmudev.com/project/wp-hummingbird/
 * @since   2.5.0
 * @package Hummingbird
 *
 * @wordpress-plugin
 * Plugin Name:       Hummingbird Redis Object Cache
 * Plugin URI:        https://wpmudev.com/project/wp-hummingbird/
 * Description:       Hummingbird object cache powered by Redis.
 * Author:            WPMU DEV
 * Author URI:        https://profiles.wordpress.org/wpmudev/
 *
 * Based on Eric Mann's and Erick Hitter's Redis Object Cache: https://github.com/ericmann/Redis-Object-Cache
 */

if ( ! defined( 'WPHB_REDIS_HOST' ) && ! defined( 'WPHB_REDIS_PORT' ) ) {
	return;
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The cache key to use for retrieval later.
 * @param  mixed      $data   The data to add to the cache.
 * @param  string     $group  Optional. The group to add the cache to. Enables the same key
 *                            to be used across groups. Default empty.
 * @param  int        $expire Optional. When the cache data should expire, in seconds.
 *                            Default 0 (no expiration).
 * @return bool False if cache key and group already exist, true on success.
 * @throws Exception Exception.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, (int) $expire );
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache.
 *
 * This does not mean that plugins can't implement this function when they need
 * to make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return true Always returns true.
 */
function wp_cache_close() {
	return true;
}

/**
 * Decrements numeric cache item's value.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The cache key to decrement.
 * @param  int        $offset Optional. The amount by which to decrement the item's value. Default 1.
 * @param  string     $group  Optional. The group the key is in. Default empty.
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $offset, $group );
}

/**
 * Removes the cache contents matching key and group.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key   What the contents in the cache are called.
 * @param  string     $group Optional. Where the cache contents are grouped. Default empty.
 * @return bool True on successful removal, false on failure.
 */
function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

/**
 * Removes all cache items.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @return bool False on failure, true on success
 */
function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key   The key under which the cache contents are stored.
 * @param  string     $group Optional. Where the cache contents are grouped. Default empty.
 * @param  bool       $force Optional. Whether to force an update of the local cache from the persistent
 *                           cache. Default false.
 * @param  bool       $found Optional. Whether the key was found in the cache (passed by reference).
 *                           Disambiguates a return of false, a storable value. Default null.
 * @return bool|mixed False on failure to retrieve contents or the cache
 *                    contents on success
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Retrieve multiple values from cache.
 *
 * Gets multiple values from cache, including across multiple groups. Mirrors the Memcached Object Cache
 * plugin's argument and return-value formats.
 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) )
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  array $groups Array of groups and keys to retrieve.
 * @return bool|mixed Array of cached values, keys in the format $group:$key. Non-existent keys false
 */
function wp_cache_get_multi( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->get_multi( $groups );
}

/**
 * Increment numeric cache item's value
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The key for the cache contents that should be incremented.
 * @param  int        $offset Optional. The amount by which to increment the item's value. Default 1.
 * @param  string     $group  Optional. The group the key is in. Default empty.
 * @return false|int False on failure, the item's new value on success.
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $offset, $group );
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache  WordPress Object Cache
 *
 * @throws Exception  Exception.
 */
function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache();
}

/**
 * Replaces the contents of the cache with new data.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The key for the cache data that should be replaced.
 * @param  mixed      $data   The new data to store in the cache.
 * @param  string     $group  Optional. The group for the cache data that should be replaced.
 *                            Default empty.
 * @param  int        $expire Optional. When to expire the cache contents, in seconds.
 *                            Default 0 (no expiration).
 * @return bool False if original value does not exist, true if contents were replaced
 * @throws Exception Exception.
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $data, $group, (int) $expire );
}

/**
 * Saves the data to the cache.
 *
 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param  int|string $key    The cache key to use for retrieval later.
 * @param  mixed      $data   The contents to store in the cache.
 * @param  string     $group  Optional. Where to group the cache contents. Enables the same key
 *                            to be used across groups. Default empty.
 * @param  int        $expire Optional. When to expire the cache contents, in seconds.
 *                            Default 0 (no expiration).
 * @return bool False on failure, true on success
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $data, $group, (int) $expire );
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int $blog_id Site ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	$wp_object_cache->switch_to_blog( $blog_id );
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string|array $groups A group or an array of groups to add.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	$wp_object_cache->add_non_persistent_groups( $groups );
}

/**
 * Class WP_Object_Cache
 */
class WP_Object_Cache {

	/**
	 * The Redis client.
	 *
	 * @var mixed
	 */
	private $redis;

	/**
	 * The Redis server version.
	 *
	 * @var null|string
	 */
	private $redis_version = null;

	/**
	 * Track if Redis is available
	 *
	 * @var bool
	 */
	private $redis_connected = false;

	/**
	 * Holds the non-Redis objects.
	 *
	 * @var array
	 */
	public $cache = array();

	/**
	 * Name of the used Redis client
	 *
	 * @var bool
	 */
	public $redis_client = null;

	/**
	 * List of global groups.
	 *
	 * @var array
	 */
	public $global_groups = array(
		'blog-details',
		'blog-id-cache',
		'blog-lookup',
		'global-posts',
		'networks',
		'rss',
		'sites',
		'site-details',
		'site-lookup',
		'site-options',
		'site-transient',
		'users',
		'useremail',
		'userlogins',
		'usermeta',
		'user_meta',
		'userslugs',
	);

	/**
	 * List of groups that will not be flushed.
	 *
	 * @var array
	 */
	public $unflushable_groups = array();

	/**
	 * List of groups not saved to Redis.
	 *
	 * @var array
	 */
	public $ignored_groups = array( 'counts', 'plugins' );

	/**
	 * Prefix used for global groups.
	 *
	 * @var string
	 */
	public $global_prefix = '';

	/**
	 * Prefix used for non-global groups.
	 *
	 * @var string
	 */
	public $blog_prefix = '';

	/**
	 * Track how many requests were found in cache
	 *
	 * @var int
	 */
	public $cache_hits = 0;

	/**
	 * Track how may requests were not cached
	 *
	 * @var int
	 */
	public $cache_misses = 0;

	/**
	 * Error message for redis connection
	 *
	 * @var int
	 */
	public $redis_error = '';

	/**
	 * WP_Object_Cache constructor.
	 *
	 * @throws Exception  Exception.
	 */
	public function __construct() {
		global $blog_id, $table_prefix;

		$parameters = array(
			'host'           => defined( 'WPHB_REDIS_HOST' ) ? WPHB_REDIS_HOST : '127.0.0.1',
			'port'           => defined( 'WPHB_REDIS_PORT' ) ? WPHB_REDIS_PORT : 6379,
			'timeout'        => 5,
			'read_timeout'   => 5,
			'retry_interval' => 0,
		);

		if ( defined( 'WP_REDIS_IGNORED_GROUPS' ) && is_array( WP_REDIS_IGNORED_GROUPS ) ) {
			$this->ignored_groups = array_unique( array_merge( $this->ignored_groups, array_map( array( $this, 'sanitize_key' ), WP_REDIS_IGNORED_GROUPS ) ) );
		}

		$client = 'predis';
		if ( class_exists( 'Redis' ) && 0 !== strcasecmp( 'predis', $client ) ) {
			$client = defined( 'HHVM_VERSION' ) ? 'hhvm' : 'pecl';
		}

		$scheme = '/' === substr( $parameters['host'], 0, 1 ) ? 'unix' : 'tcp';

		try {
			if ( 'unix' === $scheme && ( 'hhvm' === $client || 'pecl' === $client ) ) {
				$parameters['port'] = null;
			}

			if ( 'hhvm' === $client ) {
				$this->redis_client = sprintf( 'HHVM Extension (v%s)', constant( 'HHVM_VERSION' ) );

				$this->redis = new Redis();
				$this->redis->connect( $parameters['host'], $parameters['port'], $parameters['timeout'], null, $parameters['retry_interval'] );

				if ( $parameters['read_timeout'] ) {
					$this->redis->setOption( Redis::OPT_READ_TIMEOUT, $parameters['read_timeout'] );
				}
			}

			if ( 'pecl' === $client ) {
				$phpredis_version   = phpversion( 'redis' );
				$this->redis_client = sprintf( 'PECL Extension (v%s)', $phpredis_version );

				$this->redis = new Redis();
				if ( version_compare( $phpredis_version, '3.1.3', '>=' ) ) {
					$this->redis->connect( $parameters['host'], $parameters['port'], $parameters['timeout'], null, $parameters['retry_interval'], $parameters['read_timeout'] );
				} else {
					$this->redis->connect( $parameters['host'], $parameters['port'], $parameters['timeout'], null, $parameters['retry_interval'] );
				}
			}

			if ( ( 'hhvm' === $client || 'pecl' === $client ) && defined( 'WPHB_REDIS_PASSWORD' ) ) {
				$this->redis->auth( WPHB_REDIS_PASSWORD );
			}

			if ( ( 'hhvm' === $client || 'pecl' === $client ) && defined( 'WPHB_REDIS_DB_ID' ) ) {
				$this->redis->select( WPHB_REDIS_DB_ID );
			}

			if ( 'predis' === $client ) {
				$this->redis_client = 'Predis';

				// Load bundled Predis library.
				if ( ! class_exists( 'Predis\Client' ) ) {
					$predis_pro = sprintf(
						'%s/wp-hummingbird/vendor/predis/predis/autoload.php',
						defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins'
					);

					$predis_free = sprintf(
						'%s/hummingbird-performance/vendor/predis/predis/autoload.php',
						defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : WP_CONTENT_DIR . '/plugins'
					);

					if ( file_exists( $predis_pro ) ) {
						/* @noinspection PhpIncludeInspection */
						include_once $predis_pro;
					} elseif ( file_exists( $predis_free ) ) {
						/* @noinspection PhpIncludeInspection */
						include_once $predis_free;
					} else {
						throw new Exception( 'Predis library not found. Re-install Hummingbird plugin or delete object-cache.php.' );
					}
				}

				$options = array();

				if ( 'unix' === $scheme ) {
					$parameters['scheme'] = $scheme;
					$parameters['path']   = $parameters['host'];
					unset( $parameters['host'] );
					unset( $parameters['port'] );
				}

				if ( $parameters['read_timeout'] ) {
					$parameters['read_write_timeout'] = $parameters['read_timeout'];
				}

				if ( defined( 'WPHB_REDIS_PASSWORD' ) ) {
					$options['parameters']['password'] = WPHB_REDIS_PASSWORD;
				}

				if ( defined( 'WPHB_REDIS_DB_ID' ) ) {
					$options['parameters']['database'] = WPHB_REDIS_DB_ID;
				}

				$this->redis = new Predis\Client( $parameters, $options );

				$this->redis->connect();

				$this->redis_client .= sprintf( ' (v%s)', Predis\Client::VERSION );
			}

			$this->redis->ping();

			$server_info = $this->redis->info( 'SERVER' );

			if ( isset( $server_info['redis_version'] ) ) {
				$this->redis_version = $server_info['redis_version'];
			} elseif ( isset( $server_info['Server']['redis_version'] ) ) {
				$this->redis_version = $server_info['Server']['redis_version'];
			}

			$this->redis_connected = true;
		} catch ( Exception $exception ) {
			$this->redis_connected = false;

			$this->redis_error = $exception->getMessage();

			// When Redis is unavailable, fall back to the internal cache by forcing all groups to be "no redis" groups.
			$this->ignored_groups = array_unique( array_merge( $this->ignored_groups, $this->global_groups ) );
		}

		// Assign global and blog prefixes for use with keys.
		if ( function_exists( 'is_multisite' ) ) {
			$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
			$this->blog_prefix   = ( is_multisite() ? $blog_id : $table_prefix );
		}
	}

	/**
	 * Is Redis available?
	 *
	 * @return bool
	 */
	public function redis_status() {
		return $this->redis_connected;
	}

	/**
	 * Returns the Redis instance.
	 *
	 * @return mixed
	 */
	public function redis_instance() {
		return $this->redis;
	}

	/**
	 * Returns the Redis server version.
	 *
	 * @return null|string
	 */
	public function redis_version() {
		return $this->redis_version;
	}

	/**
	 * Adds a value to cache.
	 *
	 * If the specified key already exists, the value is not stored and the function
	 * returns false.
	 *
	 * @param  string $key        The key under which to store the value.
	 * @param  mixed  $value      The value to store.
	 * @param  string $group      The group value appended to the $key.
	 * @param  int    $expiration The expiration time, defaults to 0.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function add( $key, $value, $group = 'default', $expiration = 0 ) {
		return $this->add_or_replace( true, $key, $value, $group, (int) $expiration );
	}

	/**
	 * Replace a value in the cache.
	 *
	 * If the specified key doesn't exist, the value is not stored and the function
	 * returns false.
	 *
	 * @param  string $key        The key under which to store the value.
	 * @param  mixed  $value      The value to store.
	 * @param  string $group      The group value appended to the $key.
	 * @param  int    $expiration The expiration time, defaults to 0.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function replace( $key, $value, $group = 'default', $expiration = 0 ) {
		return $this->add_or_replace( false, $key, $value, $group, (int) $expiration );
	}

	/**
	 * Add or replace a value in the cache.
	 *
	 * Add does not set the value if the key exists; replace does not replace if the value doesn't exist.
	 *
	 * @param  bool   $add        True if should only add if value doesn't exist, false to only add when value already exists.
	 * @param  string $key        The key under which to store the value.
	 * @param  mixed  $value      The value to store.
	 * @param  string $group      The group value appended to the $key.
	 * @param  int    $expiration The expiration time, defaults to 0.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	protected function add_or_replace( $add, $key, $value, $group = 'default', $expiration = 0 ) {
		$addition_suspended = function_exists( 'wp_suspend_cache_addition' ) && wp_suspend_cache_addition();

		if ( $add && $addition_suspended ) {
			return false;
		}

		$result      = true;
		$derived_key = $this->build_key( $key, $group );

		// Save if group not excluded and redis is up.
		if ( ! in_array( $group, $this->ignored_groups, true ) && $this->redis_status() ) {
			$exists = $this->redis->exists( $derived_key );

			if ( $add == $exists ) {
				return false;
			}

			$expiration = $this->validate_expiration( $expiration );

			if ( $expiration ) {
				$result = $this->parse_redis_response( $this->redis->setex( $derived_key, $expiration, $this->maybe_serialize( $value ) ) );
			} else {
				$result = $this->parse_redis_response( $this->redis->set( $derived_key, $this->maybe_serialize( $value ) ) );
			}
		}

		$exists = isset( $this->cache[ $derived_key ] );

		if ( $add == $exists ) {
			return false;
		}

		if ( $result ) {
			$this->add_to_internal_cache( $derived_key, $value );
		}

		return $result;
	}

	/**
	 * Remove the item from the cache.
	 *
	 * @param  string $key   The key under which to store the value.
	 * @param  string $group The group value appended to the $key.
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function delete( $key, $group = 'default' ) {
		$result      = false;
		$derived_key = $this->build_key( $key, $group );

		if ( isset( $this->cache[ $derived_key ] ) ) {
			unset( $this->cache[ $derived_key ] );
			$result = true;
		}

		if ( $this->redis_status() && ! in_array( $group, $this->ignored_groups, true ) ) {
			$result = $this->parse_redis_response( $this->redis->del( $derived_key ) );
		}

		return $result;
	}

	/**
	 * Invalidate all items in the cache.
	 *
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function flush() {
		$results     = array();
		$this->cache = array();

		if ( $this->redis_status() ) {
			if ( defined( 'WP_REDIS_SALT' ) && WP_REDIS_SALT ) {
				$lua_script = $this->get_flush_closure( trim( WP_REDIS_SALT ) );
				$results[]  = $this->parse_redis_response( $lua_script() );
			} else {
				$results[] = $this->parse_redis_response( $this->redis->flushdb() );
			}
		}

		if ( empty( $results ) ) {
			return false;
		}

		foreach ( $results as $result ) {
			if ( ! $result ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a closure to flush selectively.
	 *
	 * @param  string $salt  The salt to be used to differentiate.
	 * @return callable      Generated callable executing the lua script.
	 */
	protected function get_flush_closure( $salt ) {
		if ( $this->unflushable_groups ) {
			return $this->lua_flush_extended_closure( $salt );
		} else {
			return $this->lua_flush_closure( $salt );
		}
	}

	/**
	 * Returns a closure ready to be called to flush selectively ignoring unflushable groups.
	 *
	 * @param  string $salt  The salt to be used to differentiate.
	 * @return callable      Generated callable executing the lua script.
	 */
	protected function lua_flush_closure( $salt ) {
		return function () use ( $salt ) {
			$script = <<<LUA
            local cur = 0
            local i = 0
            local tmp
            repeat
                tmp = redis.call('SCAN', cur, 'MATCH', '{$salt}*')
                cur = tonumber(tmp[1])
                if tmp[2] then
                    for _, v in pairs(tmp[2]) do
                        redis.call('del', v)
                        i = i + 1
                    end
                end
            until 0 == cur
            return i
LUA;

			if ( version_compare( $this->redis_version(), '5', '<' ) && version_compare( $this->redis_version(), '3.2', '>=' ) ) {
				$script = 'redis.replicate_commands()' . "\n" . $script;
			}

			$args = ( $this->redis instanceof Predis\Client )
				? array( $script, 0 )
				: array( $script );

			return call_user_func_array( array( $this->redis, 'eval' ), $args );
		};
	}

	/**
	 * Returns a closure ready to be called to flush selectively.
	 *
	 * @param  string $salt The salt to be used to differentiate.
	 * @return callable      Generated callable executing the lua script.
	 */
	protected function lua_flush_extended_closure( $salt ) {
		return function () use ( $salt ) {
			$salt_length = strlen( $salt );

			$non_flushable = array_map(
				function ( $group ) {
					return ":{$group}:";
				},
				$this->unflushable_groups
			);

			$script = <<<LUA
            local cur = 0
            local i = 0
            local d, tmp
            repeat
                tmp = redis.call('SCAN', cur, 'MATCH', '{$salt}*')
                cur = tonumber(tmp[1])
                if tmp[2] then
                    for _, v in pairs(tmp[2]) do
                        d = true
                        for _, s in pairs(KEYS) do
                            d = d and not v:find(s, {$salt_length})
                            if not d then break end
                        end
                        if d then
                            redis.call('del', v)
                            i = i + 1
                        end
                    end
                end
            until 0 == cur
            return i
LUA;
			if ( version_compare( $this->redis_version(), '5', '<' ) && version_compare( $this->redis_version(), '3.2', '>=' ) ) {
				$script = 'redis.replicate_commands()' . "\n" . $script;
			}

			$args = ( $this->redis instanceof Predis\Client )
				? array_merge( array( $script, count( $non_flushable ) ), $non_flushable )
				: array( $script, $non_flushable, count( $non_flushable ) );

			return call_user_func_array( array( $this->redis, 'eval' ), $args );
		};
	}

	/**
	 * Retrieve object from cache.
	 *
	 * Gets an object from cache based on $key and $group.
	 *
	 * @param  string    $key   The key under which to store the value.
	 * @param  string    $group The group value appended to the $key.
	 * @param  bool      $force Optional. Whether to force a refetch rather than relying on the local
	 *                          cache. Default false.
	 * @param  bool|null $found Optional. Whether the key was found in the cache. Disambiguates a return of
	 *                          false, a storable value. Passed by reference. Default null.
	 * @return bool|mixed Cached object value.
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$derived_key = $this->build_key( $key, $group );

		if ( isset( $this->cache[ $derived_key ] ) && ! $force ) {
			$found = true;
			$this->cache_hits++;
			return is_object( $this->cache[ $derived_key ] ) ? clone $this->cache[ $derived_key ] : $this->cache[ $derived_key ];
		} elseif ( in_array( $group, $this->ignored_groups, true ) || ! $this->redis_status() ) {
			$found = false;
			$this->cache_misses++;
			return false;
		}

		$result = $this->redis->get( $derived_key );
		if ( null === $result || false === $result ) {
			$found = false;
			$this->cache_misses++;
			return false;
		} else {
			$found = true;
			$this->cache_hits++;
			// All non-numeric values are serialized.
			$result = $this->maybe_unserialize( $result );
		}

		$this->add_to_internal_cache( $derived_key, $result );

		return $result;
	}

	/**
	 * Retrieve multiple values from cache.
	 *
	 * Gets multiple values from cache, including across multiple groups. Mirrors the Memcached Object Cache plugin's
	 * argument and return-value formats.
	 * Usage: array( 'group0' => array( 'key0', 'key1', 'key2', ), 'group1' => array( 'key0' ) ).
	 *
	 * @param  array $groups Array of groups and keys to retrieve.
	 * @return array|false Array of cached values, keys in the format $group:$key. Non-existent keys null.
	 */
	public function get_multi( $groups ) {
		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return false;
		}

		// Retrieve requested caches and reformat results to mimic Memcached Object Cache's output.
		$cache = array();

		foreach ( $groups as $group => $keys ) {
			if ( in_array( $group, $this->ignored_groups, true ) || ! $this->redis_status() ) {
				foreach ( $keys as $key ) {
					$cache[ $this->build_key( $key, $group ) ] = $this->get( $key, $group );
				}
			} else {
				// Reformat arguments as expected by Redis.
				$derived_keys = array();

				foreach ( $keys as $key ) {
					$derived_keys[] = $this->build_key( $key, $group );
				}

				// Retrieve from cache in a single request.
				$group_cache = $this->redis->mget( $derived_keys );

				// Build an array of values looked up, keyed by the derived cache key.
				$group_cache = array_combine( $derived_keys, $group_cache );

				// Restores cached data to its original data type.
				$group_cache = array_map( array( $this, 'maybe_unserialize' ), $group_cache );

				// Redis returns null for values not found in cache, but expected return value is false in this instance.
				$group_cache = array_map( array( $this, 'filter_redis_get_multi' ), $group_cache );

				$cache = array_merge( $cache, $group_cache );
			}
		}

		// Add to the internal cache the found values from Redis.
		foreach ( $cache as $key => $value ) {
			if ( $value ) {
				$this->cache_hits++;
				$this->add_to_internal_cache( $key, $value );
			} else {
				$this->cache_misses++;
			}
		}

		return $cache;
	}

	/**
	 * Sets a value in cache.
	 *
	 * The value is set whether or not this key already exists in Redis.
	 *
	 * @param  string $key        The key under which to store the value.
	 * @param  mixed  $value      The value to store.
	 * @param  string $group      The group value appended to the $key.
	 * @param  int    $expiration The expiration time, defaults to 0.
	 * @return bool               Returns TRUE on success or FALSE on failure.
	 */
	public function set( $key, $value, $group = 'default', $expiration = 0 ) {
		$result      = true;
		$derived_key = $this->build_key( $key, $group );

		// Save if group not excluded from redis and redis is up.
		if ( ! in_array( $group, $this->ignored_groups, true ) && $this->redis_status() ) {
			$expiration = $this->validate_expiration( $expiration );

			if ( $expiration ) {
				$result = $this->parse_redis_response( $this->redis->setex( $derived_key, $expiration, $this->maybe_serialize( $value ) ) );
			} else {
				$result = $this->parse_redis_response( $this->redis->set( $derived_key, $this->maybe_serialize( $value ) ) );
			}
		}

		// If the set was successful, or we didn't go to redis.
		if ( $result ) {
			$this->add_to_internal_cache( $derived_key, $value );
		}

		return $result;
	}

	/**
	 * Increment a Redis counter by the amount specified.
	 *
	 * @param int|string $key    The cache key to increment.
	 * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function incr( $key, $offset = 1, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );
		$offset      = (int) $offset;

		// If group is a non-Redis group, save to internal cache, not Redis.
		if ( in_array( $group, $this->ignored_groups, true ) || ! $this->redis_status() ) {
			$value  = $this->get_from_internal_cache( $derived_key, $group );
			$value += $offset;
			$this->add_to_internal_cache( $derived_key, $value );

			return $value;
		}

		// Save to Redis.
		$result = $this->parse_redis_response( $this->redis->incrBy( $derived_key, $offset ) );
		$this->add_to_internal_cache( $derived_key, (int) $this->redis->get( $derived_key ) );

		return $result;
	}

	/**
	 * Decrement a Redis counter by the amount specified.
	 *
	 * @param int|string $key    The cache key to decrement.
	 * @param int        $offset Optional. The amount by which to decrement the item's value. Default 1.
	 * @param string     $group  Optional. The group the key is in. Default 'default'.
	 * @return false|int False on failure, the item's new value on success.
	 */
	public function decr( $key, $offset = 1, $group = 'default' ) {
		$derived_key = $this->build_key( $key, $group );
		$offset      = (int) $offset;

		// If group is a non-Redis group, save to internal cache, not Redis.
		if ( in_array( $group, $this->ignored_groups, true ) || ! $this->redis_status() ) {
			$value  = $this->get_from_internal_cache( $derived_key, $group );
			$value -= $offset;
			$this->add_to_internal_cache( $derived_key, $value );

			return $value;
		}

		// Save to Redis.
		$result = $this->parse_redis_response( $this->redis->decrBy( $derived_key, $offset ) );
		$this->add_to_internal_cache( $derived_key, (int) $this->redis->get( $derived_key ) );

		return $result;
	}

	/**
	 * Builds a key for the cached object using the prefix, group and key.
	 *
	 * @param string $key   The key under which to store the value.
	 * @param string $group The group value appended to the $key.
	 *
	 * @return string
	 */
	public function build_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$salt   = defined( 'WP_REDIS_SALT' ) ? trim( WP_REDIS_SALT ) . ':' : '';
		$prefix = in_array( $group, $this->global_groups, true ) ? $this->global_prefix : $this->blog_prefix;

		$key   = str_replace( ':', '-', $key );
		$group = str_replace( ':', '-', $group );

		$prefix = trim( $prefix, '_-:$' );

		return "{$salt}{$prefix}:{$group}:{$key}";
	}

	/**
	 * Convert data types when using Redis MGET
	 *
	 * When requesting multiple keys, those not found in cache are assigned the value null upon return.
	 * Expected value in this case is false, so we convert
	 *
	 * @param  string $value Value to possibly convert.
	 * @return string Converted value
	 */
	protected function filter_redis_get_multi( $value ) {
		if ( is_null( $value ) ) {
			$value = false;
		}

		return $value;
	}

	/**
	 * Convert Redis responses into something meaningful
	 *
	 * @param  mixed $response Redis response.
	 *
	 * @return bool|int|string
	 */
	protected function parse_redis_response( $response ) {
		if ( is_bool( $response ) ) {
			return $response;
		}

		if ( is_numeric( $response ) ) {
			return $response;
		}

		if ( is_object( $response ) && method_exists( $response, 'getPayload' ) ) {
			return $response->getPayload() === 'OK';
		}

		return false;
	}

	/**
	 * Simple wrapper for saving object to the internal cache.
	 *
	 * @param string $derived_key Key to save value under.
	 * @param mixed  $value       Object value.
	 */
	public function add_to_internal_cache( $derived_key, $value ) {
		$this->cache[ $derived_key ] = $value;
	}

	/**
	 * Get a value specifically from the internal, run-time cache, not Redis.
	 *
	 * @param int|string $key   Key value.
	 * @param int|string $group Group that the value belongs to.
	 *
	 * @return bool|mixed              Value on success; false on failure.
	 */
	public function get_from_internal_cache( $key, $group ) {
		$derived_key = $this->build_key( $key, $group );

		if ( isset( $this->cache[ $derived_key ] ) ) {
			return $this->cache[ $derived_key ];
		}

		return false;
	}

	/**
	 * In multisite, switch blog prefix when switching blogs
	 *
	 * @param  int $_blog_id  Blog ID.
	 * @return bool
	 */
	public function switch_to_blog( $_blog_id ) {
		if ( ! function_exists( 'is_multisite' ) || ! is_multisite() ) {
			return false;
		}

		$this->blog_prefix = $_blog_id;
		return true;
	}

	/**
	 * Sets the list of global groups.
	 *
	 * @param array $groups List of groups that are global.
	 */
	public function add_global_groups( $groups ) {
		$groups = (array) $groups;

		if ( $this->redis_status() ) {
			$this->global_groups = array_unique( array_merge( $this->global_groups, $groups ) );
		} else {
			$this->ignored_groups = array_unique( array_merge( $this->ignored_groups, $groups ) );
		}
	}

	/**
	 * Sets the list of groups not to be cached by Redis.
	 *
	 * @param array $groups List of groups that are to be ignored.
	 */
	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;

		$this->ignored_groups = array_unique( array_merge( $this->ignored_groups, $groups ) );
	}

	/**
	 * Render data about current cache requests.
	 */
	public function stats() {
		?>
		<p>
			<strong>Redis Status:</strong> <?php echo $this->redis_status() ? 'Connected' : 'Not Connected'; ?><br />
			<strong>Redis Client:</strong> <?php echo $this->redis_client; ?><br />
			<strong>Cache Hits:</strong> <?php echo $this->cache_hits; ?><br />
			<strong>Cache Misses:</strong> <?php echo $this->cache_misses; ?>
		</p>

		<ul>
		<?php foreach ( $this->cache as $group => $cache ) : ?>
			<li>
				<?php
				printf(
					'%s - %sk', strip_tags( $group ),
					number_format( strlen( serialize( $cache ) ) / 1024, 2 )
				);
				?>
			</li>
		<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Serialize data, if needed.
	 *
	 * @param string|array|object $data Data that might be serialized.
	 *
	 * @return mixed A scalar data
	 */
	private function maybe_serialize( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return serialize( $data );
		}

		/*
		 * Double serialization is required for backward compatibility.
		 * See https://core.trac.wordpress.org/ticket/12930
		 */
		if ( $this->is_serialized( $data, false ) ) {
			return serialize( $data );
		}

		return $data;
	}

	/**
	 * Unserialize value only if it was serialized.
	 *
	 * @param string $original Maybe unserialized original, if is needed.
	 *
	 * @return mixed Unserialized data can be any type.
	 */
	public function maybe_unserialize( $original ) {
		if ( $this->is_serialized( $original ) ) { // Don't attempt to unserialize data that wasn't serialized going in.
			return @unserialize( $original );
		}

		return $original;
	}

	/**
	 * Check value to find if it was serialized.
	 *
	 * If $data is not an string, then returned value will always be false.
	 * Serialized data is always a string.
	 *
	 * @param string $data   Value to check to see if was serialized.
	 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
	 *
	 * @return bool False if not serialized and true if it was.
	 */
	private function is_serialized( $data, $strict = true ) {
		// If it isn't a string, it isn't serialized.
		if ( ! is_string( $data ) ) {
			return false;
		}

		$data = trim( $data );
		if ( 'N;' == $data ) {
			return true;
		}

		if ( strlen( $data ) < 4 ) {
			return false;
		}

		if ( ':' !== $data[1] ) {
			return false;
		}

		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			// Either ; or } must exist.
			if ( false === $semicolon && false === $brace ) {
				return false;
			}
			// But neither must be in the first X characters.
			if ( false !== $semicolon && $semicolon < 3 ) {
				return false;
			}
			if ( false !== $brace && $brace < 4 ) {
				return false;
			}
		}

		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
			// Or else fall through.
			case 'a':
			case 'O':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
		}

		return false;
	}

	/**
	 * Wrapper to validate the cache keys expiration value.
	 *
	 * @param mixed $expiration Incoming expiration value (whatever it is).
	 *
	 * @return int
	 */
	protected function validate_expiration( $expiration ) {
		return is_int( $expiration ) || ctype_digit( $expiration ) ? (int) $expiration : 0;
	}

	/**
	 * Sanitizes a string key.
	 *
	 * Keys are used as internal identifiers. Lowercase alphanumeric characters,
	 * dashes, and underscores are allowed.
	 *
	 * @param string $key  String key.
	 *
	 * @return string Sanitized key.
	 */
	public function sanitize_key( $key ) {
		$key = strtolower( $key );
		return preg_replace( '/[^a-z0-9_\-]/', '', $key );
	}

}
