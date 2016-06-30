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
        i;

    // add our class to it
    input.classList.add('limited-chars');

    // create a new element for our character count wrapper
    counterContainer = document.createElement('span');
    counterContainer.classList.add('limited-chars__container');

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

    // set the initial states
    limitedChars__updateLength(input);

    // add event listener to the textarea
    input.addEventListener("input", limitedChars__eventHandler);
}

var limitedChars__eventHandler =  function(e) {
    var el;

    el = e.target;

    limitedChars__updateLength(el);
};

// get the current length of the input
var limitedChars__charsLeft = function(input) {
    var maxLength,
        charsLeft,
        mustache,
        mustacheMatches;

    maxLength = 117;
    charsLeft = maxLength - input.value.length;

    // see if we should allow mustache template variables and what they're worth
    if(input.classList.contains('enp-quiz-share__textarea--after')) {
        mustache = '{{score_percentage}}';
        mustacheMatches = input.value.match(/{{score_percentage}}/g);
        if(mustacheMatches && 0 < mustacheMatches.length) {
            charsLeft = charsLeft + (mustache.length * mustacheMatches.length) - (3 * mustacheMatches.length) ;
        }
    }

    return charsLeft;
};

var limitedChars__updateLength = function(el) {
    var counter,
        counterContainer,
        save,
        charsLeft,
        allLimitedInputs,
        disabled,
        i,
        j;

    counterContainer = el.nextElementSibling;
    counter = counterContainer.getElementsByClassName('limited-chars__counter')[0];
    save = document.getElementsByClassName('enp-btn--submit');
    charsLeft = limitedChars__charsLeft(el);

    if(charsLeft < 0) {
        if(!counterContainer.classList.contains('limited-chars__container--error')) {
            counterContainer.classList.add('limited-chars__container--error');
            el.classList.add('has-error');
            for(i = 0; i < save.length; i++ ) {
                save[i].disabled = true;
            }
        }
    } else {
        if(counterContainer.classList.contains('limited-chars__container--error')) {
            counterContainer.classList.remove('limited-chars__container--error');
            el.classList.remove('has-error');
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


    // update the charcount
    counter.innerHTML = charsLeft;
};
