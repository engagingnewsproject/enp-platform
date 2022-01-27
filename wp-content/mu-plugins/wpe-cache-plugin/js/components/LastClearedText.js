import DateTime from '../utils/DateTime';
import JQElement from './JQElement';

/**
 * Represents the last cleared text element
 */
class LastClearedText extends JQElement {
    constructor(element = jQuery('#wpe-last-cleared-text')) {
        super(element);
    }
    setLastClearedText(date) {
        if (this.element.length) {
            let lastClearedAt;
            try {
                lastClearedAt = DateTime.formatDate(new Date(date));
            } catch {
                lastClearedAt = DateTime.formatDate(new Date(Date.now()));
            }
            this.setText(`Last cleared: ${lastClearedAt}`);
        }
    }
}

export default LastClearedText;
