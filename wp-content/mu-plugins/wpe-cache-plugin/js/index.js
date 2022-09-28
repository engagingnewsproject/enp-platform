import LastClearedText from './components/LastClearedText';
import LastErrorText from './components/LastErrorText';
import ErrorToast from './components/ErrorToast';
import ClearAllCacheBtn from './components/ClearAllCacheBtn';
import ClearAllCacheIcon from './components/ClearAllCacheIcon';
import CachePluginApiService from './services/CachePluginApiService';
import DateTime from './utils/DateTime';
import CachePluginWindowModifier from './services/CachePluginWindowModifier';
import CacheTimesFormReferField from './components/CacheTimesFormReferField';
import QueryParameters from './services/CachePluginQueryParams';

(function ($) {
    'use strict';
    $(document).ready(function () {
        const removeNotificationParamFromPathname = () => {
            const windowModifier = new CachePluginWindowModifier(window);
            const updatedWindowPath =
                windowModifier.stripQueryParamFromPathname(
                    QueryParameters.notification
                );
            windowModifier.replaceWindowState(updatedWindowPath);

            const cacheTimesFormReferField = new CacheTimesFormReferField();
            cacheTimesFormReferField.replaceRefer(updatedWindowPath);
        };

        const getPreviousCacheClearResult = (
            mostRecentRateLimitedDate,
            lastClearedAt
        ) => {
            return mostRecentRateLimitedDate.getTime() ===
                new Date(Date.parse(lastClearedAt)).getTime()
                ? 'success'
                : 'error';
        };

        const updateUIWithPreviousCacheClearResult = (
            previousCacheClearResult
        ) => {
            if (previousCacheClearResult === 'error') {
                clearCacheIcon.setErrorIcon();
                lastErrorText.setLastErrorText(mostRecentRateLimitedDate);
            } else {
                clearCacheIcon.setSuccessIcon();
                lastClearedText.setLastClearedText(mostRecentRateLimitedDate);
            }
        };

        const rootPath = wpApiSettings.root; // this root path contains the base api path for the REST Routes
        const nonce = wpApiSettings.nonce; // this is the nonce field
        const clearAllCachesPath = `${rootPath}${WPECachePlugin.clear_all_caches_path}`;
        const lastClearedAt = WPECachePlugin?.clear_all_cache_last_cleared;
        const lastErroredAt =
            WPECachePlugin?.clear_all_cache_last_cleared_error;
        const cachePluginApiService = new CachePluginApiService(nonce, {
            clearAllCachesPath,
        });

        const lastErrorText = new LastErrorText();
        const errorToast = new ErrorToast();
        const lastClearedText = new LastClearedText();
        const clearAllCacheBtn = new ClearAllCacheBtn(cachePluginApiService);
        const clearCacheIcon = new ClearAllCacheIcon();

        removeNotificationParamFromPathname();

        const mostRecentRateLimitedDate = DateTime.mostRecentRateLimitedDate(
            lastErroredAt,
            lastClearedAt
        );
        const maxCDNEnabled = WPECachePlugin.max_cdn_enabled === '1';
        if (mostRecentRateLimitedDate) {
            updateUIWithPreviousCacheClearResult(
                getPreviousCacheClearResult(
                    mostRecentRateLimitedDate,
                    lastClearedAt
                )
            );
            if (maxCDNEnabled) {
                clearAllCacheBtn.setDisabled();
            }
        }

        clearAllCacheBtn.attachSubmit({
            onSuccess: (dateTime) => {
                lastErrorText.hide();
                lastClearedText.setLastClearedText(dateTime);
                clearCacheIcon.setSuccessIcon();
                errorToast.hideToast();
            },
            onError: (errorTime) => {
                lastClearedText.hide();
                lastErrorText.setLastErrorText(errorTime);
                clearCacheIcon.setErrorIcon();
                errorToast.showToast();
            },
            maxCDNEnabled,
        });
    });
})(jQuery);
