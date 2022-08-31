import DateTime from '../utils/DateTime';
import JQTextElement from './JQTextElement';

class LastErrorText extends JQTextElement {
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
            super.show();
            this.setText(`Error clearing all cache: ${lastErrorAt}`);
        }
    }
}

export default LastErrorText;
