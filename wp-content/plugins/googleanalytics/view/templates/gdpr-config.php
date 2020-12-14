<?php
/**
 * Appearence display for gdpr config.
 */

// Template vars.
$colors = [
	'#e31010',
	'#000000',
	'#ffffff',
	'#09cd18',
	'#ff6900',
	'#fcb900',
	'#7bdcb5',
	'#00d084',
	'#8ed1fc',
	'#0693e3',
	'#abb8c3',
	'#eb144c',
	'#f78da7',
	'#9900ef',
	'#b80000',
	'#db3e00',
	'#fccb00',
	'#008b02',
	'#006b76',
	'#1273de',
	'#004dcf',
	'#5300eb',
	'#eb9694',
	'#fad0c3',
	'#fef3bd',
	'#c1e1c5',
	'#bedadc',
	'#c4def6',
	'#bed3f3',
	'#d4c4fb'
];


// User type options.
$user_types = array(
	'eu'     => esc_html__('Only visitors in the EU', 'sharethis-custom'),
	'always' => esc_html__('All visitors globally', 'sharethis-custom'),
);

// Consent type options.
$consent_types = array(
	'global'    => esc_html__(
		'Global: Publisher consent = 1st party cookie; Vendors consent = 3rd party cookie',
		'sharethis-custom'
	),
	'publisher' => esc_html__(
		'Service: publisher consent = 1st party cookie; Vendors consent = 1st party cookie',
		'sharethis-custom'
	),
);

$languages = array(
	'English'    => 'en',
	'German'     => 'de',
	'Spanish'    => 'es',
	'French'     => 'fr'
);

$publisher_name = !empty($gdpr_config['publisher_name']) ? $gdpr_config['publisher_name'] : '';
$enabled = !empty($gdpr_config['enabled']) ? $gdpr_config['enabled'] : false;
?>
<div id="adblocker-notice" class="notice notice-error is-dismissible">
	<p>
		<?php echo esc_html__( 'It appears you have an ad blocker enabled. To avoid affecting this plugin\'s functionality, please disable while using its admin configurations and registrations. Thank you.', 'sharethis-share-buttons' ); ?>
	</p>
</div>
<div id="detectadblock">
	<div class="adBanner">
	</div>
</div>
<div class="gdpr-platform">
	<div class="switch">
		<div class="purpose-item">
			<label class="enable-tool">
				<?php echo esc_html__('Enable GDPR', 'googleanalytics'); ?>
				<input type="checkbox" name="gdpr-enable" <?php echo checked('true', $enabled); ?>/>
				<span class="lever"></span>
			</label>
		</div>
	</div>
	<div class="well">
		<label class="control-label">
			<?php echo esc_html__('PUBLISHER NAME * (this will be displayed in the consent tool)',
				'sharethis-share-buttons'); ?>
		</label>
		<div class="input-div">
			<input type="text" id="sharethis-publisher-name" placeholder="Enter your company name" value="<?php echo esc_attr($publisher_name); ?>">
		</div>
		<label class="control-label">
			<?php echo esc_html__('WHICH USERS SHOULD BE ASKED FOR CONSENT?',
				'sharethis-share-buttons'); ?>
		</label>
		<div class="input-div">
			<select id="sharethis-user-type">
				<?php foreach ($user_types as $user_value => $name) : ?>
					<option value="<?php echo esc_attr($user_value); ?>" <?php echo isset($gdpr_config['display']) ? selected($user_value, $gdpr_config['display']) : ''; ?>>
						<?php echo esc_html($name); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<label class="control-label">
			<?php echo esc_html__('CONSENT SCOPE', 'sharethis-share-buttons'); ?>
		</label>
		<div class="input-div">
			<select id="sharethis-consent-type">
				<?php foreach ($consent_types as $consent_value => $c_name) : ?>
					<option
						value="<?php echo esc_attr($consent_value); ?>"
						<?php echo isset($gdpr_config['scope']) ? selected($consent_value, $gdpr_config['scope']) : ''; ?>
					>
						<?php echo esc_html($c_name); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<label class="control-label">
			<?php echo esc_html__('SELECT LANGUAGE', 'sharethis-share-buttons'); ?>
		</label>
		<div class="input-div">
			<select id="st-language">
				<?php foreach ($languages as $language => $code) : ?>
					<option value="<?php echo esc_attr($code); ?>" <?php echo isset($gdpr_config['language']) ?  selected($code, $gdpr_config['language']) : ''; ?>>
						<?php echo esc_html($language); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<div class="accor-wrap">
		<div class="accor-tab">
			<span class="accor-arrow">&#9658;</span>
			<?php echo esc_html__( 'Appearance', 'simple-share-buttons-adder' ); ?>
		</div>
		<div class="accor-content">
			<div class="well">
				<?php include plugin_dir_path(__FILE__) . 'appearance.php'; ?>
			</div>
		</div>
	</div>
	<div class="accor-wrap">
		<div class="accor-tab">
			<span class="accor-arrow">&#9658;</span>
			<?php echo esc_html__( 'Purposes', 'simple-share-buttons-adder' ); ?>
		</div>
		<div class="accor-content">
			<div class="well">
				<?php include plugin_dir_path(__FILE__) . 'purposes.php'; ?>
			</div>
		</div>
	</div>
	<div class="accor-wrap">
		<div class="accor-tab">
			<span class="accor-arrow">&#9658;</span>
			<?php echo esc_html__( 'Exclusions', 'googleanalytics' ); ?>
		</div>
		<div class="accor-content">
			<div class="well">
				<?php include plugin_dir_path(__FILE__) . 'exclusions.php'; ?>
			</div>
		</div>
	</div>
</div>
<div class="gdpr-submit-button">
	<button class="gdpr-submit">Update</button>
</div>
