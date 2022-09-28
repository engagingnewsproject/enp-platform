import JQElement from './JQElement';

class ErrorToast extends JQElement {
    constructor(element = jQuery('#wpe-cache-error-toast')) {
        super(element);
    }
    showToast() {
        if (this.element.length) {
            this.element.attr('style', 'display: block');
        }
    }

    hideToast() {
        if (this.element.length) {
            this.element.attr('style', 'display: none');
        }
    }
}

export default ErrorToast;
