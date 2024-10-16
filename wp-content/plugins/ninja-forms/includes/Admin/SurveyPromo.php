<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * NF_Survey_Promo Class
 *
 * @since 3.6
 */

class NF_Admin_SurveyPromo
{
    public $isDashboard = false;

    /**
	 *
	 */
	public function __construct()
	{
		if ( ! $this->isTargetPage() ) {
			return;
		}

        if ( array_key_exists( 'nf-dismiss-survey-notice', $_REQUEST ) ) {
			$this->dismiss();
		}
	}

    public function show()
    {
		if ( ! $this->isTargetPage() ) {
			return;
		}
        if ( ! $this->shouldShow() ) {
            return;
        }
        $this->getNoticeHtml();
    }

	public function isTargetPage()
	{
		if ( ! is_admin() ) {
			return false;
		}

        global $pagenow;
        $show = false;

        if( 'edit.php' == $pagenow && \array_key_exists( 'post_type', $_REQUEST ) && 'nf_sub' == $_REQUEST[ 'post_type' ] ) {
            $show = true;
        }

        if( \array_key_exists( 'page', $_REQUEST ) ) {
            $targets = ['nf-import-export', 'nf-submissions', 'nf-settings'];
            if( in_array( $_REQUEST['page'], $targets ) ) {
                $show = true;
            } elseif( $_REQUEST['page'] == 'ninja-forms' && ! \array_key_exists('form_id', $_REQUEST) ) {
                $show = true;
                $this->isDashboard = true;
            }
        }

        return $show;
	}

	/**
	 * Check if we should show the survey promo
	 *
	 * @return bool
	 */
	public function shouldShow()
	{

		$nf_settings = get_option( 'ninja_forms_settings' );

		if (
			isset( $nf_settings[ 'disable_admin_notices' ] ) &&
			$nf_settings[ 'disable_admin_notices' ] == 1
		) {
			return false;
		}

        if ( get_option('ninja_forms_disable_survey_promo') ) {
            return false;
        }

		if ( get_transient('ninja_forms_disable_survey_promo') ) {
			return false;
		}

		return true;
	}

    public function isDashboard()
    {
        return $this->isDashboard;
    }

	/**
	 * Set the ninja_forms_disable_survey_promo transient
	 *
	 * @return Void
	 */
	public function dismiss()
	{
        if( $_REQUEST['nf-dismiss-survey-notice'] == 'now' ) {
		    set_transient('ninja_forms_disable_survey_promo', 1, DAY_IN_SECONDS * 14);
        } elseif( $_REQUEST['nf-dismiss-survey-notice'] == 'always' ) {
            add_option('ninja_forms_disable_survey_promo', 1, '', false);
        }
	}

	/**
	 * Echo the html for the notice
	 *
	 * @return Void
	 */
	public function getNoticeHtml()
	{
        $dismissSurveyNoticeHtmlNow = esc_url(add_query_arg('nf-dismiss-survey-notice', 'now'));

        $dismissSurveyNoticeHtmlAlways =esc_url(add_query_arg('nf-dismiss-survey-notice', 'always') );
        
		$html = '<div id="nf-top-notice">
        <div class="nf-top-inner">
            <div class="nf-survey-actions"><a class="nf-survey-btn" href="https://ninjaforms.com/core-plugin-survey/?utm_source=Ninja+Forms+Plugin&utm_medium=Admin+Banner&utm_campaign=Core+Plugin+Survey" target="_blank">Win a $100 Amazon gift card!</a>
                <p class="nf-survey-alt-actions"><a class="nf-survey-remind" href="' . $dismissSurveyNoticeHtmlNow . '">' . esc_html__('Remind me later', 'ninja-forms') . '</a><span><a class="nf-survey-hide" href="' . $dismissSurveyNoticeHtmlAlways . '">' . esc_html__('Dismiss forever', 'ninja-forms') . '</a></span></p>
            </div>
            <div class="nf-survey-content">
                <p>' . esc_html__("Learning more about why you're using Ninja Forms helps us shape the future of the plugin to best match your needs. That's really important to us, and why we're offering a chance to win prizes like a $100 Amazon gift card for just a few minutes of your time.", 'ninja-forms') . '</p>
                <p><strong>' . esc_html__("If you have five minutes to spare, we'd love your feedback on this brief, multiple-choice survey.", 'ninja-forms') . '</strong></p>
            </div>
        </div>
    </div>
    <style type="text/css">
        #nf-top-notice {
        width: 100%;
        border-bottom: 2px solid #97C9ED;
        color: #424242;
        font-size: 14px;
        font-weight: normal;
        line-height: 1.25;
        margin: 0;';
        if( ! $this->isDashboard() ) {
            $html .= 'margin-left: -20px;
            padding-right: 20px;';
        }
        $html .= 'background: #DBF2FD url("https://ninjaforms.com/wp-content/uploads/2024/06/nf-plugin-asset-survey-bg.png") no-repeat 100%/50%;
        background-size: contain;
        }
        #nf-top-notice .nf-top-inner {
        display: flex;
        flex-flow: row nowrap;
        gap: 24px;
        align-content: stretch;
        align-items: center;
        padding: 8px 16vw 8px 16px;
        }
        #nf-top-notice .nf-top-inner > div {
        flex: 0 auto;
        }
        #nf-top-notice p {
        margin-top: 0;
        margin-bottom: 8px;
        }
        #nf-top-notice :last-child {
        margin-bottom: 0;
        }
        #nf-top-notice .nf-survey-actions {
        text-align: center;
        font-size: 12px;
        }
        #nf-top-notice .nf-survey-alt-actions a {
        color: #424242;
        }
        #nf-top-notice .nf-survey-alt-actions span {
        opacity: 0.85;
        font-size: 0.9em;
        margin-left: 45px;
        }
        #nf-top-notice .nf-survey-remind {
        font-weight: bold;
        }
        #nf-top-notice .nf-survey-content {
        flex: 1 100%;
        }
        #nf-top-notice .nf-survey-btn {
        display: block;
        border-radius: 8px;
        background: #EF4748;
        color: #FFF;
        font-size: 20px;
        font-style: normal;
        font-weight: 700;
        line-height: 24px;
        /* 100% */
        text-transform: uppercase;
        text-decoration: none;
        text-wrap: nowrap;
        padding: 12px 24px;
        margin-bottom: 8px;
        }
    </style>
    ';
    echo $html;
	}
}
