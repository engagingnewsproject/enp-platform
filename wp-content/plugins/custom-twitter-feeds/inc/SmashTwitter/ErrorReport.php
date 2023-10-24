<?php
/**
 * Class ErrorReport
 *
 * Tracks errors and creates messages.
 *
 * @since 2.2.2
 */
namespace TwitterFeed\SmashTwitter;

class ErrorReport {
	private $critical_error;

	private $errors;

	private $log;

	public function __construct() {
		$this->critical_error = array();
		$this->errors = array();

		$ctf_statuses_option = get_option( 'ctf_statuses', array() );
		$this->log = array();
		if ( ! empty( $ctf_statuses_option['smash_twitter']['error_log'] ) ) {
			$this->log = $ctf_statuses_option['smash_twitter']['error_log'];
		}
	}

	public function log( $message ) {
		if ( ! is_array( $this->log ) ) {
			$this->log = array();
		}

		if ( count( $this->log ) > 10 ) {
			reset( $this->log );
			unset( $this->log[ key( $this->log ) ] );
		}

		$this->log[] = date( 'Y-m-d H:i:s' ) . ' - ' . $message;

		$this->save_log();
	}

	public function save_log() {
		$ctf_statuses_option = get_option( 'ctf_statuses', array() );

		$ctf_statuses_option['smash_twitter']['error_log'] = $this->log;

		update_option( 'ctf_statuses', $ctf_statuses_option, false );
	}

	public function get_log() {
		return $this->log;
	}

	public function maybe_add_critical_error( $error ) {
		if ( empty( $this->critical_error ) ) {
			$this->critical_error = $error;
			return true;
		}

		return false;
	}

	public function add_error( $error ) {
		$this->errors[] = $error;
	}

	public function get_errors() {
		return $this->errors;
	}

	public function get_critical_error() {
		return $this->critical_error;
	}

	public function process_error( $error, $add_to_log = false ) {
		$settings_page_url = admin_url( 'admin.php?page=ctf-settings' );
		$feed_edit_url = admin_url( 'admin.php?page=ctf-feed-builder' );
		$added = false;
		if ( is_wp_error( $error ) ) {

			if ( isset( $error->errors ) ) {
				$error_message = '';
				foreach ( $error->errors as $key => $item ) {
					$error_message .= $key . ': ' . $item[0] . ' ';
				}

				$error_array = array(
					'message' => 'HTTP request error - ' . $error_message,
					'directions' => sprintf(  __( 'Troubleshoot using %sthis doc%s on our website.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/twitter-api-error-message-reference/?twitter&utm_source=twitter-free&utm_medium=error-notice&utm_campaign=smash-twitter&utm_content=ThisDoc" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );

				if ( $added && $add_to_log ) {
					$this->log( $error_array['message'] );
				}
			}
			return;
		}

		if ( is_array( $error ) && isset( $error['code'] ) ) {

			if ( $error['code'] === 429 ) {
				$error_array = array(
					'message' => sprintf( __( 'It looks like you\'ve exceeded your daily feed update limit for this site as of %s. You will not see new tweets in your feed until the limit resets.', 'custom-twitter-feeds' ), date( 'Y-m-d H:i:s' ) ),
					'directions' => __( 'This limit will automatically reset within the next week. No action required.', 'custom-twitter-feeds'   ) . ' ' . sprintf( __( 'For more information on limitations due to the Twitter API changes effective April 2023, %svisit this page%s.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/smash-balloon-twitter-changes/?twitter&utm_source=twitter-free&utm_medium=error-notice&utm_campaign=smash-twitter&utm_content=ThisPage" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error['code'] === 401 ) {
				$error_array = array(
					'message' => __( 'Unable to complete update of your feeds. It seems your site key is not authorized to make requests to the Twitter API', 'custom-twitter-feeds' ),
					'directions' => sprintf(  __( 'Try going to the %ssettings page%s to refresh your site key.', 'custom-twitter-feeds' ), '<a href="'.esc_url( $settings_page_url ).'" target="_blank" rel="noopener">','</a>' )
					);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error['code'] === 400 ) {
				$error_array = array(
					'message' => __( 'There may be an issue with your credentials for using the Smash Twitter API', 'custom-twitter-feeds' ),
					'directions' => sprintf(  __( 'Try going to the %ssettings page%s to refresh your site key.', 'custom-twitter-feeds' ), '<a href="'.esc_url( $settings_page_url ).'" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} else {
				$error_data = json_decode( $error['message'], true );
				$message = '';
				if ( $error_data ) {
					$message = $error_data['message'];
				}
				$error_array = array(
					'message' => sprintf( __( 'Error %s: %s.', 'custom-twitter-feeds' ), $error['code'], $message ),
					'directions' => sprintf(  __( 'Troubleshoot using %sthis doc%s on our website.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/twitter-api-error-message-reference/?twitter&utm_source=twitter-free&utm_medium=error-notice&utm_campaign=smash-twitter&utm_content=ThisDoc" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			}

			if ( $add_to_log && ! empty( $error_array ) ) {
				$added = $this->log( $error_array['message'] );
			}

		} elseif ( is_string( $error ) ) {
			if ( $error === 'could_not_authenticate' ) {
				$error_array = array(
					'message' => __( 'Your site is unable to connect to smashballoon.com and start updating feeds.', 'custom-twitter-feeds' ),
					'directions' => sprintf(  __( 'Try going to the %ssettings page%s to refresh your site key.', 'custom-twitter-feeds' ), '<a href="'.esc_url( $settings_page_url ).'" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error === 'no_tweets_found' ) {
				$error_array = array(
					'message' => __( 'No tweets found for your selected feed sources.', 'custom-twitter-feeds' ),
					'directions' => __( 'Make sure there are tweets available that fit your settings.', 'custom-twitter-feeds' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error === 'no_tweets_found_cache' ) {
				$error_array = array(
					'message' => __( 'No tweets found for your selected feed sources.', 'custom-twitter-feeds' ),
					'directions' => __( 'Make sure there are tweets available on Twitter that fit your settings and then clear your cache using the button found on the Settings page. It\'s also possible that you\'re encountering a limitation of our new system for updating feeds.', 'custom-twitter-feeds' ). ' ' . sprintf( __( 'For more information on limitations due to the Twitter API changes effective April 2023, %svisit this page%s.', 'custom-twitter-feeds' ), '<a href="https://smashballoon.com/doc/smash-balloon-twitter-changes-free-version/?twitter&utm_source=twitter-free&utm_medium=error-notice&utm_campaign=smash-twitter&utm_content=ThisPage" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error === 'too_many_requests' ) {
				$error_array = array(
					'message' => __( 'Too many requests', 'custom-twitter-feeds' ),
					'directions' => __( 'There were too many requests coming for the API within the certain periodof time.', 'custom-twitter-feeds' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			} elseif ( $error === 'too_much_filtering' ) {
				$error_array = array(
					'message' => __( 'It looks there were no tweets found that fit your feed moderation settings.', 'custom-twitter-feeds' ),
					'directions' => sprintf(  __( 'Try %sediting your feed settings%s to include more tweets or wait until new tweets that fit your filters are retrieved.', 'custom-twitter-feeds' ), '<a href="'.esc_url( $feed_edit_url ).'" target="_blank" rel="noopener">','</a>' )
				);
				$added = $this->maybe_add_critical_error( $error_array );
			}

			if ( $added && $add_to_log && ! empty( $error_array ) ) {
				$this->log( $error_array['message'] );
			}
		}
	}
}