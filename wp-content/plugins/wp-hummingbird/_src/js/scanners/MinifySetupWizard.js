import MinifyScanner from './MinifyScanner';

class MinifySetupWizard extends MinifyScanner {
	/**
	 * Update progress bar.
	 *
	 * @param {number}  progress Progress percentage.
	 * @param {boolean} cancel   Cancel the scan.
	 */
	updateProgressBar( progress, cancel = false ) {
		if ( progress > 100 ) {
			progress = 100;
		}

		// Update progress bar.
		document.querySelector(
			'.sui-progress .sui-progress-text'
		).innerHTML = progress + '%';

		document.querySelector(
			'.sui-progress .sui-progress-bar span'
		).style.width = progress + '%';

		if ( progress >= 90 ) {
			document.querySelector(
				'.wphb-progress-wrapper .sui-progress-state > span'
			).innerHTML = 'Finalizing...';
		}

		if ( cancel ) {
			document.querySelector(
				'.wphb-progress-wrapper .sui-progress-state > span'
			).innerHTML = 'Cancelling...';
		}
	}

	onFinish() {
		window.wphbSetupNextStep();
	}
}

export default MinifySetupWizard;
