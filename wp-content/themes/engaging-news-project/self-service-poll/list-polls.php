<?php
/*
Template Name: List Polls
*/
?>
<?php get_header(); ?>

<div id="main_content" class="clearfix">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    ?>
    <p><a href="/enp/configure-poll/">New poll</a></p>
    
    <?php
    $polls= $wpdb->get_results( 
    	"
    	SELECT * 
    	FROM enp_poll
    	WHERE user_id = " . $user_ID 
    );

    if ( $polls )
    {
    	foreach ( $polls as $poll )
    	{
    		?>
    		<h2><a href="/enp/view-poll?id=<?php echo $poll->ID ?>"><?php echo $poll->title; ?></a></h2>
        <p><?php echo $poll->question; ?></p>
        <?php
        $wpdb->get_var( 
        	"
          SELECT ip_address
          FROM enp_poll_responses	 
          WHERE poll_id = " . $poll->ID . 
          " GROUP BY ip_address"
        );
        
        ?>
        <p>Unique Views: <?php echo $wpdb->num_rows ?></p>
        <?php
        $correct_response_count = $wpdb->get_var( 
        	"
        	SELECT COUNT(*) 
        	FROM enp_poll_responses
        	WHERE is_correct = 1 AND poll_id = " . $poll->ID
        );
        
        ?>
        <p>Correct Respones: <?php echo $correct_response_count?></p>
        <p><a href="/enp/configure-poll/?edit_id=<?php echo $poll->ID ?>">Edit poll</a></p>
        <p><a href="/enp/configure-poll/?delete_id=<?php echo $poll->ID ?>" onclick="return confirm('Are you sure you want to delete this poll?')">Delete poll</a></p>
    		<?php
    	}	
    }
    else
    {
    	?>
    	<h2>No polls found for the current user</h2>
    	<?php
    }
    ?>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>