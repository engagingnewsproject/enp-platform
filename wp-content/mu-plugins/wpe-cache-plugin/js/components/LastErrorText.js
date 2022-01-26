import DateTime from '../utils/DateTime';
import JQElement from './JQElement';

class LastErrorText extends JQElement {
    constructor(element = jQuery('#wpe-last-cleared-error-text')) {
        super(element);
    }
    setLastErrorText(date) {
        if (this.element.length) {
            let lastErrorAt;
            try {
                lastErrorAt = DateTime.formatDate(new Date(date));
            } catch {
                lastErrorAt = DateTime.formatDate(new Date(Date.now()));
            }
            this.setText(`Error clearing all cache: ${lastErrorAt}`);
        }
    }
}

export default LastErrorText;
