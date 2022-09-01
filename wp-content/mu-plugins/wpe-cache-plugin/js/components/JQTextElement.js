import JQElement from './JQElement';

class JQTextElement extends JQElement {
    constructor(element) {
        super(element);
    }

    show() {
        if (this.element.length) {
            this.element.attr('style', 'display: block;');
        }
    }

    hide() {
        if (this.element.length) {
            this.element.attr('style', 'display: none;');
        }
    }
}

export default JQTextElement;
