
<?php
  if( empty($team) ) { $team = get_post_team_members(); }
?>

<ul class="enp-author-list">
<?php
  foreach( $team as $member ) :
?>
  <li class="clearfix">
    <figure><img src="<?php echo get_field("member_image", $member->ID); ?>" alt="<?php echo $member->post_title; ?>" width="90"></figure>
    <?php echo wp_get_attachment_image( $member->ID, 'thumbnail' ); ?>
    <div class="author-content">
    <div class="entry-header"><p><span class="author-name"><strong><?php echo $member->post_title; ?></strong></span><br>
      <span class="author-title"><?php echo get_field("member_designation", $member->ID); ?></span></p>
    </div>
    <div class="entry-summary"><p>
      <?php echo get_field("member_description", $member->ID); ?></p>
    </div>
  </div>
  </li>

<?php endforeach; ?>
</ul>
