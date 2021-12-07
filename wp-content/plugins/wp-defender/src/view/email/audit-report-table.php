<table class="wrapper main" align="center"
       style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
    <tbody>
    <tr style="padding: 0; text-align: left; vertical-align: top;">
        <td class="wrapper-inner main-inner"
            style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 40px; text-align: left; vertical-align: top; word-wrap: break-word;">

            <table class="main-intro"
                   style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                <tbody>
                <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <td class="main-intro-content"
                        style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                        <h3 style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 32px; font-weight: normal; line-height: 32px; margin: 0; margin-bottom: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;"><?php printf( __( "Hi %s,", 'wpdef' ), $name ) ?></h3>
                        <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
							<?php printf( __( "It's WP Defender here, reporting from the frontline with a quick update on what's been happening at <a href=\"%s\">%s</a>.", 'wpdef' ), network_site_url(), network_site_url() ) ?></p>
                    </td>
                </tr>
                </tbody>
            </table>

            <table class="results-list"
                   style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                <thead class="results-list-header" style="border-bottom: 2px solid #ff5c28;">
                <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <th class="result-list-label-title"
                        style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left; width: 35%;">
						<?php _e( "Event Type", 'wpdef' ) ?>
                    </th>
                    <th class="result-list-data-title"
                        style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left;">
						<?php _e( "Action Summaries", 'wpdef' ) ?>
                    </th>
                </tr>
                </thead>
                <tbody class="results-list-content">
				<?php $count = 0; ?>
				<?php foreach ( $list as $key => $row ): ?>
                    <tr style="padding: 0; text-align: left; vertical-align: top;">
						<?php if ( $count == 0 ) {
							$style = '-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;';
						} else {
							$style = '-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;';
						} ?>
                        <td class="result-list-label bordered"
                            style="<?php echo $style ?>">
							<?php echo ucfirst( $key ) ?>
                        </td>
                        <td class="result-list-data bordered"
                            style="<?php echo $style ?>">
							<span style="display: inline-block; font-weight: 400; width: 100%;">
                                                <?php echo $row ?>
                            </span>
                        </td>
                    </tr>
					<?php $count ++; ?>
				<?php endforeach; ?>
                </tbody>
                <tfoot class="results-list-footer">
                <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <td colspan="2"
                        style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 10px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                        <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
                            <a class="plugin-brand"
                               href="<?php echo $logs_url ?>"
                               style="Margin: 0; color: #ff5c28; display: inline-block; font: inherit; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none;"><?php _e( "You can view the full audit report for your site here.", 'wpdef' ) ?>
                                <img
                                        class="icon-arrow-right"
                                        src="<?php echo defender_asset_url( '/assets/email-images/icon-arrow-right-defender.png' ) ?>"
                                        alt="Arrow"
                                        style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: inline-block; margin: -2px 0 0 5px; max-width: 100%; outline: none; text-decoration: none; vertical-align: middle; width: auto;"></a>
                        </p>
                    </td>
                </tr>
                </tfoot>
            </table>
            <table class="main-signature"
                   style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
                <tbody>
                <tr style="padding: 0; text-align: left; vertical-align: top;">
                    <td class="main-signature-content"
                        style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
                        <p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
                            Stay safe,</p>
                        <p class="last-item"
                           style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0; text-align: left;">
                            WP Defender <br><strong>WPMU DEV Security Hero</strong></p>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>