window.Defender = window.Defender || {};

//Added extra parameter to allow for some actions to keep modal open
Defender.showNotification = function (type, message, autoClose = false) {
    var jq = jQuery;
    var html = '<div class="sui-floating-notices"><div role="alert" id="defender-notification" class="sui-notice" aria-live="assertive"></div></div>';
    if (jq('body').find('#defender-notification').size() === 0) {
        jq('.sui-wrap').prepend(html);
    }else{
        jq('#defender-notification').closest('.sui-floating-notices').replaceWith(jQuery(html));
    }
    var options = {
        type: 'green',
        icon: 'info'
    }

    if (type === 'error') {
        options.type = 'red';
    } else if (type === 'warning') {
        options.type = 'warning';
    } else if (type === 'info') {
        options.type = 'blue';
    }

    if (true === autoClose) {
        options.autoclose = {
            show: true,
            timeout: 5000
        };
    } else {
        options.dismiss = {
            show: true,
            label: 'Click to close',
            tooltip: 'Dismiss'
        };
    }

    SUI.openNotice('defender-notification', '<p>' + message + '</p>', options);
}

/**
 * Filter default wp.i18n.__ function
 * `wpdef` is our domain
 */
wp.hooks.addFilter( 'i18n.gettext_wpdef', 'defender', function ( translation, text ) {
    if ( defenderGetText && defenderGetText[ text ] ) {
        return defenderGetText[ text ];
    }

    return translation;
}, 20 );
