<h3>
	<?php echo esc_html__('WHY ARE YOU COLLECTING CUSTOMER DATA?', 'googleanalytics'); ?>
</h3>

<div id="publisher-purpose" class="switch">
	<div class="empty-choices col-md-12">
		<a id="see-st-choices" class="st-rc-link medium-btn col-md-6" href="#">See Suggested Choices</a>
		<a id="clear-choices" class="st-rc-link medium-btn col-md-6" href="#">Clear Choices</a>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'1) Store and/or access information on a device (Do you collect information on users on your site through cookies or site identifiers?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="1" type="checkbox" name="purposes[1]" value="consent" checked/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'2) Select basic ads (Do you serve ads on your site?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="2" type="radio" name="purposes[2]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="2" type="radio" name="purposes[2]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'3) Create a personalised ads profile (Do you create personalised advertising profiles associated with users on your site (ie: profiles based on demographic information, location, user’s activity)?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="3" type="radio" name="purposes[3]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="3" type="radio" name="purposes[3]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'4) Select personalised ads (Do you show ads to users based on this user profile)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="4" type="radio" name="purposes[4]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="4" type="radio" name="purposes[4]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'5) Create a personalised content profile (Do you build a personalized content profile associated with users on your site  based on the type of content they have viewed?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="5" type="radio" name="purposes[5]" value="consent" checked />
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="5" type="radio" name="purposes[5]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'6) Select personalised content (Do you serve content to the user on your site based on your recorded content interests)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="6" type="radio" name="purposes[6]" value="consent" checked />
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="6" type="radio" name="purposes[6]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'7) Measure ad performance (Do you measure the performance of advertisements on your site)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="7" type="radio" name="purposes[7]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="7" type="radio" name="purposes[7]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'8) Measure content performance  (Do you measure the performance of content served to your site visitors?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="8" type="radio" name="purposes[8]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="8" type="radio" name="purposes[8]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'9) Apply market research to generate audience insights (Do you aggregate reporting on the ads or content show to your site visitors to advertisers)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="9" type="radio" name="purposes[9]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="9" type="radio" name="purposes[9]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
	<div class="purpose-item">
		<div class="title">
			<?php echo esc_html__(
				'10) Develop and improve products (Do you use data collected on your site visitors to improve your systems or software or create new products?)',
				'googleanalytics'
			); ?>
		</div>
		<label>
			<?php echo esc_html__('Consent', 'googleanalytics'); ?>
			<input data-id="10" type="radio" name="purposes[10]" value="consent" checked/>
			<span class="lever"></span>
		</label>
		<label>
			<?php echo esc_html__('Legitimate Interest', 'googleanalytics'); ?>
			<input data-id="10" type="radio" name="purposes[10]" value="legitimate"/>
			<span class="lever"></span>
		</label>
	</div>
</div>
