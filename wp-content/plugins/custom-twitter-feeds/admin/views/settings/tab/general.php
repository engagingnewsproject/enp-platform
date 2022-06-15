<div v-if="selected === 'app-1'">
    <div class="sb-tab-box sb-license-box clearfix">
        <div class="tab-label">
            <h3>{{generalTab.licenseBox.title}}</h3>
            <p>{{generalTab.licenseBox.description}}</p>
        </div>
        <div class="ctf-tab-form-field d-flex">
            <div v-if="licenseType === 'free' && licenseStatus == 'valid'" class="ctf-tab-field-inner-wrap ctf-license" :class="['license-' + licenseStatus, 'license-type-' + licenseType, {'form-error': hasError, 'dev-site-license-field': isDevSite}]">
                <div class="upgrade-info">
                    <span v-html="generalTab.licenseBox.upgradeText1"></span>
                    <span v-html="generalTab.licenseBox.upgradeText2"></span>
                </div>
                <span v-if="!isDevSite" class="license-status" v-html="generalTab.licenseBox.freeText"></span>
                <div v-if="isDevSite">
                    <a :href="upgradeUrl" target="_blank" class="ctf-btn ctf-btn-blue ctf-upgrade-license-btn">
                        <span v-html="svgIcons.rocket"></span>
                        {{genericText.upgradeToPro}}
                    </a>
                </div>
                <div v-else class="d-flex">
                    <div class="field-left-content">
                        <div class="sb-form-field">
                            <input type="password" name="license-key" id="license-key" class="ctf-form-field" :placeholder="generalTab.licenseBox.inactiveFieldPlaceholder" v-model="licenseKey">
                        </div>
                        <div class="form-info d-flex justify-between">

                            <span class="ctf-manage-license">
                                <a :href="links.manageLicense">{{generalTab.licenseBox.manageLicense}}</a>
                            </span>
                            <span>
                                <span class="test-connection" @click="testConnection()" v-if="testConnectionStatus === null">
                                    {{generalTab.licenseBox.test}}
                                    <span v-html="testConnectionIcon()" :class="testConnectionStatus">
                                    </span>
                                </span>
                                <span v-html="testConnectionIcon()" class="test-connection"  :class="testConnectionStatus" v-if="testConnectionStatus !== null">
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="field-right-content">
                        <button type="button" class="ctf-btn" v-on:click="licenseActiveAction" :class="{loading: loading, 'sb-btn-blue': licenseType === 'free'}">
                            <span v-if="loading && pressedBtnName === 'ctf'" v-html="loaderSVG"></span>
                            <span class="ctf-license-action-text" v-if="licenseType === 'pro'">{{generalTab.licenseBox.deactivate}}</span>
                            <span class="ctf-license-action-text" v-if="licenseType === 'free'">{{generalTab.licenseBox.installPro}}</span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="ctf-tab-field-inner-wrap ctf-license" :class="['license-' + licenseStatus, 'license-type-' + licenseType, {'form-error': hasError, 'dev-site-license-field': isDevSite}]">
                <div class="upgrade-info">
                    <span v-html="generalTab.licenseBox.upgradeText1"></span>
                    <span v-html="generalTab.licenseBox.upgradeText2"></span>
                </div>
                <span v-if="!isDevSite" class="license-status" v-html="generalTab.licenseBox.freeText"></span>
                <div class="mb-6" v-if="licenseErrorMsg !== null">
                    <span v-html="licenseErrorMsg" class="ctf-error-text"></span>
                </div>
                <div v-if="isDevSite">
                    <a :href="upgradeUrl" target="_blank" class="ctf-btn ctf-btn-blue ctf-upgrade-license-btn">
                        <span v-html="svgIcons.rocket"></span>
                        {{genericText.upgradeToPro}}
                    </a>
                </div>
                <div v-else class="d-flex">
                    <div class="field-left-content">
                        <div class="sb-form-field">
                            <input type="password" name="license-key" id="license-key" class="ctf-form-field" :placeholder="generalTab.licenseBox.inactiveFieldPlaceholder" v-model="licenseKey">
                        </div>
                        <div class="form-info d-flex justify-between">

                            <span class="ctf-manage-license">
                            </span>
                            <span>
                                <span class="test-connection" @click="testConnection()" v-if="testConnectionStatus === null">
                                    {{generalTab.licenseBox.test}}
                                    <span v-html="testConnectionIcon()" :class="testConnectionStatus">
                                    </span>
                                </span>
                                <span v-html="testConnectionIcon()" class="test-connection"  :class="testConnectionStatus" v-if="testConnectionStatus !== null">
                                </span>
                                <span class="ctf-upgrade">
                                    <a :href="upgradeUrl" target="_blank">{{generalTab.licenseBox.upgrade}}</a>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="field-right-content">
                        <button type="button" class="ctf-btn sb-btn-blue" v-on:click="activateLicense">
                            <span v-if="loading && pressedBtnName === 'ctf'" class="ctf-loader-svg" v-html="loaderSVG"></span>
                            {{generalTab.licenseBox.activate}}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="licenseType === 'pro' && licenseStatus === 'valid'" class="ctf-tab-field-inner-wrap ctf-license ctf-license" :class="['license-' + licenseStatus, 'license-type-' + licenseType, {'form-error': hasError}]">
                <span class="license-status" v-html="generalTab.licenseBox.activeText"></span>
                <div class="d-flex">
                    <div class="field-left-content">
                        <div class="sb-form-field">
                            <input type="password" name="license-key" id="license-key" class="ctf-form-field" value="******************************" v-model="licenseKey">
                            <span class="field-icon fa fa-check-circle"></span>
                        </div>
                        <div class="form-info d-flex justify-between">
                            <span class="ctf-manage-license">
                                <a :href="links.manageLicense" target="_blank">{{generalTab.licenseBox.manageLicense}}</a>
                            </span>
                            <span>
                                <span class="test-connection" @click="testConnection()" v-if="testConnectionStatus === null">
                                    {{generalTab.licenseBox.test}}
                                    <span v-html="testConnectionIcon()" :class="testConnectionStatus">
                                    </span>
                                </span>
                                <span v-html="testConnectionIcon()" class="test-connection"  :class="testConnectionStatus" v-if="testConnectionStatus !== null">
                                </span>

                                <span class="ctf-upgrade">
                                    <a :href="upgradeUrl" target="_blank">{{generalTab.licenseBox.upgrade}}</a>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="field-right-content">
                        <button type="button" class="ctf-btn" v-on:click="licenseActiveAction" :class="{loading: loading}">
                            <span v-if="loading && pressedBtnName === 'ctf'" v-html="loaderSVG"></span>
							{{generalTab.licenseBox.activate}}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="licenseType === 'pro' && (
                licenseStatus === 'inactive' ||
                licenseStatus === 'site_inactive' ||
                licenseStatus === 'invalid' ||
                licenseStatus === 'deactivated' ||
                licenseStatus === 'no_activations_left' ||
                licenseStatus === 'expired')"
                 class="ctf-tab-field-inner-wrap ctf-license"
                 :class="['license-' + licenseStatus, 'license-type-' + licenseType, {'form-error': hasError}]">
                <span class="license-status" v-html="generalTab.licenseBox.inactiveText"></span>
                <div class="d-flex">
                    <div class="field-left-content">
                        <div class="sb-form-field">
                            <input type="password" name="license-key" id="license-key" class="ctf-form-field" :placeholder="generalTab.licenseBox.inactiveFieldPlaceholder" v-model="licenseKey">
                            <span class="field-icon field-icon-error fa fa-times-circle" v-if="licenseErrorMsg !== null"></span>
                        </div>
                        <div class="mb-6" v-if="licenseErrorMsg !== null">
                            <span v-html="licenseErrorMsg" class="ctf-error-text"></span>
                        </div>
                        <div class="form-info d-flex justify-between">
                            <span></span>
                            <span>
                                <span class="test-connection" @click="testConnection()" v-if="testConnectionStatus === null">
                                    {{generalTab.licenseBox.test}}
                                    <span v-html="testConnectionIcon()" :class="testConnectionStatus">
                                    </span>
                                </span>
                                <span v-html="testConnectionIcon()" class="test-connection"  :class="testConnectionStatus" v-if="testConnectionStatus !== null">
                                </span>
                                <span class="ctf-upgrade">
                                    <a :href="upgradeUrl" target="_blank">{{generalTab.licenseBox.upgrade}}</a>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="field-right-content">
                        <button type="button" class="ctf-btn sb-btn-blue" v-on:click="activateLicense">
                            <span v-if="loading && pressedBtnName === 'ctf'" v-html="loaderSVG"></span>
                            {{generalTab.licenseBox.activate}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--Manage Sources-->
    <div class="sb-tab-box sb-manage-acount-box clearfix">
        <div class="tab-label">
            <h3>{{generalTab.manageAccount.title}}</h3>
        </div>
        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <span class="help-text" v-html="generalTab.manageAccount.description"></span>
            </div>
            <div class="d-flex ctf-manage-account-inputs" v-if="checkAccountDetails()" :data-active="viewsActive['accountDetailsActive'] ? 'true' : 'false'">
                <div class="field-left-content">
                    <div class="ctf-manage-account-info">
                        <img :src="accountDetails.account_avatar" :alt="accountDetails.account_handle">
                        <strong>{{accountDetails.account_handle}}</strong>
                        <div class="ctf-manage-account-edit-icon" v-html="svgIcons['cog']" @click.prevent.default="activateView('accountDetailsActive')"></div>
                        <div class="ctf-manage-account-delete-icon" v-html="svgIcons['delete']" @click.prevent.default="openDialogBox('deleteAccount')"></div>
                    </div>
                    <div class="ctf-acc-info-item">
                        <strong>{{generalTab.manageAccount.aToken}}</strong>
                        <span>{{accountDetails.access_token}}</span>
                        <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.access_token)"></div>
                    </div>
                    <div class="ctf-acc-info-item">
                        <strong>{{generalTab.manageAccount.aTokenSecret}}</strong>
                        <span>{{accountDetails.access_token_secret}}</span>
                        <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.access_token_secret)"></div>
                    </div>
                </div>
                <button type="button" class="ctf-btn sb-btn-lg export-btn" @click.prevent.default="activateView('connectAccountPopup');switchScreen('connectAccountStep','step_1');">
                    <span class="icon" v-html="svgIcons['edit']"></span>
                    {{generalTab.manageAccount.button}}
                </button>
            </div>
            <div class="d-flex ctf-manage-account-inputs" v-else>
                 <button type="button" class="ctf-newaccount-btn ctf-btn sb-btn-lg sb-btn-blue export-btn" @click.prevent.default="activateView('connectAccountPopup')">
                    <span class="icon" v-html="svgIcons['twitter']"></span>
                    {{generalTab.manageAccount.buttonConnect}}
                </button>
            </div>
        </div>
        <div class="ctf-add-account ctf-fs">
            <div class="ctf-add-account-btn ctf-fs"  v-if="!checkAppData()" @click.prevent.default="activateView('connectAccountPopup');switchScreen('connectAccountStep','step_2');">
                <div v-html="svgIcons['linkIcon']"></div>
                {{generalTab.manageAccount.buttonConnectOwnApp}}
            </div>

            <div class="ctf-add-account-info ctf-fs" v-else>
                <div class="tab-label">
                    <h3>{{generalTab.manageAccount.titleApp}}</h3>
                </div>
                <div class="ctf-tab-form-field">
                    <div class="sb-form-field"></div>
                    <div class="d-flex ctf-manage-account-inputs-info" :data-active="viewsActive['appDetailsActive'] ? 'true' : 'false'">
                        <div class="field-left-content">
                            <div class="ctf-manage-account-info">
                                <div class="ctf-manage-account-info-icon" v-html="svgIcons['twitter']"></div>
                                <strong>{{accountDetails.app_name}}</strong>
                                <div class="ctf-manage-account-edit-icon" v-html="svgIcons['cog']" @click.prevent.default="activateView('appDetailsActive')"></div>
                                <div class="ctf-manage-account-delete-icon" v-html="svgIcons['delete']" @click.prevent.default="openDialogBox('deleteApp')"></div>
                            </div>
                            <div class="ctf-acc-info-item">
                                <strong>{{generalTab.manageAccount.cKey}}</strong>
                                <span>{{accountDetails.consumer_key}}</span>
                                <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.consumer_key)"></div>
                            </div>
                            <div class="ctf-acc-info-item">
                                <strong>{{generalTab.manageAccount.cSecret}}</strong>
                                <span>{{accountDetails.consumer_secret}}</span>
                                <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.consumer_secret)"></div>
                            </div>
                            <div class="ctf-acc-info-item">
                                <strong>{{generalTab.manageAccount.aToken}}</strong>
                                <span>{{accountDetails.access_token}}</span>
                                <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.access_token)"></div>
                            </div>
                            <div class="ctf-acc-info-item">
                                <strong>{{generalTab.manageAccount.aTokenSecret}}</strong>
                                <span>{{accountDetails.access_token_secret}}</span>
                                <div class="ctf-acc-info-icon" v-html="svgIcons['copy2']" @click.prevent.default="copyToClipBoard(accountDetails.access_token_secret)"></div>
                            </div>
                        </div>
                        <button type="button" class="ctf-btn sb-btn-lg export-btn" @click.prevent.default="activateView('connectAccountPopup');switchScreen('connectAccountStep','step_2');">
                            <span class="icon" v-html="svgIcons['edit']"></span>
                            {{generalTab.manageAccount.button}}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <div class="sb-tab-box sb-preserve-settings-box clearfix">
        <div class="tab-label">
            <h3>{{generalTab.preserveBox.title}}</h3>
        </div>

        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <label for="preserve-settings" class="ctf-checkbox">
                    <input type="checkbox" name="preserve-settings" id="preserve-settings" v-model="model.general.preserveSettings">
                    <span class="toggle-track">
                        <div class="toggle-indicator"></div>
                    </span>
                </label>
                <span class="help-text">
                    {{generalTab.preserveBox.description}}
                </span>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-import-box sb-reset-box-style clearfix">
        <div class="tab-label">
            <h3>{{generalTab.importBox.title}}</h3>
        </div>
        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <div class="d-flex mb-15">
                    <button type="button" class="ctf-btn sb-btn-lg import-btn" id="import-btn" @click="importFile" :disabled="uploadStatus !== null">
                        <span class="icon" v-html="importBtnIcon()" :class="uploadStatus"></span>
                        {{generalTab.importBox.button}}
                    </button>
                    <div class="input-hidden">
                        <input id="import_file" type="file" value="import_file" ref="file" v-on:change="uploadFile">
                    </div>
                </div>
                <span class="help-text">
                    {{generalTab.importBox.description}}
                </span>
            </div>
        </div>
    </div>

    <div class="sb-tab-box sb-export-box clearfix">
        <div class="tab-label">
            <h3>{{generalTab.exportBox.title}}</h3>
        </div>
        <div class="ctf-tab-form-field">
            <div class="sb-form-field">
                <div class="d-flex mb-15">
                    <select name="" id="ctf-feeds-list" class="ctf-select" v-model="exportFeed" ref="export_feed">
                        <option value="none" selected disabled>Select Feed</option>
                        <option v-for="feed in feeds" :value="feed.id">{{ feed.name }}</option>
                    </select>
                    <button type="button" class="ctf-btn sb-btn-lg export-btn" @click="exportFeedSettings" :disabled="exportFeed === 'none'">
                        <span class="icon" v-html="exportSVG"></span>
                        {{generalTab.exportBox.button}}
                    </button>
                </div>
                <span class="help-text">
                    {{generalTab.exportBox.description}}
                </span>
            </div>
        </div>
    </div>
</div>
<?php include_once CTF_BUILDER_DIR . 'templates/sections/popup/connect-account-popup.php'; ?>
