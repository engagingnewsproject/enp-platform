
jQuery(document).ready(function($) {
    const SUCCESS = 'success';
    const FAILURE = 'failure';
    const RATE_LIMIT_REACHED = 'rate_limit_reached';

    var ajaxCall = function(path, method, onSuccess, onError) {
        jQuery.ajax({
            type: method,
            url: path,
            headers: { 'X-WP-Nonce': wpApiSettings.nonce },
            success: function(data) { onSuccess(data) },
            error: function(error) { onError(error) },
        });
    };

    var redirectToCacheTab = function($notification) {
        var url = new URL($link.children().attr('href'));
        url.searchParams.append('notification', $notification);
        window.location.href = url.href;
    };


    var rateLimitStatusRequest = function(rateLimitStatusPath) {
        return new Promise((resolve, reject) => {
            ajaxCall( rateLimitStatusPath, 'POST', 
            (data) => {
                if (data.success) {
                    resolve(data.rate_limit_expired);
                } else {
                    reject();
                }
            },
            () => {
                reject();
            });
        });
    };

    var clearTheCachesAndRedirectToCacheTab = function(rateLimitExpired) {
        const rootPath = wpApiSettings.root; // this root path contains the base api path for the REST Routes       
        const clearAllCachesPath = `${rootPath}${WPECachePluginRequest.clear_all_caches_path}`;

        var success = function(data) {
            if (data.success) {
                redirectToCacheTab(SUCCESS);

            } else {
                redirectToCacheTab(FAILURE);
            }
        }
        var failure = function(e) {
            redirectToCacheTab(FAILURE);   
        }


        if (rateLimitExpired) {
            ajaxCall(
                clearAllCachesPath,
                'POST',
                success,
                failure
            );
            
        } else {
            redirectToCacheTab(RATE_LIMIT_REACHED);
        }
    }

    var listItemHandler = function(e) {
        const rootPath = wpApiSettings.root; // this root path contains the base api path for the REST Routes
        const rateLimitStatusPath = `${rootPath}${WPECachePluginRequest.rate_limit_status_path}`;

        $link = $(this);        
        e.preventDefault();
        jQuery('#wp-admin-bar-wpengine_adminbar').removeClass('hover');

        rateLimitStatusRequest(rateLimitStatusPath)
            .then(clearTheCachesAndRedirectToCacheTab)
            .catch( function() {
                redirectToCacheTab(FAILURE);
            });
    };

    var listItem = document.getElementById('wp-admin-bar-wpengine_adminbar_cache');
    if ( listItem ) {
        listItem.onclick = listItemHandler;
    }
});
