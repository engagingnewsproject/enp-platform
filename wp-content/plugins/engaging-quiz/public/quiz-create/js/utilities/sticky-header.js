jQuery( document ).ready( function( $ ) {

    // rebuild the scroll obj
    $( window ).resize(_.debounce(function() {
        // run the checks/set-up the object again
        var scrollObj = createScrollObj();

        // turn it off for now
        $(document).off("scroll", setTrackScroll);

        // if the screen is large enough, turn on the scroll listener
        if(scrollObj.trackScroll === true) {
            $(document).on("scroll", scrollObj, _.throttle(setTrackScroll, 200));
            // trigger a scroll to apply our css
            $(window).scroll();
        }
    }, 250));

    function createScrollObj() {

        var nextStepBtn = $('#enp-btn--next-step');

        // check the CSS value to see if we need to apply an offset or not
        if(nextStepBtn.css('position') === ('absolute' || 'fixed')) {
            // the screen is large enough that the button is at the top of the page
            trackScroll = true;
            // get our vars
            var container = $('.js-enp-quiz-create-form-container');
            var nav = $('#enp-quiz-breadcrumbs');
            var containerOffsetTop = container.offset().top;
            var containerOffsetLeft = container.offset().left;
            var containerWidth = container.outerWidth();
            var nextStepBtnWidth = nextStepBtn.outerWidth();
            var nextStepBtnOffsetRight = nextStepBtn.css('right');
            nextStepBtnOffsetRight = nextStepBtnOffsetRight.replace('px','');
            nextStepBtnOffsetRight = parseInt(nextStepBtnOffsetRight);
            // want to get the offset of the width of the container + container width - whatever the set px offset in the css is
            var nextStepBtnLeft = containerOffsetLeft + containerWidth - nextStepBtnWidth - nextStepBtnOffsetRight;

            // create our scrollObj to return
            scrollObj = {trackScroll: trackScroll,
                        container: container,
                        nav: nav,
                        containerOffsetTop: containerOffsetTop,
                        nextStepBtn: nextStepBtn,
                        nextStepBtnLeft: nextStepBtnLeft};
        } else {
            // we're on a small screen. the button is at the bottom
            trackScroll = false;
            scrollObj = {trackScroll: trackScroll};
        }

        return scrollObj;
    }

    function setTrackScroll() {
        // scroll check
        var scrollTopDist = $(document).scrollTop();
        // we check vs the container, because after the breadcrumbs are
        // fixed, their offset.top is always 0
        if(scrollTopDist > scrollObj.containerOffsetTop) {
            if(!scrollObj.nav.hasClass('enp-quiz-breadcrumbs--fixed')) {
                scrollObj.nav.addClass('enp-quiz-breadcrumbs--fixed');
                scrollObj.nextStepBtn.addClass('enp-btn--next-step--fixed').css({left: scrollObj.nextStepBtnLeft});
            }
        } else if(scrollTopDist < scrollObj.containerOffsetTop) {
            if(scrollObj.nav.hasClass('enp-quiz-breadcrumbs--fixed')) {
                scrollObj.nav.removeClass('enp-quiz-breadcrumbs--fixed');
                scrollObj.nextStepBtn.removeClass('enp-btn--next-step--fixed').css({left: 'auto'});
            }
        }
    }

    // set-up vars
    var scrollObj = createScrollObj();

    // if the screen is large enough, turn on the scroll listener
    if(scrollObj.trackScroll === true) {
        // go ahead and apply the offset now
        $(document).on("scroll", scrollObj, _.throttle(setTrackScroll, 200));
    }





});
