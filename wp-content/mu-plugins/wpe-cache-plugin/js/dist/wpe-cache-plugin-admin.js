var $parcel$global =
typeof globalThis !== 'undefined'
  ? globalThis
  : typeof self !== 'undefined'
  ? self
  : typeof window !== 'undefined'
  ? window
  : typeof global !== 'undefined'
  ? global
  : {};
var $parcel$modules = {};
var $parcel$inits = {};

var parcelRequire = $parcel$global["parcelRequire8534"];
if (parcelRequire == null) {
  parcelRequire = function(id) {
    if (id in $parcel$modules) {
      return $parcel$modules[id].exports;
    }
    if (id in $parcel$inits) {
      var init = $parcel$inits[id];
      delete $parcel$inits[id];
      var module = {id: id, exports: {}};
      $parcel$modules[id] = module;
      init.call(module.exports, module, module.exports);
      return module.exports;
    }
    var err = new Error("Cannot find module '" + id + "'");
    err.code = 'MODULE_NOT_FOUND';
    throw err;
  };

  parcelRequire.register = function register(id, init) {
    $parcel$inits[id] = init;
  };

  $parcel$global["parcelRequire8534"] = parcelRequire;
}
"use strict";
parcelRequire.register("3jFSf", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $26a3a0c4e1c7244e$var$_DateTime = $26a3a0c4e1c7244e$var$_interopRequireDefault((parcelRequire("fCeHw")));

var $26a3a0c4e1c7244e$var$_JQElement = $26a3a0c4e1c7244e$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $26a3a0c4e1c7244e$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
/**
 * Represents the last cleared text element
 */ class $26a3a0c4e1c7244e$var$LastClearedText extends $26a3a0c4e1c7244e$var$_JQElement.default {
    setLastClearedText(date) {
        if (this.element.length) {
            let lastClearedAt;
            try {
                lastClearedAt = $26a3a0c4e1c7244e$var$_DateTime.default.formatDate(new Date(date));
            } catch  {
                lastClearedAt = $26a3a0c4e1c7244e$var$_DateTime.default.formatDate(new Date(Date.now()));
            }
            this.setText(`Last cleared: ${lastClearedAt}`);
        }
    }
    constructor(element = jQuery('#wpe-last-cleared-text')){
        super(element);
    }
}
var $26a3a0c4e1c7244e$var$_default = $26a3a0c4e1c7244e$var$LastClearedText;
module.exports.default = $26a3a0c4e1c7244e$var$_default;

});
parcelRequire.register("fCeHw", function(module, exports) {
'use strict';
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $b5e5ceee33b8fda8$var$_Time = $b5e5ceee33b8fda8$var$_interopRequireDefault((parcelRequire("d6oBM")));
function $b5e5ceee33b8fda8$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
class $b5e5ceee33b8fda8$var$DateTime {
    static getDateTimeUTC(date) {
        return date.getTime() + $b5e5ceee33b8fda8$var$_Time.default.minutes(date.getTimezoneOffset());
    }
    static getLocalDateTimeFromUTC(date) {
        const newDate = new Date(date.getTime() + $b5e5ceee33b8fda8$var$_Time.default.minutes(date.getTimezoneOffset()));
        const offset = date.getTimezoneOffset() / 60;
        const hours = date.getHours();
        newDate.setHours(hours - offset);
        return newDate;
    }
    static formatDate(date, locale = window.navigator.language || 'en-US') {
        const localOptions = {
            dateStyle: 'medium',
            timeStyle: 'short'
        };
        return `${new Intl.DateTimeFormat(locale, localOptions).format(date)} UTC`;
    }
    static isLastClearedExpired(lastClearedAt, threshold = $b5e5ceee33b8fda8$var$_Time.default.minutes(5)) {
        const lastClearedAtDate = new Date(Date.parse(lastClearedAt));
        if (!this.isValidDate(lastClearedAtDate)) {
            console.warn(`Invalid date: ${lastClearedAt}`);
            return true;
        }
        const now = $b5e5ceee33b8fda8$var$DateTime.getDateTimeUTC(new Date(Date.now()));
        return now - lastClearedAtDate.getTime() > threshold;
    }
    static isValidDate(d) {
        return d instanceof Date && !Number.isNaN(d.getTime());
    }
}
var $b5e5ceee33b8fda8$var$_default = $b5e5ceee33b8fda8$var$DateTime;
module.exports.default = $b5e5ceee33b8fda8$var$_default;

});
parcelRequire.register("d6oBM", function(module, exports) {
'use strict';
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;
class $989eec95120977d9$var$Time {
    static hours(h) {
        return h * 3600000;
    }
    static minutes(m) {
        return m * 60000;
    }
    static days(d) {
        return d * 86400000;
    }
}
var $989eec95120977d9$var$_default = $989eec95120977d9$var$Time;
module.exports.default = $989eec95120977d9$var$_default;

});


parcelRequire.register("gvtgQ", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;
/**
 * Represents a JQuery Element in the DOM
 */ class $c0463f268f7e0b56$var$JQElement {
    setText(text) {
        var _this$element;
        if (((_this$element = this.element) === null || _this$element === void 0 ? void 0 : _this$element.text()) !== text) this.element.text(text);
    }
    constructor(element){
        this.element = element;
    }
}
var $c0463f268f7e0b56$var$_default = $c0463f268f7e0b56$var$JQElement;
module.exports.default = $c0463f268f7e0b56$var$_default;

});



var $972e5950cb8d9133$var$_LastClearedText = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("3jFSf")));
parcelRequire.register("ia8rg", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $d38fb4f1758fb37f$var$_DateTime = $d38fb4f1758fb37f$var$_interopRequireDefault((parcelRequire("fCeHw")));

var $d38fb4f1758fb37f$var$_JQElement = $d38fb4f1758fb37f$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $d38fb4f1758fb37f$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
class $d38fb4f1758fb37f$var$LastErrorText extends $d38fb4f1758fb37f$var$_JQElement.default {
    setLastErrorText(date) {
        if (this.element.length) {
            let lastErrorAt;
            try {
                lastErrorAt = $d38fb4f1758fb37f$var$_DateTime.default.formatDate(new Date(date));
            } catch  {
                lastErrorAt = $d38fb4f1758fb37f$var$_DateTime.default.formatDate(new Date(Date.now()));
            }
            this.setText(`Error clearing all cache: ${lastErrorAt}`);
        }
    }
    constructor(element = jQuery('#wpe-last-cleared-error-text')){
        super(element);
    }
}
var $d38fb4f1758fb37f$var$_default = $d38fb4f1758fb37f$var$LastErrorText;
module.exports.default = $d38fb4f1758fb37f$var$_default;

});


var $972e5950cb8d9133$var$_LastErrorText = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("ia8rg")));
parcelRequire.register("8uLyv", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $62f6039ccb91cd41$var$_JQElement = $62f6039ccb91cd41$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $62f6039ccb91cd41$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
class $62f6039ccb91cd41$var$ErrorToast extends $62f6039ccb91cd41$var$_JQElement.default {
    showToast() {
        if (this.element.length) this.element.attr('style', 'display: block');
    }
    constructor(element = jQuery('#wpe-cache-error-toast')){
        super(element);
    }
}
var $62f6039ccb91cd41$var$_default = $62f6039ccb91cd41$var$ErrorToast;
module.exports.default = $62f6039ccb91cd41$var$_default;

});


var $972e5950cb8d9133$var$_ErrorToast = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("8uLyv")));
parcelRequire.register("9ms2k", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $6d0bea94a5cab383$var$_JQElement = $6d0bea94a5cab383$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $6d0bea94a5cab383$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
/**
 * Represents the clear all caches button
 */ class $6d0bea94a5cab383$var$ClearAllCacheBtn extends $6d0bea94a5cab383$var$_JQElement.default {
    setDisabled(reason = 'Clear all caches button disabled for 5 minutes') {
        if (this.element.length) {
            this.element.attr('aria-disabled', true);
            this.element.attr('aria-describedby', reason);
            this.element.attr('disabled', true);
        }
    }
    attachSubmit({ onSuccess: onSuccess , onError: onError  }) {
        this.element.one('click', ()=>{
            this.setDisabled();
            this.apiService.clearAllCaches().then(onSuccess).catch(onError);
        });
    }
    constructor(apiService, element = jQuery('#wpe-clear-all-cache-btn')){
        super(element);
        this.apiService = apiService;
    }
}
var $6d0bea94a5cab383$var$_default = $6d0bea94a5cab383$var$ClearAllCacheBtn;
module.exports.default = $6d0bea94a5cab383$var$_default;

});


var $972e5950cb8d9133$var$_ClearAllCacheBtn = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("9ms2k")));
parcelRequire.register("lhIHl", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $f7eddb9feb325a3e$var$_JQElement = $f7eddb9feb325a3e$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $f7eddb9feb325a3e$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
/**
 * Represents the clear all caches icon
 */ class $f7eddb9feb325a3e$var$ClearAllCacheIcon extends $f7eddb9feb325a3e$var$_JQElement.default {
    setSuccessIcon() {
        if (this.element.length) this.element.attr('style', "content: url(\"data:image/svg+xml,%3Csvg width='50' height='50' viewBox='0 0 32 33' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Crect y='0.600098' width='32' height='32' rx='16' fill='%230ecad4'/%3E%3Cpath d='M21 12.7993L14.2 19.5993L11.4 16.7993L10 18.1993L14.2 22.3993L22.4 14.1993L21 12.7993Z' fill='white'/%3E%3C/svg%3E \");");
    }
    setErrorIcon() {
        if (this.element.length) this.element.attr('style', "content: url(\"data:image/svg+xml,%3Csvg width='32' height='33' viewBox='0 0 32 33' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M16 0.242615C12.8355 0.242615 9.74207 1.181 7.11088 2.9391C4.4797 4.6972 2.42894 7.19606 1.21793 10.1197C0.0069327 13.0433 -0.309921 16.2604 0.307443 19.3641C0.924806 22.4678 2.44866 25.3187 4.6863 27.5563C6.92394 29.794 9.77486 31.3178 12.8786 31.9352C15.9823 32.5525 19.1993 32.2357 22.1229 31.0247C25.0466 29.8137 27.5454 27.7629 29.3035 25.1317C31.0616 22.5005 32 19.4071 32 16.2426C31.9952 12.0006 30.308 7.93375 27.3084 4.93421C24.3089 1.93466 20.242 0.247414 16 0.242615ZM3.20001 16.2426C3.19796 13.8473 3.86862 11.4996 5.13558 9.46686C6.40255 7.4341 8.21491 5.79798 10.3662 4.74485C12.5176 3.69172 14.9214 3.26391 17.304 3.51013C19.6866 3.75635 21.9522 4.66672 23.8427 6.13755L5.89494 24.0853C4.14652 21.8451 3.19786 19.0843 3.20001 16.2426ZM16 29.0426C13.1592 29.0442 10.3995 28.0955 8.16 26.3477L26.1051 8.40261C27.5751 10.2931 28.4848 12.5584 28.7306 14.9406C28.9764 17.3228 28.5484 19.7261 27.4954 21.877C26.4424 24.0278 24.8066 25.8398 22.7743 27.1067C20.742 28.3735 18.3948 29.0443 16 29.0426Z' fill='%23D21B46'/%3E%3C/svg%3E%0A\");");
    }
    constructor(element = jQuery('#wpe-clear-all-cache-icon')){
        super(element);
    }
}
var $f7eddb9feb325a3e$var$_default = $f7eddb9feb325a3e$var$ClearAllCacheIcon;
module.exports.default = $f7eddb9feb325a3e$var$_default;

});


var $972e5950cb8d9133$var$_ClearAllCacheIcon = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("lhIHl")));
parcelRequire.register("cYlaD", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $971b82a25e3040a1$var$_DateTime = $971b82a25e3040a1$var$_interopRequireDefault((parcelRequire("fCeHw")));
function $971b82a25e3040a1$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
class $971b82a25e3040a1$var$CachePluginApiService {
    clearAllCaches() {
        return new Promise((resolve, reject)=>{
            this.ajaxCall(this.paths.clearAllCachesPath, 'POST', (data)=>{
                if (data.success) {
                    const dateTime = new Date(Date.parse(data.time_cleared));
                    resolve(dateTime);
                } else reject(data.last_error_at);
            }, ()=>{
                const now = $971b82a25e3040a1$var$_DateTime.default.formatDate(new Date(Date.now()));
                reject(now);
            });
        });
    }
    ajaxCall(path, method, onSuccess, onError) {
        jQuery.ajax({
            type: method,
            url: path,
            success: (data)=>onSuccess(data)
            ,
            error: (error)=>onError(error)
        });
    }
    constructor(nonce, paths){
        this.nonce = nonce;
        this.paths = paths;
        jQuery.ajaxSetup({
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', nonce);
            }
        });
    }
}
var $971b82a25e3040a1$var$_default = $971b82a25e3040a1$var$CachePluginApiService;
module.exports.default = $971b82a25e3040a1$var$_default;

});


var $972e5950cb8d9133$var$_CachePluginApiService = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("cYlaD")));

var $972e5950cb8d9133$var$_DateTime = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("fCeHw")));
parcelRequire.register("drdNH", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;
function $9c888078b014f48f$var$_classPrivateMethodGet(receiver, privateSet, fn) {
    if (!privateSet.has(receiver)) throw new TypeError("attempted to get private field on non-instance");
    return fn;
}
var $9c888078b014f48f$var$_removeQueryParam = /*#__PURE__*/ new WeakSet();
class $9c888078b014f48f$var$CachePluginWindowModifier {
    stripQueryParamFromPathname(queryParam) {
        const urlParams = $9c888078b014f48f$var$_classPrivateMethodGet(this, $9c888078b014f48f$var$_removeQueryParam, $9c888078b014f48f$var$_removeQueryParam2).call(this, queryParam);
        return `${this.window.location.pathname}?${urlParams}`;
    }
    replaceWindowState(url) {
        this.window.history.replaceState(null, '', url);
    }
    constructor(window){
        $9c888078b014f48f$var$_removeQueryParam.add(this);
        this.window = window;
    }
}
function $9c888078b014f48f$var$_removeQueryParam2(queryParam) {
    const newUrl = new URL(this.window.location.href);
    let params = new URLSearchParams(newUrl.search);
    params.delete(queryParam);
    return params;
}
var $9c888078b014f48f$var$_default = $9c888078b014f48f$var$CachePluginWindowModifier;
module.exports.default = $9c888078b014f48f$var$_default;

});


var $972e5950cb8d9133$var$_CachePluginWindowModifier = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("drdNH")));
parcelRequire.register("d8eVl", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;

var $98f79949a198409d$var$_JQElement = $98f79949a198409d$var$_interopRequireDefault((parcelRequire("gvtgQ")));
function $98f79949a198409d$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
/**
 * Represents the hidden _wp_http_referer field in the cache times form
 */ class $98f79949a198409d$var$CacheTimesFormReferField extends $98f79949a198409d$var$_JQElement.default {
    replaceRefer(url) {
        this.element.val(url);
    }
    constructor(element = jQuery('input[name="_wp_http_referer"]')){
        super(element);
    }
}
var $98f79949a198409d$var$_default = $98f79949a198409d$var$CacheTimesFormReferField;
module.exports.default = $98f79949a198409d$var$_default;

});


var $972e5950cb8d9133$var$_CacheTimesFormReferField = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("d8eVl")));
parcelRequire.register("bbBbb", function(module, exports) {
"use strict";
Object.defineProperty(module.exports, "__esModule", {
    value: true
});
module.exports.default = void 0;
var $824d8fd9a1855cea$var$_default = {
    notification: 'notification'
};
module.exports.default = $824d8fd9a1855cea$var$_default;

});


var $972e5950cb8d9133$var$_CachePluginQueryParams = $972e5950cb8d9133$var$_interopRequireDefault((parcelRequire("bbBbb")));
function $972e5950cb8d9133$var$_interopRequireDefault(obj) {
    return obj && obj.__esModule ? obj : {
        default: obj
    };
}
(function($) {
    $(document).ready(function() {
        var _WPECachePlugin, _WPECachePlugin2;
        const removeNotificationParamFromPathname = ()=>{
            const windowModifier = new $972e5950cb8d9133$var$_CachePluginWindowModifier.default(window);
            const updatedWindowPath = windowModifier.stripQueryParamFromPathname($972e5950cb8d9133$var$_CachePluginQueryParams.default.notification);
            windowModifier.replaceWindowState(updatedWindowPath);
            const cacheTimesFormReferField = new $972e5950cb8d9133$var$_CacheTimesFormReferField.default();
            cacheTimesFormReferField.replaceRefer(updatedWindowPath);
        };
        const rootPath = wpApiSettings.root; // this root path contains the base api path for the REST Routes
        const nonce = wpApiSettings.nonce; // this is the nonce field
        const clearAllCachesPath = `${rootPath}${WPECachePlugin.clear_all_caches_path}`;
        const lastClearedAt = (_WPECachePlugin = WPECachePlugin) === null || _WPECachePlugin === void 0 ? void 0 : _WPECachePlugin.clear_all_cache_last_cleared;
        const lastErroredAt = (_WPECachePlugin2 = WPECachePlugin) === null || _WPECachePlugin2 === void 0 ? void 0 : _WPECachePlugin2.clear_all_cache_last_cleared_error;
        const cachePluginApiService = new $972e5950cb8d9133$var$_CachePluginApiService.default(nonce, {
            clearAllCachesPath: clearAllCachesPath
        });
        const activeError = lastErroredAt && !$972e5950cb8d9133$var$_DateTime.default.isLastClearedExpired(lastErroredAt);
        const activeLastCleared = lastClearedAt && !$972e5950cb8d9133$var$_DateTime.default.isLastClearedExpired(lastClearedAt);
        const lastErrorText = new $972e5950cb8d9133$var$_LastErrorText.default();
        const errorToast = new $972e5950cb8d9133$var$_ErrorToast.default();
        const lastClearedText = new $972e5950cb8d9133$var$_LastClearedText.default();
        const clearAllCacheBtn = new $972e5950cb8d9133$var$_ClearAllCacheBtn.default(cachePluginApiService);
        const clearCacheIcon = new $972e5950cb8d9133$var$_ClearAllCacheIcon.default();
        removeNotificationParamFromPathname();
        if (activeError) {
            lastErrorText.setLastErrorText(lastErroredAt);
            clearAllCacheBtn.setDisabled();
            clearCacheIcon.setErrorIcon();
        } else if (activeLastCleared) {
            lastClearedText.setLastClearedText(lastClearedAt);
            clearAllCacheBtn.setDisabled();
            clearCacheIcon.setSuccessIcon();
        }
        clearAllCacheBtn.attachSubmit({
            onSuccess: (dateTime)=>{
                lastClearedText.setLastClearedText(dateTime);
                clearCacheIcon.setSuccessIcon();
            },
            onError: (errorTime)=>{
                lastErrorText.setLastErrorText(errorTime);
                clearCacheIcon.setErrorIcon();
                errorToast.showToast();
            }
        });
    });
})(jQuery);


