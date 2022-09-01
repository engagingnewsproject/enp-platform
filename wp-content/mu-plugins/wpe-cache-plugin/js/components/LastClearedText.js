import DateTime from '../utils/DateTime';
import JQTextElement from './JQTextElement';

/**
 * Represents the last cleared text element
 */
class LastClearedText extends JQTextElement {
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
            super.show();
            this.setText(`Last cleared: ${lastClearedAt}`);
        }
    }
}

export default LastClearedText;
