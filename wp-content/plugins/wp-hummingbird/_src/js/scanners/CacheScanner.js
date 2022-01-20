import Scanner from '../utils/scanner';
import Fetcher from '../utils/fetcher';

// Number of sites to clear out per request.
const BATCH_SIZE = 20;

/**
 * CacheScanner class responsible for clearing out network cache on subsites.
 *
 * @since 2.7.0
 */
class CacheScanner extends Scanner {
	/**
	 * Run step.
	 *
	 * @since 2.7.0
	 *
	 * @param {number} remainingSteps
	 */
	step( remainingSteps ) {
		super.step( remainingSteps );

		this.currentStep++;

		if ( remainingSteps > 0 ) {
			const offset = ( this.totalSteps - remainingSteps ) * BATCH_SIZE;

			Fetcher.caching.clearCacheBatch( BATCH_SIZE, offset ).then( () => {
				this.updateProgressBar( this.getProgress() );
				this.step( this.totalSteps - this.currentStep );
			} );
		} else {
			this.onFinish();
		}
	}

	/**
	 * Set the total number of steps based on the number of subsites and BATCH_SIZE.
	 *
	 * @since 2.7.0
	 */
	onStart() {
		return Fetcher.common
			.call( 'wphb_get_network_sites' )
			.then( ( response ) => {
				this.totalSteps = Math.ceil(
					parseInt( response ) / BATCH_SIZE
				);
			} );
	}

	/**
	 * Finish up.
	 *
	 * @since 2.7.0
	 */
	onFinish() {
		super.onFinish();
		location.reload();
	}
}

export default CacheScanner;
