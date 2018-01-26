
<?php
  if( empty($team) ) { $team = get_post_team_members(); }
?>

<ul class="enp-author-list">
<?php
  foreach( $team as $member ) :
?>
  <li class="clearfix" id="team-list">
    <figure><img src="<?php echo get_field("member_image", $member->ID); ?>" alt="<?php echo $member->post_title; ?>" width="90"></figure>
    <?php echo wp_get_attachment_image( $member->ID, 'thumbnail' ); ?>
    <div class="author-content">
        <div class="entry-header" id="<?php echo $member->post_name; ?>"><p><span class="author-name"><strong><?php echo $member->post_title; ?></strong></span><br>
      <span class="author-title"><?php echo get_field("member_designation", $member->ID); ?></span><br>
            <span class="author-email"><a href="mailto:<?php echo get_field("member_email", $member->ID); ?>"><?php echo get_field("member_email", $member->ID); ?></a></span></p>
    </div>
    <div class="entry-summary"><p>
      <?php echo get_field("member_description", $member->ID); ?></p>
    </div>
    <?php if( !empty(get_field("member_telephone", $member->ID)) ) { ?>
        <span class="author-telephone"><p>Tel: <?php echo get_field("member_telephone", $member->ID); ?></p></span>
    <?php } ?>
  </div>
  </li>

<?php endforeach; ?>
</ul>
