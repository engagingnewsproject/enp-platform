/**
 * Scanner class.
 *
 * @since 2.7.0
 */
class Scanner {
	constructor( totalSteps, currentStep ) {
		this.totalSteps = parseInt( totalSteps );
		this.currentStep = parseInt( currentStep );
		this.cancelling = false;
	}

	/**
	 * Start the scan.
	 */
	start() {
		this.updateProgressBar( this.getProgress() );

		const remainingSteps = this.totalSteps - this.currentStep;

		if ( this.currentStep !== 0 ) {
			// Scan already started.
			this.step( remainingSteps );
		} else {
			this.onStart().then( () => {
				this.step( remainingSteps );
			} );
		}
	}

	/**
	 * Cancel scan.
	 */
	cancel() {
		this.cancelling = true;
		this.updateProgressBar( 0, true );
	}

	/**
	 * Get scan progress.
	 *
	 * @return {number}  Progress 0-99.
	 */
	getProgress() {
		if ( this.cancelling ) {
			return 0;
		}
		const remainingSteps = this.totalSteps - this.currentStep;
		return Math.min(
			Math.round(
				( parseInt( this.totalSteps - remainingSteps ) * 100 ) /
					this.totalSteps
			),
			99
		);
	}

	/**
	 * Update progress bar.
	 *
	 * @param {number}  progress  Progress percentage.
	 * @param {boolean} cancel    Cancel the scan.
	 */
	updateProgressBar( progress, cancel = false ) {
		if ( progress > 100 ) {
			progress = 100;
		}

		// Update progress bar.
		document.querySelector(
			'.sui-progress-block .sui-progress-text span'
		).innerHTML = progress + '%';

		document.querySelector(
			'.sui-progress-block .sui-progress-bar span'
		).style.width = progress + '%';

		if ( progress >= 90 ) {
			document.querySelector(
				'.sui-progress-state .sui-progress-state-text'
			).innerHTML = 'Finalizing...';
		}

		if ( cancel ) {
			document.querySelector(
				'.sui-progress-state .sui-progress-state-text'
			).innerHTML = 'Cancelling...';
		}
	}

	/**
	 * Execute a scan step.
	 *
	 * @param {number} remainingSteps
	 */
	step( remainingSteps ) {
		if ( remainingSteps >= 0 ) {
			this.currentStep = this.totalSteps - remainingSteps;
		}
	}

	/**
	 * On start function.
	 */
	onStart() {
		throw new Error( 'onStart() must be implemented in child class' );
	}

	/**
	 * On finish function.
	 */
	onFinish() {
		this.updateProgressBar( 100 );
	}
}

export default Scanner;
