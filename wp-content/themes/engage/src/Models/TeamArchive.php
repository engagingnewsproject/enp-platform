<?php
/**
* Set data needed for teams page
*/
namespace Engage\Models;

class TeamArchive extends TileArchive
{

  public function __construct($options, $query = false, $class = 'Engage\Models\Teammate')
  {

      parent::__construct($options, $query, $class);
      if(is_post_type_archive("team") && $this->vertical or $this->category) {
        $vertical = get_query_var('verticals', false);
        if ($vertical == "center-leadership") {
          $this->regroupByLeadershipPosition();
        
        } else if ($vertical == "media-ethics") {
          $this->regroupForMediaEthics();
        } else {
          $this->regroupByDesignation();
        }
      }
      else {
        usort($this->posts, [$this, "regroupByLeadershipPosition"]);
      }

  }

  function lastNameCompare($a, $b) {
    // Gets the last name of each Team Member
    $nameA = explode(' ', $a->name);
    $nameB = explode(' ', $b->name);
    return strcmp(end($nameA), end($nameB));
  }

  function desigOrderCompare($a, $b) {
    // Gets the order # of each posts designation
    $desigA = get_field('order', $a->getTermDesign()[0]) ?? 100;
    $desigB = get_field('order', $b->getTermDesign()[0]) ?? 100;
    if ($desigA < $desigB) {
      return -1;
    }
    elseif ($desigA > $desigB) {
      return 1;
    }
    else {
      return 0;
    }
  }

  public function regroupByDesignation() {
    // Sorts all posts by designation order
    usort($this->posts, array($this, "desigOrderCompare"));
    $groups = array();
    // Splits the queried posts by designation, using the slugs as keys
    foreach($this->posts as $post) {
      $design_slug = $post->getTermDesign()[0]->slug;
      if (!in_array($design_slug, ['director', 'assistant-director'], true )) {
        $design_slug = "other";
      }

      if (!array_key_exists ($design_slug, $groups)) {
        $groups[$design_slug] = array($post);
      } else {
        array_push($groups[$design_slug], $post);
      }
    }

    // Sorts each designation group alphabetically then merges back to posts
    $this->posts = array();
    foreach($groups as $group) {
      usort($group, array($this, "lastNameCompare"));
      $this->posts = array_merge($this->posts, $group);
    }
  }

  // Used to reorder the team members for the media ethics vertical.
  public function regroupForMediaEthics() {
    
    $media_ethics = array();
    // Slug used to group leadership for top of media ethics page
    $leadership= array();

    // Splits the posts between leadership positions and remaining staff
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->name, ['Scott R. Stroud', 
        'Finja Augsburg', 'Kat Williams'], true)) {
          array_push($leadership, $post);
        } else {
          array_push($media_ethics, $post);
        }
    }
    
    // Orders leadership team.
    $order = array("Kat Williams", "Finja Augsburg", "Scott R. Stroud");
    usort($leadership, function ($a, $b) use ($order) {
      $pos_a = array_search($a->name, $order);
      $pos_b = array_search($b->name, $order);
      return $pos_b - $pos_a;
    });

    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($media_ethics, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $media_ethics);                             
  }



  public function regroupByLeadershipPosition() {
    $order = array("Katalina Deaven", "Samuel C. Woolley", "Scott R. Stroud", "Anthony Dudo", "Melody Avant", "Gina M. Masullo", "Natalie (Talia) Jomini Stroud");
    usort($this->posts, function ($a, $b) use ($order) {
      $pos_a = array_search($a->name, $order);
      $pos_b = array_search($b->name, $order);
      return $pos_b - $pos_a;
    });
  }
}
