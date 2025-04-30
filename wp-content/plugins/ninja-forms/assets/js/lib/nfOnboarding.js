function NinjaOnboarding () {
    this.init();
}

NinjaOnboarding.prototype.init  = function () {
    this.pointer = jQuery("#nf_blinky");
    this.backdrop = jQuery("#nf_curtain");
    this.next = '<span class="next">' + nfOBi18n.next + '</span>';
    this.star = '<span>*</span>';
    this.tip = {
        speech: jQuery(".nf-pixel-tip").first(),
        thought: jQuery(".nf-pixel-tip-floating").first(),
        bubble: jQuery(".nf-pixel-tip-thinking").first()
    }
    this.target = '';
    this.builderClean = 'undefined' != typeof(Backbone) ? Backbone.Radio.channel('app').request('get:setting', 'clean') : true;
    this.builderCurrentDrawer = '';
    this.currentStep = 0;
    this.box = new jBox('Modal', {
        addClass: 'nf-onboarding-modal',
        title: '',
        closeButton: 'title',
        onClose: function(e) {
            nfOB.modal(4);
        }
    });
    this.confirmBox = new jBox('Modal', {
        addClass: 'nf-onboarding-modal',
    });
    this.congratulationsBox = new jBox('Modal', {
        addClass: 'nf-onboarding-modal',
        title: '',
        closeButton: 'title',
        onClose: function(e) {
            nfOB.step(0);
        },
        content: '<img src="' + nfAdmin.requireBaseUrl + '../img/onboarding/congratulations.png" alt="' + nfOBi18n.congratulations + '" /><p>' + nfOBi18n.congratulations1 + '</p><p>' + nfOBi18n.congratulations2 + '</p><a href="https://wordpress.org/support/plugin/ninja-forms/reviews/#new-post" target="_blank" class="nf-button primary">' + nfOBi18n.rateUs + '</a>'
    });
}

/**
 * Show the beacon over a target element.
 * @param {String} ref
 */
NinjaOnboarding.prototype.beacon = function (ref = '', offset = {}) {
    if(typeof(offset.y) == 'undefined') offset.y = 0;
    if(typeof(offset.x) == 'undefined') offset.x = 0;
    if(this.target !== '') {
        this.target.unbind('mouseenter mouseleave');
    }
    if(ref === '') {
        this.pointer.hide();
        this.target = '';
        return;
    }
    this.target = jQuery(ref);
    let pos = this.target.offset();
    let h = this.target.outerHeight();
    let w = this.target.outerWidth();
    this.pointer.css({
        position: 'fixed',
        top: (pos.top + h / 2 - 24 + offset.y) + 'px',
        left: (pos.left + w / 2 - 24 + offset.x) + 'px'
    })
    .show();
    this.target.hover( function(e) {
        nfOB.pointer.hide();
    }, function(e) {
        nfOB.pointer.show();
    });
}

/**
 * Toggle visibility of the backdrop.
 * @param {Boolean} hide 
 */
NinjaOnboarding.prototype.curtain = function (hide = false) {
    if(hide) {
        this.backdrop.hide();
    } else {
        this.backdrop.show();
    }
}

NinjaOnboarding.prototype.say = function (message) {
    this.mute();
    // Show speech bubble.
    this.tip.speech.html(message).show();
}

NinjaOnboarding.prototype.mute = function () {
    // Hide speech bubble.
    this.tip.speech.html('').hide();
    // Hide thought bubble.
    this.tip.bubble.hide();
    this.tip.thought.html('').hide();
}

NinjaOnboarding.prototype.think = function (message) {
    this.mute();
    // Show thought bubble.
    left = this.pointer.offset().left + 12 + 'px';
    this.tip.bubble.show();
    this.tip.thought.html(this.star + message).css({'left': left, 'top': '125px'}).show();
}

NinjaOnboarding.prototype.cleanChanged = function (clean) {
    this.builderClean = clean;
    this.step(this.currentStep);
}

NinjaOnboarding.prototype.drawerClosed = function () {
    this.builderCurrentDrawer = '';
    this.step(this.currentStep);
}

NinjaOnboarding.prototype.drawerOpened = function () {
    this.builderCurrentDrawer = Backbone.Radio.channel('app').request('get:currentDrawer').get('id');
    let safeDrawer = [ '', 'newForm' ];
    if(this.currentStep == 9 && !safeDrawer.includes(this.builderCurrentDrawer)) this.step(this.currentStep);
}

NinjaOnboarding.prototype.step = function (step = 0) {
    if(step > 0) this.currentStep = step;
    switch (step) {
        case 1:
            /* Dashboard */
            this.tip.speech.addClass('dashboard');
            this.tip.speech.css('left', jQuery('.app-title').first().offset().left - 55);
            window.onresize = function(e) {nfOB.tip.speech.css('left', jQuery('.app-title').first().offset().left - 55)};
            this.say(nfOBi18n.step1);
            jQuery('#nf-start').html(nfOBi18n.inProgress).addClass('disabled');
            this.beacon('.add.nf-button.primary', {x: 60});
            this.target.one('click', function(e){
                e.preventDefault();
                jQuery.post(
                    nfAdmin.ajax_url,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfAdmin.onboardingStep = '2';
                        nfOB.step(2);
                    }
                });
            });
            break;
        case 2:
            /* Dashboard */
            this.tip.speech.addClass('dashboard');
            this.tip.speech.css('left', jQuery('.app-title').first().offset().left - 55);
            window.onresize = function(e) {nfOB.tip.speech.css('left', jQuery('.app-title').first().offset().left - 55)};
            this.say(nfOBi18n.step2);
            setTimeout( function() {
                jQuery('.template').not('.ad').addClass('glow').click(function(e){
                    jQuery.post(
                        nfAdmin.ajax_url,
                        {
                            'action': 'nf_onboarding_next'
                        }
                    ).then (function (response ) {
                        response = JSON.parse(response);
                        if(response.data.success) {
                        }
                    });
                });
            }, 1000);
            break;
        case 3:
            /* Builder */
            Backbone.Radio.channel( 'app' ).request( 'open:drawer', 'addField' );
            this.say(nfOBi18n.step3 + this.next);
            jQuery('.nf-pixel-tip > .next').click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(4);
                    }
                });
            });
            break;
        case 4:
            /* Builder */
            Backbone.Radio.channel( 'app' ).request( 'close:drawer' );
            this.say(nfOBi18n.step4 + this.next);
            jQuery('.nf-pixel-tip > .next').click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(5);
                    }
                });
            });
            break;
        case 5:
            /* Builder */
            this.beacon('[title|="Emails & Actions"]', {y: 20});
            this.think(nfOBi18n.step5);
            this.target.click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(6);
                    }
                });
            });
            break;
        case 6:
            /* Builder */
            if(Backbone.Radio.channel('app').request('get:currentDomain').id !== 'actions') {
                setTimeout( function() {
                    Backbone.Radio.channel( 'app' ).request( 'close:drawer' );
                    Backbone.Radio.channel('hotkeys').trigger('changeDomain:actions');
                }, 1000);
            }
            this.say(nfOBi18n.step6 + this.next);
            jQuery('.nf-pixel-tip > .next').click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(7);
                    }
                });
            });
            break;
        case 7:
            /* Builder */
            this.beacon('[title|="Advanced"]', {y: 20});
            this.think(nfOBi18n.step7);
            this.target.click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(8);
                    }
                });
            });
            break;
        case 8:
            /* Builder */
            if(Backbone.Radio.channel('app').request('get:currentDomain').id !== 'settings') {
                setTimeout( function() {
                    Backbone.Radio.channel( 'app' ).request( 'close:drawer' );
                    Backbone.Radio.channel('hotkeys').trigger('changeDomain:settings');
                }, 1000);
            }
            this.say(nfOBi18n.step8 + this.next);
            jQuery('.nf-pixel-tip > .next').click(function(e) {
                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'nf_onboarding_next'
                    }
                ).then (function (response ) {
                    response = JSON.parse(response);
                    if(response.data.success) {
                        nfOB.step(9);
                    }
                });
            });
            break;
        case 9:
            /* Builder */
            // If the publish button is disabled
            // OR the drawer is open
            let safeDrawer = [ '', 'newForm' ];
            if(this.builderClean || !safeDrawer.includes(this.builderCurrentDrawer)) {
                this.step(0);
                break;
            }
            this.say(nfOBi18n.step9);
            this.beacon('.primary.publish', {x: -55});
            this.target.click(function(e) {
                let id = String(Backbone.Radio.channel('app').request('get:formModel').id);
                // If this form wasn't from a template...
                if( id.startsWith('tmp-') ) {
                    nfOB.say(nfOBi18n.step9opt);
                    nfOB.beacon();
                    setTimeout(function() {
                        if( '' === jQuery('#title').val() ) {
                            nfOB.target = jQuery('.primary.nf-close-drawer');
                            jQuery('#title').addClass('glow').on('input', function(e) {
                                jQuery('#title').removeClass('glow');
                                nfOB.beacon('.primary.nf-close-drawer', {x: -55});
                            });
                        } else {
                            nfOB.beacon('.primary.nf-close-drawer', {x: -55});
                        }
                        nfOB.target.click(function(e) {
                            jQuery.post(
                                ajaxurl,
                                {
                                    // 'action': 'nf_onboarding_next'
                                    'action': 'nf_onboarding_complete'
                                }
                            ).then (function (response ) {
                                response = JSON.parse(response);
                                if(response.data.success) {
                                    // nfOB.step(10);
                                    nfOB.step(11);
                                }
                            });
                        });
                    }, 500);
                } else {
                    jQuery.post(
                        ajaxurl,
                        {
                            // 'action': 'nf_onboarding_next'
                            'action': 'nf_onboarding_complete'
                        }
                    ).then (function (response ) {
                        response = JSON.parse(response);
                        if(response.data.success) {
                            // nfOB.step(10);
                            nfOB.step(11);
                        }
                    });
                }
            });
            break;
        case 10:
            /* Builder */
            /* Currently Bypassed */
            this.mute();
            this.curtain();
            this.beacon();
            this.modal(1);
            break;
        case 11:
            /* Post Editor */
            /* Temporarily in Builder */
            this.mute();
            this.curtain();
            this.congratulationsBox.open();
            break;
        default:
            /* Shutdown */
            this.mute();
            this.curtain(true);
            this.beacon();
    }
}

/* Currently Unused */
NinjaOnboarding.prototype.modal = function (contents = 0) {
    this.confirmBox.close();
    switch (contents) {
        case 1:
            let choose = '<button type="button" onClick="nfOB.modal(2)">' + nfOBi18n.chooseBtn + '</button>';
            let create = '<button type="button" onClick="nfOB.modal(3)">' + nfOBi18n.createBtn + '</button>';
            this.box.setContent(nfOBi18n.appendTitle + '<br />' + choose + create + '<br />' + nfOBi18n.step10);
            this.box.open();
            break;
        case 2:
            go = '<button type="button" onClick="nfOB.validate(2)">' + nfOBi18n.go + '</button>';
            back = '<button type="button" onClick="nfOB.modal(1)">' + nfOBi18n.back + '</button>';
            this.box.setContent(nfOBi18n.selectTitle + '<br />' + go + back + '<br />');
            this.box.open();
            break;
        case 3:
            go = '<button type="button" onClick="nfOB.validate(3)">' + nfOBi18n.go + '</button>';
            back = '<button type="button" onClick="nfOB.modal(1)">' + nfOBi18n.back + '</button>';
            this.box.setContent(nfOBi18n.createTitle + '<br />' + go + back + '<br />');
            this.box.open();
            break;
        case 4:
            go = '<button type="button" onClick="nfOB.modal(5)">' + nfOBi18n.exitb1 + '</button>';
            back = '<button type="button" onClick="nfOB.modal(1)">' + nfOBi18n.exitb2 + '</button>';
            this.confirmBox.setContent(nfOBi18n.exit + '<br />' + go + back + '<br />');
            this.confirmBox.open();
            break;
        case 5:
            jQuery.post(
                ajaxurl,
                {
                    'action': 'nf_onboarding_dismiss'
                }
            ).then (function (response ) {
                response = JSON.parse(response);
                if(response.data.success) {
                    nfOB.box.destroy();
                    nfOB.step(0);
                }
            });
            break;
        default:
            this.box.close();
            break;
    }
}

/* Currently Unused */
NinjaOnboarding.prototype.validate = function (modal) {
    if(modal === 2) {
        // Make sure we have a selection
    } else {
        // Make sure we have a title
    }
}