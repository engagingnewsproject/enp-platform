import Scanner from '../utils/scanner';
import Fetcher from '../utils/fetcher';
import { getString } from '../utils/helpers';

export const BATCH_SIZE = 3000;

/**
 * OrphanedScanner class responsible for clearing out orphaned Asset Optimization data.
 *
 * @since 2.7.0
 */
export class OrphanedScanner extends Scanner {
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
			Fetcher.advanced
				.clearOrphanedBatch( BATCH_SIZE )
				.then( ( response ) => {
					let timeout = 1000;
					if (
						'undefined' !== typeof response.highCPU &&
						response.highCPU
					) {
						timeout = 10000;
						document
							.getElementById( 'site-health-orphanned-speed' )
							.classList.remove( 'sui-hidden' );
					} else {
						document
							.getElementById( 'site-health-orphanned-speed' )
							.classList.add( 'sui-hidden' );
					}

					// SQL operations can be CPU sensitive, so let's give the server some time to breathe.
					window.setTimeout( () => {
						this.updateProgressBar( this.getProgress() );
						this.step( this.totalSteps - this.currentStep );
					}, timeout );
				} );
		} else {
			this.onFinish();
		}
	}

	/**
	 * Initialize scanner.
	 *
	 * @since 2.7.0
	 */
	onStart() {
		document
			.getElementById( 'site-health-orphaned-progress' )
			.classList.remove( 'sui-hidden' );

		document
			.getElementById( 'site-health-orphaned-clear' )
			.classList.add( 'sui-button-onload' );

		return Promise.resolve();
	}

	/**
	 * Finish up.
	 *
	 * @since 2.7.0
	 */
	onFinish() {
		super.onFinish();

		window.SUI.closeModal();
		document.getElementById( 'count-ao-orphaned' ).innerHTML = '0';
		window.WPHB_Admin.notices.show( getString( 'successAoOrphanedPurge' ) );
	}
}
