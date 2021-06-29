jQuery(function ($) {
    $('#install-defender-pro').click(function () {
        var that = $(this);
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                nonce: that.data('nonce'),
                action: 'installDefenderPro'
            },
            beforeSend: function () {
                that.attr('disabled', 'disabled');
            },
            success: function (data) {
                if (data.success == 1) {
                    location.href = data.data.url;
                } else {
                    that.removeAttr('disabled');
                    that.parent().append(data.data.message);
                    that.closest('div').removeClass('notice-info').addClass('notice-error');
                }
            }
        })
    })
    $('body').on('click', '.wp-defender-notice .notice-dismiss', function () {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'hideDefenderNotice',
            }
        })
    })
});