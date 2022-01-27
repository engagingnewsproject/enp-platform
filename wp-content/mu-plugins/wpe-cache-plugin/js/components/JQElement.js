/**
 * Represents a JQuery Element in the DOM
 */
class JQElement {
    constructor(element) {
        this.element = element;
    }
    setText(text) {
        if (this.element?.text() !== text) {
            this.element.text(text);
        }
    }
}

export default JQElement;
