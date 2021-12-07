jQuery(document).ready(function($){

    // access token retrieving
    var $ctfRetrievedAccessToken = $('#ctf-retrieved-access-token'),
        $ctfRetrievedAccessTokenSecret = $('#ctf-retrieved-access-token-secret'),
        $ctfRetrievedDefaultScreenName = $('#ctf-retrieved-default-screen-name'),

    // toggle token input fields
        $ctfConsumerFields = $('.ctf-toggle-consumer'),
        $ctfAccessFields = $('.ctf-toggle-access'),
        $ctfHaveOwnTokens = $('#ctf_have_own_tokens');

    if ( $ctfRetrievedAccessToken.length ) {
        $('#ctf_access_token').val($ctfRetrievedAccessToken.val());
        $('#ctf_access_token_secret').val($ctfRetrievedAccessTokenSecret.val());
        if($('#ctf_usertimeline_text').val() == '') {
            $('#ctf_usertimeline_text').val($ctfRetrievedDefaultScreenName.val());
        }

        if (!$ctfHaveOwnTokens.is(':checked')) {
            $.ajax({
                url: ctf.ajax_url,
                type: 'post',
                data: {
                    action: 'ctf_auto_save_tokens',
                    security: ctf.sb_nonce,
                    access_token: $ctfRetrievedAccessToken.val(),
                    access_token_secret: $ctfRetrievedAccessTokenSecret.val(),
                    just_tokens: true
                },
                success: function (data) {
                    $('#ctf_access_token').after('<span class="ctf-success"><span class="fa fa-check-circle"></span> saved</span>');
                    $('#ctf_access_token_secret').after('<span class="ctf-success"><span class="fa fa-check-circle"></span> saved</span>');
                }
            });
        }
    }

    function toggleAccessInputs() {
        if($ctfHaveOwnTokens.is(':checked')) {
            $ctfAccessFields.show();
            $ctfConsumerFields.show();
        } else {
            $ctfConsumerFields.hide();
            if($ctfAccessFields.find('#ctf_access_token').val() == '' && $ctfAccessFields.find('#ctf_access_token_secret').val() == '') {
                $ctfAccessFields.hide();
                $ctfConsumerFields.hide();
            }
        }
    }
    toggleAccessInputs();

    $ctfHaveOwnTokens.on('change', function() {
        toggleAccessInputs();
    });

    // variables for time triggered validator
    var typingTimer,
        doneTypingInterval = 1000,
        $ctfSearchText = $('#ctf-admin #ctf_search_text'),
        $ctfUserText = $('#ctf-admin #ctf_usertimeline_text'),
        $ctfSearchError = $('#ctf-admin .ctf_search_error'),
        $ctfUserError= $('#ctf-admin .ctf_usertimeline_error');

    // hide elements when page loads
    $ctfSearchError.hide();
    $ctfUserError.hide();

    // on search text keyup, start timer to trigger validator
    $ctfSearchText.keyup(function(){
        clearTimeout(typingTimer);
        if($ctfSearchText.val){
            typingTimer = setTimeout(searchValidator, doneTypingInterval);
        }
    });

    // on usertimeline text keyup, start timer to trigger validator
    $ctfUserText.keyup(function(){
        clearTimeout(typingTimer);
        if($ctfUserText.val){
            typingTimer = setTimeout(userValidator, doneTypingInterval);
        }
    });

   // validate search input when user is done typing
    var internationalHashtagRegexString = "[A-z\u0041-\u005A\u0061-\u007A\u00AA\u00B5\u00BA\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u02C1\u02C6-\u02D1\u02E0-\u02E4\u02EC\u02EE\u0370-\u0374\u0376\u0377\u037A-\u037D\u0386\u0388-\u038A\u038C\u038E-\u03A1\u03A3-\u03F5\u03F7-\u0481\u048A-\u0527\u0531-\u0556\u0559\u0561-\u0587\u05D0-\u05EA\u05F0-\u05F2\u0620-\u064A\u066E\u066F\u0671-\u06D3\u06D5\u06E5\u06E6\u06EE\u06EF\u06FA-\u06FC\u06FF\u0710\u0712-\u072F\u074D-\u07A5\u07B1\u07CA-\u07EA\u07F4\u07F5\u07FA\u0800-\u0815\u081A\u0824\u0828\u0840-\u0858\u08A0\u08A2-\u08AC\u0904-\u0939\u093D\u0950\u0958-\u0961\u0971-\u0977\u0979-\u097F\u0985-\u098C\u098F\u0990\u0993-\u09A8\u09AA-\u09B0\u09B2\u09B6-\u09B9\u09BD\u09CE\u09DC\u09DD\u09DF-\u09E1\u09F0\u09F1\u0A05-\u0A0A\u0A0F\u0A10\u0A13-\u0A28\u0A2A-\u0A30\u0A32\u0A33\u0A35\u0A36\u0A38\u0A39\u0A59-\u0A5C\u0A5E\u0A72-\u0A74\u0A85-\u0A8D\u0A8F-\u0A91\u0A93-\u0AA8\u0AAA-\u0AB0\u0AB2\u0AB3\u0AB5-\u0AB9\u0ABD\u0AD0\u0AE0\u0AE1\u0B05-\u0B0C\u0B0F\u0B10\u0B13-\u0B28\u0B2A-\u0B30\u0B32\u0B33\u0B35-\u0B39\u0B3D\u0B5C\u0B5D\u0B5F-\u0B61\u0B71\u0B83\u0B85-\u0B8A\u0B8E-\u0B90\u0B92-\u0B95\u0B99\u0B9A\u0B9C\u0B9E\u0B9F\u0BA3\u0BA4\u0BA8-\u0BAA\u0BAE-\u0BB9\u0BD0\u0C05-\u0C0C\u0C0E-\u0C10\u0C12-\u0C28\u0C2A-\u0C33\u0C35-\u0C39\u0C3D\u0C58\u0C59\u0C60\u0C61\u0C85-\u0C8C\u0C8E-\u0C90\u0C92-\u0CA8\u0CAA-\u0CB3\u0CB5-\u0CB9\u0CBD\u0CDE\u0CE0\u0CE1\u0CF1\u0CF2\u0D05-\u0D0C\u0D0E-\u0D10\u0D12-\u0D3A\u0D3D\u0D4E\u0D60\u0D61\u0D7A-\u0D7F\u0D85-\u0D96\u0D9A-\u0DB1\u0DB3-\u0DBB\u0DBD\u0DC0-\u0DC6\u0E01-\u0E30\u0E32\u0E33\u0E40-\u0E46\u0E81\u0E82\u0E84\u0E87\u0E88\u0E8A\u0E8D\u0E94-\u0E97\u0E99-\u0E9F\u0EA1-\u0EA3\u0EA5\u0EA7\u0EAA\u0EAB\u0EAD-\u0EB0\u0EB2\u0EB3\u0EBD\u0EC0-\u0EC4\u0EC6\u0EDC-\u0EDF\u0F00\u0F40-\u0F47\u0F49-\u0F6C\u0F88-\u0F8C\u1000-\u102A\u103F\u1050-\u1055\u105A-\u105D\u1061\u1065\u1066\u106E-\u1070\u1075-\u1081\u108E\u10A0-\u10C5\u10C7\u10CD\u10D0-\u10FA\u10FC-\u1248\u124A-\u124D\u1250-\u1256\u1258\u125A-\u125D\u1260-\u1288\u128A-\u128D\u1290-\u12B0\u12B2-\u12B5\u12B8-\u12BE\u12C0\u12C2-\u12C5\u12C8-\u12D6\u12D8-\u1310\u1312-\u1315\u1318-\u135A\u1380-\u138F\u13A0-\u13F4\u1401-\u166C\u166F-\u167F\u1681-\u169A\u16A0-\u16EA\u1700-\u170C\u170E-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176C\u176E-\u1770\u1780-\u17B3\u17D7\u17DC\u1820-\u1877\u1880-\u18A8\u18AA\u18B0-\u18F5\u1900-\u191C\u1950-\u196D\u1970-\u1974\u1980-\u19AB\u19C1-\u19C7\u1A00-\u1A16\u1A20-\u1A54\u1AA7\u1B05-\u1B33\u1B45-\u1B4B\u1B83-\u1BA0\u1BAE\u1BAF\u1BBA-\u1BE5\u1C00-\u1C23\u1C4D-\u1C4F\u1C5A-\u1C7D\u1CE9-\u1CEC\u1CEE-\u1CF1\u1CF5\u1CF6\u1D00-\u1DBF\u1E00-\u1F15\u1F18-\u1F1D\u1F20-\u1F45\u1F48-\u1F4D\u1F50-\u1F57\u1F59\u1F5B\u1F5D\u1F5F-\u1F7D\u1F80-\u1FB4\u1FB6-\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6-\u1FCC\u1FD0-\u1FD3\u1FD6-\u1FDB\u1FE0-\u1FEC\u1FF2-\u1FF4\u1FF6-\u1FFC\u2071\u207F\u2090-\u209C\u2102\u2107\u210A-\u2113\u2115\u2119-\u211D\u2124\u2126\u2128\u212A-\u212D\u212F-\u2139\u213C-\u213F\u2145-\u2149\u214E\u2183\u2184\u2C00-\u2C2E\u2C30-\u2C5E\u2C60-\u2CE4\u2CEB-\u2CEE\u2CF2\u2CF3\u2D00-\u2D25\u2D27\u2D2D\u2D30-\u2D67\u2D6F\u2D80-\u2D96\u2DA0-\u2DA6\u2DA8-\u2DAE\u2DB0-\u2DB6\u2DB8-\u2DBE\u2DC0-\u2DC6\u2DC8-\u2DCE\u2DD0-\u2DD6\u2DD8-\u2DDE\u2E2F\u3005\u3006\u3031-\u3035\u303B\u303C\u3041-\u3096\u309D-\u309F\u30A1-\u30FA\u30FC-\u30FF\u3105-\u312D\u3131-\u318E\u31A0-\u31BA\u31F0-\u31FF\u3400-\u4DB5\u4E00-\u9FCC\uA000-\uA48C\uA4D0-\uA4FD\uA500-\uA60C\uA610-\uA61F\uA62A\uA62B\uA640-\uA66E\uA67F-\uA697\uA6A0-\uA6E5\uA717-\uA71F\uA722-\uA788\uA78B-\uA78E\uA790-\uA793\uA7A0-\uA7AA\uA7F8-\uA801\uA803-\uA805\uA807-\uA80A\uA80C-\uA822\uA840-\uA873\uA882-\uA8B3\uA8F2-\uA8F7\uA8FB\uA90A-\uA925\uA930-\uA946\uA960-\uA97C\uA984-\uA9B2\uA9CF\uAA00-\uAA28\uAA40-\uAA42\uAA44-\uAA4B\uAA60-\uAA76\uAA7A\uAA80-\uAAAF\uAAB1\uAAB5\uAAB6\uAAB9-\uAABD\uAAC0\uAAC2\uAADB-\uAADD\uAAE0-\uAAEA\uAAF2-\uAAF4\uAB01-\uAB06\uAB09-\uAB0E\uAB11-\uAB16\uAB20-\uAB26\uAB28-\uAB2E\uABC0-\uABE2\uAC00-\uD7A3\uD7B0-\uD7C6\uD7CB-\uD7FB\uF900-\uFA6D\uFA70-\uFAD9\uFB00-\uFB06\uFB13-\uFB17\uFB1D\uFB1F-\uFB28\uFB2A-\uFB36\uFB38-\uFB3C\uFB3E\uFB40\uFB41\uFB43\uFB44\uFB46-\uFBB1\uFBD3-\uFD3D\uFD50-\uFD8F\uFD92-\uFDC7\uFDF0-\uFDFB\uFE70-\uFE74\uFE76-\uFEFC\uFF21-\uFF3A\uFF41-\uFF5A\uFF66-\uFFBE\uFFC2-\uFFC7\uFFCA-\uFFCF\uFFD2-\uFFD7\uFFDA-\uFFDC",
        hashtagRegex = new RegExp("^"+internationalHashtagRegexString+"]"+internationalHashtagRegexString+"0-9_]+$|^#+"+internationalHashtagRegexString+"]"+internationalHashtagRegexString+"0-9_]+$");

    function searchValidator() {
        var ctfSearch = $ctfSearchText.val();

        if ( ctfSearch.indexOf(',') > -1 || ctfSearch.indexOf(' ') > -1 ){
            $ctfSearchError.slideDown();
        } else {
            $ctfSearchError.slideUp();
        }
    }

    // validate screen name input when user is done typing
    function userValidator(){
        var ctfUser = $ctfUserText.val();

        if(ctfUser.match(/^@[A-Za-z0-9_]{1,15}$/) || ctfUser.match(/^[A-Za-z0-9_]{1,15}$/)){
            $ctfUserError.slideUp();
        } else {
            $ctfUserError.slideDown();
        }
    }

    // search term guide toggle
    var $ctfToggleSearchGuide = $('#ctf-admin .ctf-toggle-search-guide');

    // hide initially
    $ctfToggleSearchGuide.closest('h4').next('div').hide();

    // show on click
    $ctfToggleSearchGuide.on('click',function(){
        $(this).closest('h4').next('div').slideToggle();
    });

    // tooltips
    $('#ctf-admin .ctf-tooltip-link').on('click',function(){
        $(this).closest('tr, h3, .ctf-tooltip-wrap').find('.ctf-tooltip').slideToggle();
    });

    // include replies
    $('.ctf_include_replies_toggle').hide();
    $('.ctf_include_replies_toggle input').prop('disabled', true);

    function toggleIncludeReplies() {
        $('.ctf_include_replies_toggle').each(function() {
            if($(this).closest('td').find('.ctf-feed-settings-radio').is(':checked')) {
                $(this).slideDown();
            } else {
                $(this).slideUp();
            }
        });
    }
    toggleIncludeReplies();

    $('.ctf-feed-settings-radio').on('change', function() {
        toggleIncludeReplies();

        if( $('#ctf-admin #ctf_usertimeline_radio').is(':checked') ) {
            userValidator();
            // $ctfSearchError.slideUp();
        } else if( $('#ctf-admin #ctf_search_radio').is(':checked') ) {
            searchValidator();
            // $ctfUserError.slideUp();
        }
    });

    // color picker
    var $ctfColorpicker = $('.ctf-colorpicker');

    if($ctfColorpicker.length > 0){
        $ctfColorpicker.wpColorPicker();
    }

    // shortcode tooltips
    var $ctfAdminLabel = $('#ctf-admin label');

    $ctfAdminLabel.on('click',function(){
        var $sbi_shortcode = $(this).siblings('.ctf_shortcode');
        if($sbi_shortcode.is(':visible')){
            $(this).removeClass('ctf_shortcode_visible');
            $(this).siblings('.ctf_shortcode').css('display','none');
        } else {
            $(this).addClass('ctf_shortcode_visible');
            $(this).siblings('.ctf_shortcode').css('display','block');
        }
    });

    $ctfAdminLabel.on('mouseenter mouseleave', function(e) {
        switch(e.type) {
            case 'mouseenter':
                if($(this).siblings('.ctf_shortcode').length > 0 ){
                    $(this).attr('title', 'Click for shortcode option').append('<code class="ctf_shortcode_symbol">[]</code>');
                }
                break;
            case 'mouseleave':
                $(this).find('.ctf_shortcode_symbol').remove();
                break;
        }
    });

    //Scroll to hash for quick links
    $('#ctf-admin a').on('click',function() {
        if(location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'') && location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : this.hash.slice(1);
            if(target.length) {
                $('html,body').animate({
                    scrollTop: target.offset().top
                }, 500);
                return false;
            }
        }
    });

    //Mobile width
    var ctfWidthUnit = $('#ctf-admin #ctf_width_unit').val(),
        ctfWidth = $('#ctf-admin #ctf_width').val(),
        $ctfWidthOptions = $('#ctf-admin #ctf_width_options');

    if (typeof ctfWidth !== 'undefined') {
        //Show initially if a width is set
        if(ctfWidth.length > 1 && !(ctfWidth == '100' && ctfWidthUnit == '%')) $ctfWidthOptions.show();

        $('#ctf_width, #ctf_width_unit').on('change',function(){
            ctfWidthUnit = $('#ctf-admin #ctf_width_unit').val(),
            ctfWidth = $('#ctf-admin #ctf_width').val();

            if(ctfWidth.length < 2 || (ctfWidth == '100' && ctfWidthUnit == '%')) {
                $ctfWidthOptions.slideUp();
            } else {
                $ctfWidthOptions.slideDown();
            }
        });
    }

    // clear cache
    var $ctfClearCacheButton = $('#ctf-admin #ctf-clear-cache');

    $ctfClearCacheButton.on('click',function(event) {
        event.preventDefault();

        $('#ctf-clear-cache-success').remove();
        $(this).prop("disabled",true);

        $.ajax({
            url : ctf.ajax_url,
            type : 'post',
            data : {
                action : 'ctf_clear_cache_admin'
            },
            success : function(data) {
                $ctfClearCacheButton.prop('disabled',false);
                if(!data===false) {
                    $ctfClearCacheButton.after('<span id="ctf-clear-cache-success" class="fa fa-check-circle ctf-success"></span>');
                } else {
                    $ctfClearCacheButton.after('<span>error</span>');
                }
            }
        }); // ajax call
    }); // clear-cache click

    // clear persistent cache
    var $ctfClearPersistentCacheButton = $('#ctf-admin #ctf-clear-persistent-cache');

    $ctfClearPersistentCacheButton.on('click',function(event) {
        event.preventDefault();

        $('#ctf-clear-cache-success').remove();
        $(this).prop("disabled",true);

        $.ajax({
            url : ctf.ajax_url,
            type : 'post',
            data : {
                action : 'ctf_clear_persistent_cache'
            },
            success : function(data) {
                $ctfClearPersistentCacheButton.prop('disabled',false);
                $ctfClearPersistentCacheButton.after('<span id="ctf-clear-cache-success" class="fa fa-check-circle ctf-success"></span>');
            }
        }); // ajax call
    }); // clear-persistent-cache click

    $('.ctf-opt-in').on('click',function(event) {
        event.preventDefault();

        var $btn = jQuery(this);
        $btn.prop( 'disabled', true ).addClass( 'loading' ).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');

        ctfSubmitOptIn(true);
    }); // clear_comment_cache click

    $('.ctf-no-usage-opt-out').on('click',function(event) {
        event.preventDefault();

        var $btn = jQuery(this);
        $btn.prop( 'disabled', true ).addClass( 'loading' ).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');

        ctfSubmitOptIn(false);
    }); // clear_comment_cache click

    function ctfSubmitOptIn(choice) {
        $.ajax({
            url : ctf.ajax_url,
            type : 'post',
            data : {
                action : 'ctf_usage_opt_in_or_out',
                opted_in: choice,
            },
            success : function(data) {
                $('.ctf-no-usage-opt-out').closest('.ctf-admin-notice').fadeOut();
            }
        }); // ajax call
    }

    //Pro version notices
    var ctfUpgradeNote = '<span class="ctf_note"> - <a href="https://smashballoon.com/custom-twitter-feeds/?utm_source=twitter-free&utm_source=settings&utm_medium=layout" target="_blank">Available in Pro version</a></span>';
    $('.ctf_pro').each(function(){
        var $pro = $(this);
        if (!$pro.find('.ctf_layout_options_wrap').length) {
            $pro.find('td').last().append(ctfUpgradeNote);
            $pro.find('input, select, textarea').attr('disabled', 'true');
        }
    });
    $('#ctf_include_twittercards, #ctf_include_media, #ctf_include_replied_to').prop('disabled', true).prop('checked',false).next('label').css('color', '#999').after(ctfUpgradeNote);

    $('#ctf-admin .ctf-show-pro').closest('span').next('.ctf-pro-options').hide();
    $('#ctf-admin .ctf-show-pro').on('click',function() {
        if ($(this).closest('span').next('.ctf-pro-options').is(':visible')) {
            $(this).closest('span').next('.ctf-pro-options').hide();
        } else {
            $(this).closest('span').next('.ctf-pro-options').show();
        }
    });

    function ctfUpdateLayoutTypeOptionsDisplay() {
        setTimeout(function(){
            jQuery('.ctf_layout_settings').hide();
            jQuery('.ctf_layout_settings.ctf_layout_type_'+jQuery('.ctf_layout_type:checked').val()).show();
        }, 1);
    }
    ctfUpdateLayoutTypeOptionsDisplay();
    jQuery('.ctf_layout_type').on('change',ctfUpdateLayoutTypeOptionsDisplay);

    // notices

    if (jQuery('#ctf-notice-bar').length) {
        jQuery('#wpadminbar').after(jQuery('#ctf-notice-bar'));
        jQuery('#wpcontent').css('padding-left', 0);
        jQuery('#wpbody').css('padding-left', '20px');
        jQuery('#ctf-notice-bar').show();
    }

    jQuery('#ctf-notice-bar .dismiss').on('click',function(e) {
        e.preventDefault();
        jQuery('#ctf-notice-bar').remove();
        jQuery.ajax({
            url: ctf.ajax_url,
            type: 'post',
            data: {
                action : 'ctf_lite_dismiss',
                ctf_nonce: ctf.sb_nonce
            },
            success: function (data) {
            }
        });
    });

    jQuery('.ctf_show_gdpr_list').on('click', function(){
        jQuery(this).closest('div').find('.ctf_gdpr_list').slideToggle();
    });

    //Selecting a post style
    jQuery('#ctf_gdpr_setting').on('change', function(){
        ctfCheckGdprSetting( jQuery(this).val() );
    });
    function ctfCheckGdprSetting(option) {
        if( option == 'yes' ){
            jQuery('.ctf_gdpr_yes').show();
            jQuery('.ctf_gdpr_no, .ctf_gdpr_auto').hide();
        }
        if( option == 'no' ){
            jQuery('.ctf_gdpr_no').show();
            jQuery('.ctf_gdpr_yes, .ctf_gdpr_auto').hide();
        }
        if( option == 'auto' ){
            jQuery('.ctf_gdpr_auto').show();
            jQuery('.ctf_gdpr_yes, .ctf_gdpr_no').hide();
        }
    }
    ctfCheckGdprSetting();

    // Locator
    jQuery('.ctf-locator-more').click(function(e) {
        e.preventDefault();
        jQuery(this).closest('td').find('.ctf-full-wrap').show();
        jQuery(this).closest('td').find('.ctf-condensed-wrap').hide();
        jQuery(this).remove();
    });

    //Click event for other plugins in menu
    $('.ctf_get_sbi, .ctf_get_cff, .ctf_get_ctf, .ctf_get_yt').parent().on('click', function(e){
        e.preventDefault();

        jQuery('.sb_cross_install_modal').remove();

        $('#wpbody-content').prepend('<div class="sb_cross_install_modal"><div class="sb_cross_install_inner" id="ctf-admin-about"><div id="ctf-admin-addons"><div class="addons-container"><i class="fa fa-spinner fa-spin ctf-loader" aria-hidden="true"></i></div></div></div></div>');

        var $self = $(this).find('span'),
            sb_get_plugin = 'custom_twitter_feeds';

        if( $self.hasClass('ctf_get_cff') ){
            sb_get_plugin = 'custom_facebook_feed';
        } else if( $self.hasClass('ctf_get_sbi') ){
            sb_get_plugin = 'instagram_feed';
        } else if( $self.hasClass('ctf_get_yt') ){
            sb_get_plugin = 'feeds_for_youtube';
        }

        $get_plugins_url = ctf.ajax_url.replace('admin-ajax.php', '');

        // Get the quick install box from the about page
        $('.sb_cross_install_modal .addons-container').load($get_plugins_url+'admin.php?page=custom-twitter-feeds&tab=more #install_'+sb_get_plugin);
    });
    //Close the modal if clicking anywhere outside it
    jQuery('body').on('click', '.sb_cross_install_modal', function(e){
        if (e.target !== this) return;
        jQuery('.sb_cross_install_modal').remove();
    });

    //Add class to Pro menu item
    $('.ctf_get_pro').parent().attr({'class':'ctf_get_pro_highlight', 'target':'_blank'});
});

/* global smash_admin, jconfirm, wpCookies, Choices, List */

(function($) {

    'use strict';

    // Global settings access.
    var s;

    // Admin object.
    var SmashAdmin = {

        // Settings.
        settings: {
            iconActivate: '<i class="fa fa-toggle-on fa-flip-horizontal" aria-hidden="true"></i>',
            iconDeactivate: '<i class="fa fa-toggle-on" aria-hidden="true"></i>',
            iconInstall: '<i class="fa fa-cloud-download" aria-hidden="true"></i>',
            iconSpinner: '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>',
            mediaFrame: false
        },

        /**
         * Start the engine.
         *
         * @since 1.3.9
         */
        init: function() {

            // Settings shortcut.
            s = this.settings;

            // Document ready.
            $( document ).ready( SmashAdmin.ready );

            // Addons List.
            SmashAdmin.initAddons();
        },

        /**
         * Document ready.
         *
         * @since 1.3.9
         */
        ready: function() {

            // Action available for each binding.
            $( document ).trigger( 'smashReady' );
        },

        //--------------------------------------------------------------------//
        // Addons List.
        //--------------------------------------------------------------------//

        /**
         * Element bindings for Addons List page.
         *
         * @since 1.3.9
         */
        initAddons: function() {

            // Some actions have to be delayed to document.ready.
            $( document ).on( 'smashReady', function() {

                // Only run on the addons page.
                if ( ! $( '#ctf-admin-addons' ).length ) {
                    return;
                }

                // Display all addon boxes as the same height.
                if( $( '#ctf-admin-about .addon-item').length ){
                    $( '#ctf-admin-about .addon-item .details' ).matchHeight( { byrow: false, property: 'height' } );
                }

                // Addons searching.
                if ( $('#ctf-admin-addons-list').length ) {
                    var addonSearch = new List( 'ctf-admin-addons-list', {
                        valueNames: [ 'addon-name' ]
                    } );

                    $( '#ctf-admin-addons-search' ).on( 'keyup', function () {
                        var searchTerm = $( this ).val(),
                            $heading = $( '#addons-heading' );

                        if ( searchTerm ) {
                            $heading.text( ctf_admin_strings.addon_search );
                        }
                        else {
                            $heading.text( $heading.data( 'text' ) );
                        }

                        addonSearch.search( searchTerm );
                    } );
                }
            });

            // Toggle an addon state.
            $( document ).on( 'click', '#ctf-admin-addons .addon-item button', function( event ) {

                event.preventDefault();

                if ( $( this ).hasClass( 'disabled' ) ) {
                    return false;
                }

                SmashAdmin.addonToggle( $( this ) );
            });
        },

        /**
         * Toggle addon state.
         *
         * @since 1.3.9
         */
        addonToggle: function( $btn ) {

            var $addon = $btn.closest( '.addon-item' ),
                plugin = $btn.attr( 'data-plugin' ),
                plugin_type = $btn.attr( 'data-type' ),
                action,
                cssClass,
                statusText,
                buttonText,
                errorText,
                successText;

            if ( $btn.hasClass( 'status-go-to-url' ) ) {
                // Open url in new tab.
                window.open( $btn.attr('data-plugin'), '_blank' );
                return;
            }

            $btn.prop( 'disabled', true ).addClass( 'loading' );
            $btn.html( s.iconSpinner );

            if ( $btn.hasClass( 'status-active' ) ) {
                // Deactivate.
                action     = 'ctf_deactivate_addon';
                cssClass   = 'status-inactive';
                if ( plugin_type === 'plugin' ) {
                    cssClass += ' button button-secondary';
                }
                statusText = ctf_admin_strings.addon_inactive;
                buttonText = ctf_admin_strings.addon_activate;
                if ( plugin_type === 'addon' ) {
                    buttonText = s.iconActivate + buttonText;
                }
                errorText  = s.iconDeactivate + ctf_admin_strings.addon_deactivate;

            } else if ( $btn.hasClass( 'status-inactive' ) ) {
                // Activate.
                action     = 'ctf_activate_addon';
                cssClass   = 'status-active';
                if ( plugin_type === 'plugin' ) {
                    cssClass += ' button button-secondary disabled';
                }
                statusText = ctf_admin_strings.addon_active;
                buttonText = ctf_admin_strings.addon_deactivate;
                if ( plugin_type === 'addon' ) {
                    buttonText = s.iconDeactivate + buttonText;
                } else if ( plugin_type === 'plugin' ) {
                    buttonText = ctf_admin_strings.addon_activated;
                }
                errorText  = s.iconActivate + ctf_admin_strings.addon_activate;

            } else if ( $btn.hasClass( 'status-download' ) ) {
                // Install & Activate.
                action   = 'ctf_install_addon';
                cssClass = 'status-active';
                if ( plugin_type === 'plugin' ) {
                    cssClass += ' button disabled';
                }
                statusText = ctf_admin_strings.addon_active;
                buttonText = ctf_admin_strings.addon_activated;
                if ( plugin_type === 'addon' ) {
                    buttonText = s.iconActivate + ctf_admin_strings.addon_deactivate;
                }
                errorText = s.iconInstall + ctf_admin_strings.addon_activate;

            } else {
                return;
            }

            var data = {
                action: action,
                nonce : ctf_admin_strings.nonce,
                plugin: plugin,
                type  : plugin_type
            };
            $.post( ctf_admin_strings.ajax_url, data, function( res ) {

                if ( res.success ) {
                    if ( 'ctf_install_addon' === action ) {
                        $btn.attr( 'data-plugin', res.data.basename );
                        successText = res.data.msg;
                        if ( ! res.data.is_activated ) {
                            cssClass = 'status-inactive';
                            if ( plugin_type === 'plugin' ) {
                                cssClass = 'button';
                            }
                            statusText = ctf_admin_strings.addon_inactive;
                            buttonText = s.iconActivate + ctf_admin_strings.addon_activate;
                        }
                    } else {
                        successText = res.data;
                    }
                    $addon.find( '.actions' ).append( '<div class="msg success">'+successText+'</div>' );
                    $addon.find( 'span.status-label' )
                        .removeClass( 'status-active status-inactive status-download' )
                        .addClass( cssClass )
                        .removeClass( 'button button-primary button-secondary disabled' )
                        .text( statusText );
                    $btn
                        .removeClass( 'status-active status-inactive status-download' )
                        .removeClass( 'button button-primary button-secondary disabled' )
                        .addClass( cssClass ).html( buttonText );
                } else {
                    if ( 'download_failed' === res.data[0].code ) {
                        if ( plugin_type === 'addon' ) {
                            $addon.find( '.actions' ).append( '<div class="msg error">'+ctf_admin_strings.addon_error+'</div>' );
                        } else {
                            $addon.find( '.actions' ).append( '<div class="msg error">'+ctf_admin_strings.plugin_error+'</div>' );
                        }
                    } else {
                        $addon.find( '.actions' ).append( '<div class="msg error">'+res.data+'</div>' );
                    }
                    $btn.html( errorText );
                }

                $btn.prop( 'disabled', false ).removeClass( 'loading' );

                // Automatically clear addon messages after 3 seconds.
                setTimeout( function() {
                    $( '.addon-item .msg' ).remove();
                }, 3000 );

            }).fail( function( xhr ) {
                console.log( xhr.responseText );
            });
        },

    };

    SmashAdmin.init();

    window.SmashAdmin = SmashAdmin;

})( jQuery );
