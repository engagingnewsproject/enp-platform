<div class="wrap">
    <header class="topbar">
        <div class="app-title">
            <strong><?php esc_html_e( 'Welcome to Ninja Forms', 'ninja-forms' ); ?></strong>
        </div>
    </header>
    <style type="text/css">
        /** TODO: Move styling to external css file */
        #wpbody-content > div:not(.wrap) {
            display: none;
        }

        #wpcontent {
            padding: 0;
            background: #fff;
            height: 100%;
            min-height: 900px;
        }

        .wrap {
            text-align: left;
            color: #000;
            font-family: "Segoe UI",Frutiger,"Frutiger Linotype","Dejavu Sans","Helvetica Neue",Arial,sans-serif;
            font-style: normal;
            line-height: normal;
            margin: 0;
        }

        .topbar {
            background-color: #ebedee;
            margin-bottom: 40px;
        }

        .topbar .app-title {
            width: 100%;
            max-width: 50rem;
            margin: auto;
            background-image: url(<?php echo Ninja_Forms::$url . 'assets/img/nf-logo-dashboard.png' ?>);
            background-size: 315px 48px;
            background-position: 0 100%;
            background-repeat: no-repeat;
            height: 52px;
        }

        .app-title strong {
            display: block;
            text-indent: -9999px;
        }

        .welcome-banner {
            position: relative;
            width: 1000px;
        }

        .content {
            flex-shrink: 0;
            width: 1000px;
            margin: auto;
        }

        .content p {
            margin-left: 100px;
            font-size: 16px;
            font-weight: 600;
        }

        .above-banner {
            margin-bottom: -20px;
        }

        .above-banner .greeting {
            font-size: 36px;
            font-weight: 350;
        }

        .above-banner .thanks {
            font-weight: 500;
            font-size: 20px;
        }

        .below-banner {
            margin-top: -40px;
        }

        .below-banner .message {
            font-weight: 400;
            width: 490px;
        }

        .subtext a {
            color: #1ea9ea;
            cursor: pointer;
        }

        .actions {
            display: block;
            margin: 35px 95px;
        }

        a.nf-button {
            display: inline-block;
            width: 375px;
            font-size: 28px;
            font-weight: 350;
            border-radius: 8px;
            padding-top: 7px;
            height: 60px;
            margin: 5px;
            border: 1px solid #1ea9ea;
            cursor: pointer;
            line-height: 48px;
            text-decoration: none;
            text-align: center;
        }

        a.disabled {
            color: #000 !important;
            background-color: #ebedee !important;
            border-color: #ebedee !important;
            cursor: default;
        }

        a.primary {
            background: #1ea9ea;
            color: #fff;
        }

        a.secondary {
            background: #fff;
            color: #1ea9ea;
        }

        #nf-dismiss,
        #nf-dismiss:active,
        #nf-dismiss:visited {
            color: #1ea9ea;
            text-decoration: underline;
        }

        #nf-dismiss:hover {
            text-decoration: none;
            cursor: pointer;
        }

        /*
        jBox
        ---------------------------------------------*/

        .jBox-Modal {
            background-color: white;
        }

        .jBox-Modal .jBox-title {
            padding-top: 10px;
            padding-bottom: 10px;
            font-size: 150%;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }

        .jBox-Modal .jBox-content {
            padding: 10px;
            width: 100%;
        }

        .jBox-Modal .buttons,
        .jBox-Modal .buttons::after {
            clear: none;
            content: "";
            display: block;
        }

        @keyframes nf-service-installing {
            100% {
                transform:rotate(360deg);
            }
        }

        .dashicons-update-spin {
            animation: nf-service-installing 1s linear infinite;
        }
    </style>

    <div class="content nf-main-content">
        <div class="above-banner">
            <p class="greeting"><?php esc_html_e("Thank you for giving Ninja Forms a try!", "ninja-forms"); ?></p>
            <p class="thanks"><?php esc_html_e("As a small WordPress business, every new person exploring the plugin means a lot to us.", "ninja-forms"); ?></p>
        </div>
        <div class="welcome-banner">
            <img src="<?php echo Ninja_Forms::$url . 'assets/img/onboarding/nf-team-welcome.png' ?>" alt="Our team thanks you!" />
        </div>
        <div class="below-banner">
            <p class="message"><?php esc_html_e("We've been standing by our product and our users for over a decade, working to make your experience the best it can be. We're one of the only form builders around that offers support for all users, whether you've made a purchase or not. If you have any questions or suggestions, you'll find a direct line to our team from the Get Help tab. We're always happy to hear from you.", "ninja-forms"); ?></p>
            <p class="thanks"><?php esc_html_e("Thanks again for joining us. We're excited to have you on board!", "ninja-forms"); ?></p>
        </div>
        <div class="actions">
            <?php if(apply_filters('nf_onboarding_step_now', 0) > 0) { ?>
                <a class="primary nf-button disabled" id="nf-start"><?php esc_html_e("In progress...", "ninja-forms"); ?></a>
            <?php } else { ?>
                <a class="primary nf-button" id="nf-start"><?php esc_html_e("Create your first form", "ninja-forms"); ?></a>
            <?php } ?>
            <a class="secondary nf-button" href="https://ninjaforms.com/documentation/?utm_source=Ninja+Forms+Plugin&utm_medium=Welcome&utm_campaign=Documentation" target="_blank"><?php esc_html_e("Documentation", "ninja-forms"); ?></a>
        </div>
        <p><?php echo sprintf(esc_html__("Already know what you're doing? Great! You can %sdismiss this page.%s", "ninja-forms"), "<a id='nf-dismiss'>", "</a>"); ?></p>
    </div>

</div>