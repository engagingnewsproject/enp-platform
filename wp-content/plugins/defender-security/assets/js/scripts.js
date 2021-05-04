window.Defender = window.Defender || {};

//Added extra parameter to allow for some actions to keep modal open
Defender.showNotification = function (type, message, closeModal) {
    var jq = jQuery;
    var html = '<div class="sui-floating-notices"><div role="alert" id="defender-notification" class="sui-notice" aria-live="assertive"></div></div>';
    if (jq('body').find('#defender-notification').size() === 0) {
        jq('.sui-wrap').prepend(html);
    }else{
        jq('#defender-notification').closest('.sui-floating-notices').replaceWith(jQuery(html));
    }
    var options = {
        type: 'green',
        icon: 'info',
        dismiss: {
            show: true,
            label: 'Click to close',
            tooltip: 'Dismiss'
        }
    }
    if (type === 'error') {
        options.type = 'red';
    } else if (type === 'info') {
        options.type = 'blue';
    }
    SUI.openNotice('defender-notification', '<p>' + message + '</p>', options);
}
