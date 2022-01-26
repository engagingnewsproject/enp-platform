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

        const rootPath = wpApiSettings.root; // this root path contains the base api path for the REST Routes
        const nonce = wpApiSettings.nonce; // this is the nonce field
        const clearAllCachesPath = `${rootPath}${WPECachePlugin.clear_all_caches_path}`;
        const lastClearedAt = WPECachePlugin?.clear_all_cache_last_cleared;
        const lastErroredAt =
            WPECachePlugin?.clear_all_cache_last_cleared_error;
        const cachePluginApiService = new CachePluginApiService(nonce, {
            clearAllCachesPath,
        });
        const activeError =
            lastErroredAt && !DateTime.isLastClearedExpired(lastErroredAt);
        const activeLastCleared =
            lastClearedAt && !DateTime.isLastClearedExpired(lastClearedAt);

        const lastErrorText = new LastErrorText();
        const errorToast = new ErrorToast();
        const lastClearedText = new LastClearedText();
        const clearAllCacheBtn = new ClearAllCacheBtn(cachePluginApiService);
        const clearCacheIcon = new ClearAllCacheIcon();

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
            onSuccess: (dateTime) => {
                lastClearedText.setLastClearedText(dateTime);
                clearCacheIcon.setSuccessIcon();
            },
            onError: (errorTime) => {
                lastErrorText.setLastErrorText(errorTime);
                clearCacheIcon.setErrorIcon();
                errorToast.showToast();
            },
        });
    });
})(jQuery);
