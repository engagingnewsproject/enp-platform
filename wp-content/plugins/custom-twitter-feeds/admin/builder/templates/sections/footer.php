<div class="ctf-stck-wdg" v-if="viewsActive.pageScreen !== 'selectFeed' && ! iscustomizerScreen" :data-active="checkActiveView('footerWidget')">
	<?php
		$smashballoon_info = TwitterFeed\Builder\CTF_Feed_Builder::get_smashballoon_info();
	?>
	<div class="ctf-stck-pop">

		<div class="ctf-stck-el ctf-stck-el-upgrd ctf-fb-fs sb-btn-orange">
			<div class="ctf-stck-el-icon"><?php echo $icons[$smashballoon_info['upgrade']['icon']] ?></div>
			<div class="ctf-stck-el-txt sb-small-p sb-bold" style="color: #fff;"><?php echo __('Upgrade to Pro','custom-twitter-feeds') ?></div>
            <div class="ctf-chevron">
                <svg width="7" height="10" viewBox="0 0 7 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1.3332 0L0.158203 1.175L3.97487 5L0.158203 8.825L1.3332 10L6.3332 5L1.3332 0Z" fill="white"/>
                </svg>
            </div>
            <a :href="links.popup.allAccessBundle" target="_blank" class="ctf-fs-a"></a>
		</div>

		<div class="ctf-stck-title ctf-fb-fs sb-small-p sb-bold sb-dark-text"><?php echo __('Our Feeds for other platforms','custom-twitter-feeds') ?></div>

		<div class="ctf-stck-el-list ctf-fb-fs">
			<?php foreach ($smashballoon_info['platforms'] as $platform): ?>
				<div class="ctf-stck-el ctf-fb-fs">

					<div class="ctf-stck-el-icon" style="color:<?php echo $smashballoon_info['colorSchemes'][$platform['icon']] ?>;"><?php echo $icons[$platform['icon']] ?></div>
					<div class="ctf-stck-el-txt sb-small-text sb-small-p sb-dark-text"><?php echo $platform['name'] ?></div>
                    <div class="ctf-chevron">
                        <svg width="7" height="10" viewBox="0 0 7 10" fill="#8C8F9A" xmlns="http://www.w3.org/2000/svg"><path d="M1.3332 0L0.158203 1.175L3.97487 5L0.158203 8.825L1.3332 10L6.3332 5L1.3332 0Z" fill="#8C8F9A"></path></svg>
                    </div>
					<a href="<?php echo esc_url($platform['link'] ) ?>" target="_blank" rel="nofollow noopener" class="ctf-fs-a"></a>
				</div>
			<?php endforeach ?>
		</div>
		<div class="ctf-stck-follow ctf-fb-fs">
			<span><?php echo __('Follow Us','custom-twitter-feeds') ?></span>
			<div class="ctf-stck-flw-links">
				<?php foreach ($smashballoon_info['socialProfiles'] as $social_key => $social): ?>
					<a href="<?php echo esc_url($social); ?>" data-type="<?php echo $social_key ?>" target="_blank" rel="nofollow noopener" style="color:<?php echo $smashballoon_info['colorSchemes'][$social_key] ?>;"><?php echo $icons[$social_key] ?></a>
				<?php endforeach ?>
			</div>
		</div>
	</div>
	<div class="ctf-stck-wdg-btn" @click.prevent.default="activateView('footerWidget')">
		<?php echo $icons['smash']; ?>
		<div class="ctf-stck-wdg-btn-cls">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M14.501 1.77279L13.091 0.362793L7.50098 5.95279L1.91098 0.362793L0.500977 1.77279L6.09098 7.36279L0.500977 12.9528L1.91098 14.3628L7.50098 8.77279L13.091 14.3628L14.501 12.9528L8.91098 7.36279L14.501 1.77279Z" fill="#141B38"/>
            </svg>
        </div>
	</div>
</div>
<?php
	#include_once CTF_BUILDER_DIR . 'templates/sections/popup/add-source-popup.php';
	#include_once CTF_BUILDER_DIR . 'templates/sections/popup/sources-list-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/connect-account-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/extensions-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtypes-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtypes-customizer-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/confirm-dialog-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/embed-popup.php';
    include_once CTF_BUILDER_DIR . 'templates/sections/popup/onboarding-popup.php';
    include_once CTF_BUILDER_DIR . 'templates/sections/popup/onboarding-customizer-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/install-plugin-popup.php';
	include_once CTF_BUILDER_DIR . 'templates/sections/popup/feedtemplates-popup.php';
?>
<div class="sb-notification-ctn" :data-active="notificationElement.shown" :data-type="notificationElement.type">
	<div class="sb-notification-icon" v-html="svgIcons[notificationElement.type+'Notification']"></div>
	<span class="sb-notification-text" v-html="notificationElement.text"></span>
</div>

<div class="sb-full-screen-loader" :data-show="fullScreenLoader ? 'shown' :  'hidden'">
	<div class="sb-full-screen-loader-logo">
		<div class="sb-full-screen-loader-spinner"></div>
		<div class="sb-full-screen-loader-img" v-html="svgIcons['smash']"></div>
	</div>
	<div class="sb-full-screen-loader-txt">
		Loading...
	</div>
</div>



<sb-confirm-dialog-component
:dialog-box.sync="dialogBox"
:source-to-delete="sourceToDelete"
:svg-icons="svgIcons"
:parent-type="'builder'"
:generic-text="genericText"
></sb-confirm-dialog-component>

<!--
<sb-add-source-component
:sources-list="sourcesList"
:select-source-screen="selectSourceScreen"
:views-active="viewsActive"
:generic-text="genericText"
:selected-feed="selectedFeed"
:svg-icons="svgIcons"
:links="links"
ref="addSourceRef"
>
</sb-add-source-component>
-->
<install-plugin-popup
:views-active="viewsActive"
:generic-text="genericText"
:svg-icons="svgIcons"
:plugins="plugins[viewsActive.installPluginModal]"
>
</install-plugin-popup>
