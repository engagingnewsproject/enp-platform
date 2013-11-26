<?php
/*
Template Name: List Polls
*/
?>
<?php get_header(); ?>
<? 
if ( $_GET["delete_guid"] ) {
  $wpdb->delete( 'enp_poll', array( 'guid' => $_GET["delete_guid"] ) );
  echo "Poll Deleted: ". $_GET["delete_guid"];
}
?>

<div id="main_content" class="clearfix">
	<div id="left_area" class="bootstrap">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>
    <?php
    $user_ID = get_current_user_id(); 
    
    if ( $user_ID ) {
    ?>
    <h2>My Polls</h2>
    <p><a href="configure-poll/" class="btn btn-primary btn-sm active" role="button">New poll</a></p>
    <?php
    $polls= $wpdb->get_results( 
    	"
    	SELECT * 
    	FROM enp_poll
    	WHERE user_id = " . $user_ID 
    );
        
    if ( $polls )
    {
      echo "<div class='table-responsive'>";
      echo "<table class='table'>";
      echo "<thead><tr>
              <th>Title</th>
              <th>Question</th>
              <th>Unique Views</th>
              <th>Correct Respones</th>
            </tr></thead>";
    	foreach ( $polls as $poll )
    	{
    		?>
        <tr>
          <td><a href="view-poll?guid=<?php echo $poll->guid ?>"><?php echo $poll->title; ?></a></td>
          <td><?php echo $poll->question; ?></td>
        <?php
        $wpdb->get_var( 
        	"
          SELECT ip_address
          FROM enp_poll_responses	 
          WHERE poll_id = " . $poll->ID . 
          " GROUP BY ip_address"
        );
        
        ?>
        <td><a href="poll-report/?guid=<?php echo $poll->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $wpdb->num_rows ?></a></td>
        <?php
        $correct_response_count = $wpdb->get_var( 
        	"
        	SELECT COUNT(*) 
        	FROM enp_poll_responses
        	WHERE is_correct = 1 AND poll_id = " . $poll->ID
        );
        
        ?>
          <td><a href="poll-report/?guid=<?php echo $poll->guid ?>" class="btn btn-warning btn-xs active" role="button"><?php echo $correct_response_count?></a></td>
          <td><a href="configure-poll/?edit_guid=<?php echo $poll->guid ?>" class="btn btn-info btn-xs active" role="button">Edit</a></td>
          <td><a href="list-polls/?delete_guid=<?php echo $poll->guid ?>" onclick="return confirm('Are you sure you want to delete this poll?')" class="btn btn-danger btn-xs active" role="button">Delete</a></td>
        </tr>
    		<?php
    	}	
      
      echo "</table>";
      echo "</div>";
    }
    else
    {
    	?>
    	<h2>No polls found for the current user</h2>
    	<?php
    }
    ?>
    
    <?php
    } else {
    ?>
      <p>Please login to start creating polls!</p>
    <?php } ?>
    
		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>