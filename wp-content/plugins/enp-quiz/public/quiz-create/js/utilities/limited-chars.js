/**
* Set-up Twitter Character count limiters on fields.
* Add .limited-chars class to input/textarea you're limited
*/
// wait for window to load
function limitedChars(input) {
    var inputParent,
        inputSibling,
        counter,
        counterContainer,
        counterContainerContent,
        charsLeft,
        i;

    // add our class to it
    input.classList.add('limited-chars');
    // increase the maxlength so they can type over the amount
    input.setAttribute('maxlength', 255);

    // create a new element for our character count wrapper
    counterContainer = document.createElement('span');
    counterContainer.classList.add('limited-chars__container');
    // Announce changes to the user
    counterContainer.setAttribute('aria-live', 'polite');
    counterContainer.setAttribute('aria-role', 'log');
    // Read the entire element even when only a small piece changes
    // ie - Announce "7 Characters Left" instead of just "7".
    counterContainer.setAttribute('aria-atomic', 'true');

    // create a new element for our character count
    counter = document.createElement('span');


    // insert the counter into the counterContainer
    counterContainer.appendChild(counter);

    // add the current length as the text value
    counterContainerContent = document.createTextNode(' Characters Left');
    // add the counter content to the counter element
    counterContainer.appendChild(counterContainerContent);

    // add classes
    counter.classList.add('limited-chars__counter');
    // add it to the dom after the input
    inputParent = input.parentNode; // get parent
    inputSibling = input.nextSibling; // get sibling
    // insertAfter
    inputParent.insertBefore(counterContainer, inputSibling);

    //get the current charsLeft
    charsLeft = limitedChars__charsLeft(input, true);

    // set the initial states
    limitedChars__updateLength(counter, charsLeft);

    // add event listener to the textarea
    input.addEventListener("input", limitedChars__eventHandler);
}

var limitedChars__eventHandler =  function(e) {
    var input,
        counter,
        counterContainer,
        charsLeft;

    input = e.target;
    counterContainer = input.nextElementSibling;
    counter = counterContainer.getElementsByClassName('limited-chars__counter')[0];

    // calculate charsLeft
    charsLeft = limitedChars__charsLeft(input, true);
    // update the character count display
    limitedChars__updateLength(counter, charsLeft);
    // set classes and check save buttons, etc
    limitedChars__setStates(input, counterContainer, charsLeft);
};

// get the current length of the input
var limitedChars__charsLeft = function(input, checkMustache) {
    var maxLength,
        charsLeft,
        mustache,
        mustacheMatches;

    maxLength = 117;
    charsLeft = maxLength - input.value.length;

    // see if we should allow mustache template variables and what they're worth
    if(checkMustache === true) {
        mustache = '{{score_percentage}}';
        mustacheMatches = input.value.match(/{{score_percentage}}/g);
        if(mustacheMatches && 0 < mustacheMatches.length) {
            charsLeft = charsLeft + (mustache.length * mustacheMatches.length) - (3 * mustacheMatches.length) ;
        }
    }

    return charsLeft;
};

var limitedChars__updateLength = function(counter, charsLeft) {

    // update the charcount
    counter.innerHTML = charsLeft;

    return charsLeft;
};


var limitedChars__setStates = function(input, counterContainer, charsLeft) {
    var save,
        allLimitedInputs,
        disabled,
        i,
        j;

    save = document.getElementsByClassName('enp-btn--submit');

    if(charsLeft < 0) {
        if(!counterContainer.classList.contains('limited-chars__container--error')) {
            counterContainer.classList.add('limited-chars__container--error');
            input.classList.add('has-error');
            input.setAttribute('aria-invalid', true);
            for(i = 0; i < save.length; i++ ) {
                save[i].disabled = true;
            }
        }
    } else {
        if(counterContainer.classList.contains('limited-chars__container--error')) {
            counterContainer.classList.remove('limited-chars__container--error');
            input.classList.remove('has-error');
            input.setAttribute('aria-invalid', false);
            // check if any others are disabled
            allLimitedInputs = document.getElementsByClassName('limited-chars');
            disabled = false;
            for(i = 0; i < allLimitedInputs.length; i++) {
                if(allLimitedInputs[i].classList.contains('has-error')) {
                    disabled = true;
                    break;
                }
            }
            if(disabled === false ) {
                for(j = 0; j < save.length; j++ ) {
                    save[j].disabled = false;
                }
            }

        }
    }

};
