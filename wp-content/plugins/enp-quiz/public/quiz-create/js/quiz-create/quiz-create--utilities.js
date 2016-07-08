/*
* Create utility functions for use across quiz-create.js
*/

function getQuestionIndex(questionID) {
    $('.enp-question-content').each(function(i) {
        if(parseInt($('.enp-question-id', this).val()) === parseInt(questionID)) {
            // we found it!
            questionIndex = i;
            // breaks out of the each loop
            return false;
        }
    });
    // return the found index
    return questionIndex;
}

// find the newly inserted mc_option_id
function getNewMCOption(questionID, question) {
    for (var prop in question) {
        // loop through the questions and get the one we want
        // then get the id of the newly inserted mc_option
        if(parseInt(question[prop].question_id) === parseInt(questionID)) {
            // now loop the mc options
            for(var mc_option_prop in question[prop].mc_option) {
                if(question[prop].mc_option[mc_option_prop].action === 'insert') {
                    // here's our new mc option ID!
                    return question[prop].mc_option[mc_option_prop];
                }

            }
        }
    }
    return false;
}

function checkQuestionSaveStatus(questionID, question) {
    // loop through questions
    for (var prop in question) {
        // check if this question equals question_id that was trying to be deleted
        if(parseInt(question[prop].question_id) === parseInt(questionID)) {
            // found it! return the question JSON
            return question[prop];
        }
    }

    return false;
}

function checkMCOptionSaveStatus(mcOptionID, question) {
    // loop through questions
    for (var prop in question) {
        // check if this question equals question_id that was trying to be deleted
        for (var mc_option_prop in question[prop].mc_option) {
            if(parseInt(question[prop].mc_option[mc_option_prop].mc_option_id) === parseInt(mcOptionID)) {
                // found it! return the mc_option
                return question[prop].mc_option[mc_option_prop];
            }
        }
    }

    return false;
}

// Search for the question that was inserted in the json response
function getNewQuestion(question) {
    for (var prop in question) {
        if(question[prop].action === 'insert') {
            // this is our new question, because it was inserted and not updated
            return question[prop];
        }
    }
    return false;
}

// Add a loading animation
function waitSpinner(waitClass) {
    return '<div class="spinner '+waitClass+'"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';
}

/** set-up accordions for questions
* @param obj: $('#jqueryObj') of the question you want to turn into an accordion
*/
function setUpAccordion(obj) {
    var accordion,
        question_title,
        question_content;
    // get the value for the title
    question_title = $('.enp-question-title__textarea', obj).val();
    // if it's empty, set it as an empty string
    if(question_title === undefined || question_title === '') {
        question_title = 'Question';
    }
    // set-up question_content var
    question_content = obj;
    // create the title and content accordion object so our headings can get created
    accordion = {title: question_title, content: question_content, baseID: obj.attr('id')};
    //returns an accordion object with the header object and content object
    accordion = enp_accordion__create_headers(accordion);
    // set-up all the accordion classes and start classes (so they're closed by default)
    enp_accordion__setup(accordion);
}

/**
* Replace all attributes with regex replace/string of an element
* and its children
*
* @param el: DOM element
* @param pattern: regex pattern for matching with replace();
* @param replace: string if pattern matches, what you want
*        the pattern to be replaced with
*/
function findReplaceDomAttributes(el, pattern, replace) {
    // replace on the passed dom attributes
    replaceAttributes(el, pattern, replace);
    // see if it has children
    if(el.children) {
        // loop the children
        // This function will also replace the attributes
        loopChildren(el.children, pattern, replace);
    }
}

/**
* Loop through the children of an element, replace it's attributes,
* and search for more children to loop
*
* @param nodes: el.children
* @param pattern: regex pattern for matching with replace();
* @param replace: string if pattern matches, what you want
*        the pattern to be replaced with
*/
function loopChildren(children, pattern, replace)
{
    var el;
    for(var i=0;i<children.length;i++)
    {
        el = children[i];
        // replace teh attributes on this element
        replaceAttributes(el, pattern, replace);

        if(el.children){
            loopChildren(el.children, pattern, replace);
        }

    }
}

/**
* replace all attributes on an element with regex replace()
* @param el: DOM element
* @param pattern: regex pattern for matching with replace();
* @param replace: string if pattern matches, what you want
*        the pattern to be replaced with
*/
function replaceAttributes(el, pattern, replace) {
    for (var att, i = 0, atts = el.attributes, n = atts.length; i < n; i++){
        att = atts[i];
        newAttrVal = att.nodeValue.replace(pattern, replace);

        // if the new val and the old val match, then nothing was replaced,
        // so we can skip it
        if(newAttrVal !== att.nodeValue) {

            if(att.nodeName === 'value') {
                // I heard value was trickier to track and update cross-browser,
                // so use jQuery til further notice...
                $(el).val(newAttrVal);
            } else {
                el.setAttribute(att.nodeName, newAttrVal);
            }
        }
    }
}

_.middleNumber = function(a, b) {
    return (a + b)/2;
};
