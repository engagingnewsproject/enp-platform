jQuery( document ).ready( function( $ ) {
    /*
    *   Progressive Enhancement Accordion Set-up
    *   @usage Create a JS object with what the header and content is for each accordion
    *   and pass it to enp_accordion__setup(accordion);
    *   This will set all the appropriate classes for your accordion.
    *   ex:
    *   $('.enp-question-content').each(function() {
    *      var accordion = {header: $(this).prev('.enp-question-header'), content: $(this)};
    *      enp_accordion__setup(accordion);
    *   }
    */

    /* @param accordion: title, accordion content object
    *         ex: accordion = {title: 'string', content: $('.enp-question-content')};
    *  @usage call within a each loop.
    *  @return new accordion object: {header: accordion.header, content: accordion.content}
    */
    window.enp_accordion__create_headers = function(accordion) {
        var new_accordion;
        // create the HTML for the header
        accordion_header = '<button id="'+accordion.baseID+'__accordion-header" class="enp-accordion-header"><span class="enp-accordion-header__title">'+accordion.title+'</span><svg class="enp-icon enp-accordion-header__icon"><use xlink:href="#icon-chevron-down" /></svg></button>';
        // create the heading sections
        $(accordion.content).before(accordion_header);
        // set the accordion_header as the newly created object
        accordion_header = $(accordion.content).prev('.enp-accordion-header');
        // create the new accordion to return
        new_accordion = {header: accordion_header, content: accordion.content};
        // return the new accordion object
        return new_accordion;
    };

    /*
    *  enp_accordion__setup
    *  @description Sets up all the start classes for your accordions
    *  @param accordion: accordion object containing
    *                    accordion header and accordion content
    *                    var accordion = {header: $(this), content: $(this).next()};
    *  @usage call within a each loop.
    */
    window.enp_accordion__setup = function(accordion) {
        // switch out the html for a button
        // make sure we have a button, if not, replace the tag with a button tag
        var tag = $(accordion.header)[0].tagName;
        if(tag !== 'BUTTON') {
            accordion.header = enp_replaceTags(accordion.header, tag, 'button');
        }


        if(!accordion.header.hasClass('enp-accordion-header')) {
            accordion.header.addClass('enp-accordion-header');
        }
        accordion.header.addClass('enp-accordion-header--closed');
        // add the classes to our accordion content area
        accordion.content.addClass('enp-accordion-content enp-accordion-content--closed');
        accordion.content.attr('aria-hidden', true);
    };


    /* @param accordion: accordion object containing
    *                    accordion header and accordion content
    *                    var accordion = {header: $(this), content: $(this).next()};
    *  @usage call within a $(document).on('click', function(e)) {
    *                            var accordion,
    *                                accordion_state;
    *                           // create accordion object
    *                           accordion = {header: $(this), content: $(this).next()};
    *                           // set correct classnames and
    *                           //get response of the new state (open, closed);
    *                           accordion_state = enp_accordion(accordion);
    *                           if(accordion_state === 'open') {
    *                               // whatever
    *                           }
    *                       }
    */
    window.enp_accordion = function(accordion) {
        // check if the accordion is open or closed
        if(accordion.header.hasClass('enp-accordion-header--closed')) {
            // if it's closed, open it
            accordion.header.removeClass('enp-accordion-header--closed');
            accordion.content.removeClass('enp-accordion-content--closed');
            accordion.header.addClass('enp-accordion-header--open');
            accordion.content.addClass('enp-accordion-content--open');
            accordion.content.attr('aria-hidden', false);
        } else {
            // if it's open, close it
            accordion.header.removeClass('enp-accordion-header--open');
            accordion.content.removeClass('enp-accordion-content--open');
            accordion.header.addClass('enp-accordion-header--closed');
            accordion.content.addClass('enp-accordion-content--closed');
            accordion.content.attr('aria-hidden', true);
        }
    };

    /*
    *   Default click function
    *   Registers clicks on enp-accordion-headers, creates an accordion object, and passes
    *   it to enp_accordion so all the classes can get set appropriately (open/closed)
    */
    $(document).on('click', '.enp-accordion-header', function(e) {
        e.preventDefault();
        var accordion;
        // create the accordion object
        accordion = {header: $(this), content: $(this).next('.enp-accordion-content')};
        // set the correct classes
        enp_accordion(accordion);

    });

    /*
    *   enp_replaceTags
    *   @description: With progressive enhancement, sometimes we need to
    *                 change the tags on elements. This helps us do that.
    *   @usage: Pass in the object, current tag ('p'), and what you want it to be
    *           replaced with ('button')
    *   @params
    *       obj: object that will have its tag changed
    *       tag: current tag (ie: 'p')
    *       replacementTag: what tag you want it to be changed to (ie: 'button')
    */
    window.enp_replaceTags = function(obj, tag, replacementTag) {

        var outer = obj.outerHTML;
        // if it's a class it might return undefined, so try a different way
        if(outer === undefined) {
            outer = $(obj)[0].outerHTML;
            obj = $(obj);
        }
        // Replace opening tag
        var regex = new RegExp('<' + tag, 'i');
        var newTag = outer.replace(regex, '<' + replacementTag);

        // Replace closing tag
        regex = new RegExp('</' + tag, 'i');
        newTag = newTag.replace(regex, '</' + replacementTag);
        // a little hack to store our current place in the DOM
        var store_location = obj.next();
        // replace the tags.
        obj.replaceWith(newTag);
        // finish the hack. move back to the location where the new replaced element is
        replacedObj = $(store_location).prev();
        // return the new obj
        return replacedObj;
    };
});
