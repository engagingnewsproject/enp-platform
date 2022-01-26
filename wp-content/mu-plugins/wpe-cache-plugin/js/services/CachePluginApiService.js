import DateTime from '../utils/DateTime';
class CachePluginApiService {
    constructor(nonce, paths) {
        this.nonce = nonce;
        this.paths = paths;
        jQuery.ajaxSetup({
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            },
        });
    }

    clearAllCaches() {
        return new Promise((resolve, reject) => {
            this.ajaxCall(
                this.paths.clearAllCachesPath,
                'POST',
                (data) => {
                    if (data.success) {
                        const dateTime = new Date(
                            Date.parse(data.time_cleared)
                        );
                        resolve(dateTime);
                    } else {
                        reject(data.last_error_at);
                    }
                },
                () => {
                    const now = DateTime.formatDate(new Date(Date.now())); 
                    reject(now);
                }
            );
        });
    }

    ajaxCall(path, method, onSuccess, onError) {
        jQuery.ajax({
            type: method,
            url: path,
            success: (data) => onSuccess(data),
            error: (error) => onError(error),
        });
    }
}

export default CachePluginApiService;
