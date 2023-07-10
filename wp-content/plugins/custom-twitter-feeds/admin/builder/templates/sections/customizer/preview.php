<div class="sb-customizer-preview" :data-preview-device="customizerScreens.previewScreen">
	<?php
		/**
		 * CFF Admin Notices
		 *
		 * @since 2.0
		 */
		do_action('ctf_admin_notices');

		$feed_id = ! empty( $_GET['feed_id'] ) ? (int)$_GET['feed_id'] : 0;
	?>
	<div class="sb-preview-ctn sb-tr-2">
		<div class="sb-preview-top-chooser ctf-fb-fs">
			<strong :class="getModerationShoppableMode == true ? 'ctf-moderate-heading' :''" v-html="getModerationShoppableMode == false ? genericText.preview : ( svgIcons['eyePreview'] + '' + genericText.moderationModePreview )"></strong>
			<div class="sb-preview-chooser" v-if="getModerationShoppableMode == false">
				<button class="sb-preview-chooser-btn" v-for="device in previewScreens" v-bind:class="'sb-' + device" v-html="svgIcons[device]" @click.prevent.default="switchCustomizerPreviewDevice(device)" :data-active="customizerScreens.previewScreen == device"></button>
			</div>
		</div>
        <div class="ctf-preview-empty-ctn ctf-fb-fs"  v-if="customizerFeedData.feed_enabled === false">

        </div>
		<div class="ctf-preview-ctn ctf-fb-fs" v-if="customizerFeedData.feed_enabled !== false">
			<div>
				<component :is="{template}"></component>
			</div>
		</div>

	</div>
	<ctf-dummy-lightbox-component :dummy-light-box-screen="dummyLightBoxScreen" :customizer-feed-data="customizerFeedData"></ctf-dummy-lightbox-component>
</div>
<div class="ctf-preview-disabled sb-fs-boss"  v-if="customizerFeedData.feed_enabled === false">
    <div class="ctf-preview-disabled-content">
        <div class="ctf-preview-disabled-text ctf-disabled-feed-notice" v-html="(checkNotEmpty(customizerFeedData.settings.type) && customizerFeedData.settings.type.toLowerCase() === 'usertimeline') ? genericText.disabledFeedTooltipNotice : genericText.disabledFeedTypeTooltipNotice"></div>
        <div class="ctf-preview-disabled-actions">
            <button class="ctf-fb-btn ctf-btn-grey" @click.prevent.default="window.location = builderUrl"><?php echo __('Back to All Feeds', 'custom-twitter-feeds' ) ?></button>
            <a class="ctf-fb-btn ctf-btn-blue" v-if="(checkNotEmpty(customizerFeedData.settings.type) && customizerFeedData.settings.type.toLowerCase() === 'usertimeline')" target="_blank" href="https://smashballoon.com/custom-twitter-feeds/"><?php echo __('Upgrade to Pro', 'custom-twitter-feeds' ) ?></a>
            <button class="ctf-fb-btn ctf-btn-red" v-if="(checkNotEmpty(customizerFeedData.settings.type) && customizerFeedData.settings.type.toLowerCase() !== 'usertimeline')" @click.prevent.default="feedActionDelete([customizerFeedData.feed_info.id], true)"><?php echo __('Delete Feed', 'custom-twitter-feeds' ) ?></button>
        </div>
    </div>
</div>


