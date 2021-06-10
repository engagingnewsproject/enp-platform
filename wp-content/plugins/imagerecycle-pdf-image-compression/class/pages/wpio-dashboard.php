<?php

defined('ABSPATH') || die('No direct script access allowed!');

$adminUser = get_userdata(1);
$adminName = urlencode($adminUser->user_login);
$adminMail = urlencode(get_option("admin_email"));

$urlImagerecycle = "https://www.imagerecycle.com";
$urlToList = admin_url("upload.php?page=wp-image-recycle-page&iomess=accountCreated");
$urlToCreateAccount = "https://www.imagerecycle.com/index.php?option=com_ioa&view=register&adminName={$adminName}&adminMail={$adminMail}&TB_iframe=true&width=600&height=550";
$urlToLoginAccount = "https://www.imagerecycle.com/index.php?option=com_ioa&view=login&layout=connect&TB_iframe=true&width=600&height=550";

add_thickbox();
?>
<div class="main-presentation"
     style="margin: 30px auto; max-width: 1200px; background-color:#f0f1f4;font-family: helvetica,arial,sans-serif;">
    <div class="main-textcontent"
         style="margin: 0px auto; min-height: 300px; border-left: 1px dotted #d2d3d5; border-right: 1px dotted #d2d3d5; width: 840px; background-color:#fff;border-top: 5px solid #544766;"
         cellspacing="0" cellpadding="0" align="center">
        <a href="https://www.imagerecycle.com/" target="_blank"> <img
                    src="https://www.imagerecycle.com/images/Notification-mail/logo-image-recycle.png"
                    alt="logo image recycle" width="500" height="84" class="CToWUd"
                    style="display: block; outline: medium none; text-decoration: none; margin-left: auto; margin-right: auto; margin-top:15px;">
        </a>
        <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 24px; line-height: 24px; padding-right: 10px; padding-left: 10px;"
           align="center"><strong>Welcome on board!<br></strong></p>
        <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 14px; line-height: 22px; padding-left: 20px; padding-right: 20px; text-align: center;">
            ImageRecycle will help you to compress automatically your website images & PDF.<br>In order to start the optimization process, create a 100MB FREE TRIAL account to see how you can compress your content. You can also use existing account and just login. Enjoy!</p>
        <p></p>
        <p>
            <a style="width: 250px; float: left; background: #554766; font-size: 12px; line-height: 18px; text-align: center;  margin-left:20px;color: #fff;font-size: 14px;text-decoration: none; text-transform: uppercase; padding: 8px 20px; font-weight:bold;"
               href="<?php echo $urlToLoginAccount; ?>" class="thickbox">Use existing account</a></p>
        <p>
            <a style="width: 250px; float: right; background: #554766; font-size: 12px; line-height: 18px; text-align: center;  margin-right:20px;color: #fff;font-size: 14px;text-decoration: none; text-transform: uppercase; padding: 8px 20px; font-weight:bold;"
               href="<?php echo $urlToCreateAccount; ?>" class="thickbox">Create a trial account</a></p>
        <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 24px; line-height: 24px; padding-right: 10px; padding-left: 10px; padding-top: 40px;"
           align="center"><strong><br>Why ImageRecycle?<br></strong></p>
        <p style="background-color: #ffffff; color: #445566; font-family: helvetica,arial,sans-serif; font-size: 14px; line-height: 22px; padding-left: 20px; padding-right: 20px; text-align: center;">
            Images represent 60% to 70% of website page weight. Image optimization is good for your users and for your
            server.<br> You won't find any other service that compress in the same time .pdf .jpeg and .png images
            keeping the original quality. We are using a unique algorithm to achieve that. Each compression script is
            executed on an optimized server to serve you as fast as possible.</p>
        <p style="font-size: 130%;">
            <a href="https://www.imagerecycle.com/plans/free-trial-plan?group_id=0" target="_blank">Free trial</a> -
            <a href="https://www.imagerecycle.com/prices" target="_blank">Prices</a> -
            <a href="https://www.imagerecycle.com/uploader" target="_blank">Uploader</a>
        </p>
        <p style="text-align: center; color: #9da2a8; font-size: 11px; line-height: 12px; padding: 15px;"><br> This is a
            welcome screen to help you with ImageRecycle configuration. Once you have registered your API key this
            message will no longer be displayed.<br><br><a href="https://www.facebook.com/imagerecycle" target="_blank"><img
                        src="https://www.imagerecycle.com/images/Notification-mail/facebook.png" alt="facebook"
                        width="24" height="24" class="CToWUd"></a> &nbsp; <a href="https://twitter.com/ImageRecycle"
                                                                             target="_blank" style="margin: 0 5px;"><img
                        src="https://www.imagerecycle.com/images/Notification-mail/twitter.png" alt="twitter" width="24"
                        height="24" class="CToWUd"></a></p>
    </div>
</div>
<script>
    window.addEventListener("message",
        function (e) {
            if (e.origin !== "<?=$urlImagerecycle ?>") {
                return;
            }
            var accountData = JSON.parse(e.data)
            jQuery.ajax({
                url: ajaxurl,
                data: {action: 'wpio_createAccount', key: accountData.key, secret: accountData.secret},
                type: 'post',
                dataType: 'json',
                success: function (response) {
                    if (response == true) {
                        window.location = '<?=$urlToList ?>';
                    }
                    else {
                        alert("Error, try again");
                    }
                }
            });
        },
        false);
</script>    