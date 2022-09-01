import JQElement from './JQElement';

/**
 * Represents the clear all caches button
 */
class ClearAllCacheBtn extends JQElement {
    constructor(apiService, element = jQuery('#wpe-clear-all-cache-btn')) {
        super(element);
        this.apiService = apiService;
    }
    setDisabled(reason = 'Clear all caches button disabled for 5 minutes') {
        if (this.element.length) {
            this.element.attr('aria-disabled', true);
            this.element.attr('aria-describedby', reason);
            this.element.attr('disabled', true);
        }
    }

    attachSubmit({ onSuccess, onError, maxCDNEnabled }) {
        this.element.on('click', () => {
            if (maxCDNEnabled) {
                this.setDisabled();
            }
            this.apiService.clearAllCaches().then(onSuccess).catch(onError);
        });
    }
}

export default ClearAllCacheBtn;
