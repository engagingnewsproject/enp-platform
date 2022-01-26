import JQElement from './JQElement';

/**
 * Represents the hidden _wp_http_referer field in the cache times form
 */
class CacheTimesFormReferField extends JQElement {
    constructor(element = jQuery('input[name="_wp_http_referer"]')) {
        super(element);
    }

    replaceRefer(url) {
        this.element.val(url);
    }
}

export default CacheTimesFormReferField;
