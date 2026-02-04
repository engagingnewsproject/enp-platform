<?php
/**
 * Cache Helper for Ninja Forms Abilities API
 *
 * Provides centralized cache management for consistent cache invalidation
 * across all abilities operations.
 *
 * @package NinjaForms
 * @subpackage Abilities
 * @since 3.13.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NF_Abilities_Cache
 *
 * Centralizes cache management for abilities API
 */
class NF_Abilities_Cache {

	/**
	 * Invalidate all form-related caches
	 *
	 * @param int $form_id Form ID
	 * @return void
	 */
	public static function invalidate_form_cache( $form_id ) {
		$form_id = (int) $form_id;
		
		if ( $form_id <= 0 ) {
			return;
		}
		
		// Clear Ninja Forms internal cache
		if ( class_exists( 'WPN_Helper' ) ) {
			WPN_Helper::delete_nf_cache( $form_id );
		}
		
		// Clear WordPress object cache
		wp_cache_delete( "nf_form_{$form_id}", 'ninja_forms' );
		wp_cache_delete( "nf_form_fields_{$form_id}", 'ninja_forms' );
		wp_cache_delete( "nf_form_actions_{$form_id}", 'ninja_forms' );
		wp_cache_delete( "nf_form_settings_{$form_id}", 'ninja_forms' );
		
		// Clear transients
		delete_transient( "nf_form_data_{$form_id}" );
		delete_transient( "nf_form_count_{$form_id}" );
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', $form_id, 'form' );
	}

	/**
	 * Invalidate field-related caches
	 *
	 * @param int $field_id Field ID
	 * @param int $form_id Form ID (optional)
	 * @return void
	 */
	public static function invalidate_field_cache( $field_id, $form_id = null ) {
		$field_id = (int) $field_id;
		
		if ( $field_id <= 0 ) {
			return;
		}
		
		// Clear field-specific cache
		wp_cache_delete( "nf_field_{$field_id}", 'ninja_forms' );
		wp_cache_delete( "nf_field_meta_{$field_id}", 'ninja_forms' );
		
		// Clear form cache if form ID provided
		if ( $form_id ) {
			self::invalidate_form_cache( $form_id );
		}
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', $field_id, 'field' );
	}

	/**
	 * Invalidate submission-related caches
	 *
	 * @param int $submission_id Submission ID
	 * @param int $form_id Form ID (optional)
	 * @return void
	 */
	public static function invalidate_submission_cache( $submission_id, $form_id = null ) {
		$submission_id = (int) $submission_id;
		
		if ( $submission_id <= 0 ) {
			return;
		}
		
		// Clear submission-specific cache
		wp_cache_delete( "nf_submission_{$submission_id}", 'ninja_forms' );
		wp_cache_delete( "nf_submission_data_{$submission_id}", 'ninja_forms' );
		
		// Clear form submission count cache
		if ( $form_id ) {
			wp_cache_delete( "nf_form_submission_count_{$form_id}", 'ninja_forms' );
			delete_transient( "nf_submissions_count_{$form_id}" );
		}
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', $submission_id, 'submission' );
	}

	/**
	 * Invalidate action-related caches
	 *
	 * @param int $action_id Action ID
	 * @param int $form_id Form ID (optional)
	 * @return void
	 */
	public static function invalidate_action_cache( $action_id, $form_id = null ) {
		$action_id = (int) $action_id;
		
		if ( $action_id <= 0 ) {
			return;
		}
		
		// Clear action-specific cache
		wp_cache_delete( "nf_action_{$action_id}", 'ninja_forms' );
		wp_cache_delete( "nf_action_meta_{$action_id}", 'ninja_forms' );
		
		// Clear form cache if form ID provided
		if ( $form_id ) {
			self::invalidate_form_cache( $form_id );
		}
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', $action_id, 'action' );
	}

	/**
	 * Invalidate calculation-related caches
	 *
	 * @param string $calc_name Calculation name
	 * @param int $form_id Form ID
	 * @return void
	 */
	public static function invalidate_calculation_cache( $calc_name, $form_id ) {
		$form_id = (int) $form_id;
		
		if ( $form_id <= 0 || empty( $calc_name ) ) {
			return;
		}
		
		$calc_name = sanitize_key( $calc_name );
		
		// Clear calculation-specific cache
		wp_cache_delete( "nf_calculation_{$form_id}_{$calc_name}", 'ninja_forms' );
		
		// Clear form cache
		self::invalidate_form_cache( $form_id );
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', $calc_name, 'calculation' );
	}

	/**
	 * Invalidate all abilities-related caches
	 *
	 * Use with caution - clears all form-related data
	 *
	 * @return void
	 */
	public static function invalidate_all_cache() {
		// Clear all Ninja Forms caches
		if ( class_exists( 'WPN_Helper' ) ) {
			WPN_Helper::delete_nf_cache( 'all' );
		}
		
		// Clear object cache group
		wp_cache_flush_group( 'ninja_forms' );
		
		// Clear all NF transients
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nf_%' OR option_name LIKE '_transient_timeout_nf_%'" );
		
		// Fire action for third-party cache clearing
		do_action( 'ninja_forms_abilities_cache_invalidated', 'all', 'global' );
	}

	/**
	 * Warm up form cache after operations
	 *
	 * @param int $form_id Form ID
	 * @return void
	 */
	public static function warm_form_cache( $form_id ) {
		$form_id = (int) $form_id;
		
		if ( $form_id <= 0 ) {
			return;
		}
		
		// Pre-load form data
		$form = Ninja_Forms()->form( $form_id )->get();
		if ( $form && $form->get_id() ) {
			// Pre-load fields and actions
			$form->get_fields();
			$form->get_actions();
		}
		
		// Fire action for third-party cache warming
		do_action( 'ninja_forms_abilities_cache_warmed', $form_id );
	}

	/**
	 * Get cache key for abilities data
	 *
	 * @param string $type Cache type
	 * @param mixed $id Resource ID
	 * @return string
	 */
	public static function get_cache_key( $type, $id ) {
		$type = sanitize_key( $type );
		$id = is_numeric( $id ) ? (int) $id : sanitize_key( $id );
		
		return "nf_abilities_{$type}_{$id}";
	}

	/**
	 * Set cache with standardized expiration
	 *
	 * @param string $key Cache key
	 * @param mixed $data Data to cache
	 * @param int $expiration Expiration in seconds (default 1 hour)
	 * @return bool
	 */
	public static function set( $key, $data, $expiration = HOUR_IN_SECONDS ) {
		return wp_cache_set( $key, $data, 'ninja_forms', $expiration );
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param mixed $default Default value if not found
	 * @return mixed
	 */
	public static function get( $key, $default = false ) {
		return wp_cache_get( $key, 'ninja_forms' ) ?: $default;
	}

	/**
	 * Delete specific cache key
	 *
	 * @param string $key Cache key
	 * @return bool
	 */
	public static function delete( $key ) {
		return wp_cache_delete( $key, 'ninja_forms' );
	}
}