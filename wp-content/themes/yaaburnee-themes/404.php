 <?php 
	get_header();
?> 
<!-- Container -->
<div class="container">
    
    <!-- Primary left -->
    <div id="primary-fullwidth">
        <div class="row" id="page-404">
            <div class="col6">
                <h3><?php _e("404", THEME_NAME);?> <span><?php _e(":)", THEME_NAME);?></span></h3>
            </div>
            <div class="col6">
                <h5><?php _e("The page you are looking for<br>has been moved or doesn't exist anymore!", THEME_NAME);?></h5>
                <p><?php _e("But don't worry, it can happen to the best of us - and it just happened to you!<br> You can search something else or read this text one more time.", THEME_NAME);?></p>
                <form method="get" action="<?php echo home_url();?>">
                    <input type="text" placeholder="<?php _e("Search...", THEME_NAME);?>" name="s" id="s"/>
                    <input type="submit" value="Search"/>
                </form>
            </div>
        </div>
    </div>

</div>

<?php 
	get_footer();
?>