<div v-if="selected === 'app-4'">
    <div class="sb-tab-box sb-resizing-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.optimizeBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="resizing-settings" class="ctf-checkbox">
                    <input type="checkbox" id="resizing-settings" value="false" :disabled="licenseType == 'free'">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                    <button type="button" class="ctf-btn ml-10 optimize-image-btn" @click="clearImageResizeCache()" :disabled="licenseType == 'free'">
                        <span v-html="clearImageResizeCacheIcon()" :class="optimizeCacheStatus" v-if="optimizeCacheStatus !== null"></span>
                        {{advancedTab.optimizeBox.reset}}
                    </button>
                </label>
                <span class="help-text">
                    {{advancedTab.optimizeBox.helpText}}
                </span>
            </div>
        </div>
        <div v-if="licenseType == 'free'" class="ctf-caching-pro-cta clearfix">
            <span>
                <a :href="links.optimizeImagesLink" target="_blank">{{advancedTab.optimizeBox.promoText}}
                    <span class="ctf-upgrade-cta-icon">
                        <svg width="7" height="10" viewBox="0 0 7 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.8332 0L0.658203 1.175L4.47487 5L0.658203 8.825L1.8332 10L6.8332 5L1.8332 0Z" fill="#0068A0"/>
                        </svg>
                    </span>
                </a>
            </span>
        </div>
    </div>
    <div class="sb-tab-box sb-persistentCacheBox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.persistentCacheBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="persistentcache-settings" class="ctf-checkbox">
                    <input type="checkbox" id="persistentcache-settings" v-model="model.advanced.persistentcache">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                     <button type="button" class="ctf-btn ml-10 persistent-cache-btn" @click="clearPersistentCache()">
                        <span v-html="clearPersistentCacheIcon()" :class="persistentCacheStatus" v-if="persistentCacheStatus !== null"></span>
                        {{advancedTab.persistentCacheBox.reset}}
                    </button>
                </label>
                <span class="help-text">
                    {{advancedTab.persistentCacheBox.helpText}}
                </span>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-ajaxThemeBox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.ajaxThemeBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="ajax_theme-settings" class="ctf-checkbox">
                    <input type="checkbox" id="ajax_theme-settings" v-model="model.advanced.ajax_theme">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{advancedTab.ajaxThemeBox.helpText}}
                </span>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-templatesBox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.templatesBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="customtemplates-settings" class="ctf-checkbox">
                    <input type="checkbox" id="customtemplates-settings" v-model="model.advanced.customtemplates">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text" v-html="advancedTab.templatesBox.helpText">
                </span>
            </div>
        </div>
    </div>
    <div class="sb-tab-box sb-creditbox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.creditbox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="creditctf-settings" class="ctf-checkbox">
                    <input type="checkbox" id="creditctf-settings" v-model="model.advanced.creditctf">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{advancedTab.creditbox.helpText}}
                </span>
            </div>
        </div>
    </div>
    <div class="sb-tab-box sb-resbox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.resbox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="autores-settings" class="ctf-checkbox">
                    <input type="checkbox" id="autores-settings" v-model="model.advanced.autores">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{advancedTab.resbox.helpText}}
                </span>
            </div>
        </div>
    </div>
    <div class="sb-tab-box sb-intentBox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.intentBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="enableintents-settings" class="ctf-checkbox">
                    <input type="checkbox" id="enableintents-settings" v-model="model.advanced.enableintents">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{advancedTab.intentBox.helpText}}
                </span>
            </div>
        </div>
    </div>
    <div class="sb-tab-box sb-requestMethodBox-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.requestMethodBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <div class="d-flex mb-10">
                    <select id="ctf-send-report" class="ctf-select size-md" v-model="model.advanced.request_method">
                        <option v-for="(name, key) in advancedTab.requestMethodBox.options" :value="key">{{name}}</option>
                    </select>
                </div>
                <span class="help-text">
                    {{advancedTab.requestMethodBox.helpText}}
                </span>
            </div>
        </div>
    </div>
    <div class="sb-tab-box sb-clearCacheBox-box clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.clearCacheBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="cron_cache_clear-settings" class="ctf-checkbox">
                    <input type="checkbox" id="cron_cache_clear-settings" v-model="model.advanced.cron_cache_clear">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{advancedTab.clearCacheBox.helpText}}
                </span>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-twittercard-box clearfix">
        <div class="tab-label">
            <h3>{{advancedTab.twittercard.title}}</h3>
            <span>{{advancedTab.twittercard.description}}</span>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <div class="sb-setting-promo-banner">
                    <div class="sb-setting-promo-img" v-html="svgIcons[advancedTab.twittercard.promoIcon]"></div>
                    <div class="sb-setting-promo-content">
                        <h5 v-html="advancedTab.twittercard.promoTitle"></h5>
                        <div v-html="advancedTab.twittercard.promoLink"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
