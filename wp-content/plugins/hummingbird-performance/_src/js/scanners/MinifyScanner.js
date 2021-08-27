import Scanner from '../utils/scanner';
import Fetcher from '../utils/fetcher';
import { getLink } from '../utils/helpers';

class MinifyScanner extends Scanner {
	/**
	 * Execute a scan step recursively.
	 *
	 * @param {number} remainingSteps
	 */
	step( remainingSteps ) {
		super.step( remainingSteps );

		if ( remainingSteps >= 0 ) {
			Fetcher.minification.checkStep( this.currentStep ).then( () => {
				remainingSteps = remainingSteps - 1;
				this.updateProgressBar( this.getProgress() );
				this.step( remainingSteps );
			} );
		} else {
			Fetcher.minification.finishCheck().then( ( response ) => {
				this.onFinish( response );
			} );
		}
	}

	cancel() {
		super.cancel();
		Fetcher.minification.cancelScan().then( () => {
			window.location.href = getLink( 'minification' );
		} );
	}

	onStart() {
		return Fetcher.minification.startCheck();
	}

	onFinish( response ) {
		super.onFinish();

		if ( 'undefined' !== typeof response.assets_msg ) {
			document.getElementById( 'assetsFound' ).innerHTML =
				response.assets_msg;
		}

		window.SUI.closeModal();
		window.SUI.openModal( 'wphb-assets-modal', 'wpbody-content' );
	}
}

export default MinifyScanner;
