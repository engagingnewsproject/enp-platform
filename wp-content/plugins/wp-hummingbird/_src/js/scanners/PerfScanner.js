import Scanner from '../utils/scanner';
import Fetcher from '../utils/fetcher';
import { getString, getLink } from '../utils/helpers';

class PerfScanner extends Scanner {
	/**
	 * Execute a scan step recursively.
	 *
	 * @param {number} remainingSteps
	 */
	step( remainingSteps ) {
		super.step( remainingSteps );

		this.currentStep++;

		// Update progress bar.
		this.updateProgressBar( this.getProgress() );

		Fetcher.common
			.call( 'wphb_performance_run_test' )
			.then( ( response ) => {
				if ( ! response.finished ) {
					// Try again 3 seconds later
					window.setTimeout( () => {
						this.step( this.totalSteps - this.currentStep );
					}, 3000 );
				} else {
					this.onFinish( response );
				}
			} );
	}

	updateProgressBar( progress, cancel = false ) {
		// Test has been initialized.
		if ( 0 === progress ) {
			this.currentStep = 2;

			this.timer = window.setInterval( () => {
				this.currentStep += 1;
				this.updateProgressBar( this.getProgress() );
			}, 100 );
		}

		const progressStatus = document.querySelector(
			'.wphb-performance-scan-modal .sui-progress-state .sui-progress-state-text'
		);

		if ( 3 === progress ) {
			progressStatus.innerHTML = getString( 'scanRunning' );
		}

		if ( 73 === progress ) {
			clearInterval( this.timer );
			this.timer = false;

			this.timer = window.setInterval( () => {
				this.currentStep += 1;
				this.updateProgressBar( this.getProgress() );
			}, 1000 );

			progressStatus.innerHTML = getString( 'scanAnalyzing' );
		}

		if ( 99 === progress ) {
			progressStatus.innerHTML = getString( 'scanWaiting' );
			clearInterval( this.timer );
			this.timer = false;
		}

		document.querySelector(
			'.wphb-performance-scan-modal .sui-progress-block .sui-progress-text span'
		).innerHTML = progress + '%';

		document.querySelector(
			'.wphb-performance-scan-modal .sui-progress-block .sui-progress-bar span'
		).style.width = progress + '%';

		if ( 100 === progress ) {
			const loader = document.querySelector(
				'.wphb-performance-scan-modal .sui-progress-block span.sui-icon-loader'
			);
			loader.classList.remove( 'sui-icon-loader', 'sui-loading' );
			loader.classList.add( 'sui-icon-check' );

			progressStatus.innerHTML = getString( 'scanComplete' );
			clearInterval( this.timer );
			this.timer = false;
		}
	}

	onStart() {
		return Promise.resolve();
	}

	onFinish( response ) {
		super.onFinish();

		window.wphbMixPanel.track( 'plugin_scan_finished', {
			score_mobile: response.mobileScore,
			score_desktop: response.desktopScore,
		} );

		// Give a second for the report to be saved to the db.
		window.setTimeout( function() {
			window.location = getLink( 'audits' );
		}, 2000 );
	}
}

export default PerfScanner;
