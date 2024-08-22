<?php
/**
* Set data needed for teams page
*/
namespace Engage\Models;

class TeamArchive extends TileArchive
{

  public function __construct( $options, $query = false )
  {

      parent::__construct( $query, $options );
      if(is_post_type_archive("team") && $this->vertical or $this->category) {
        $vertical = get_query_var('verticals', false);
        if ($vertical == "center-leadership") {
          $this->regroupByLeadershipPosition();
        } else if ($vertical == "media-ethics") {
        $this->regroupForMediaEthics();
        } else if ($vertical == "science-communication") {
          $this->regroupForScienceComm();
        } else if ($this->category && $this->slug == "administrative-and-technology") {
          $this->regroupForTech();
        } else if ($vertical == "propaganda") {
          $this->regroupForPropaganda();
        } else if ($vertical == "journalism") {
          $this->regroupForJournalism();
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
    $nameA = explode(' ', $a->title);
    $nameB = explode(' ', $b->title);
    return strcmp(end($nameA), end($nameB));
  }

  function desigOrderCompare($a, $b) {
    // Gets the order # of each posts designation
    // OLD CODE:
    // $desigA = get_field('order', $a->getTermDesign()[0]) ?? 100;
    // $desigB = get_field('order', $b->getTermDesign()[0]) ?? 100;
    // NEW CODE:
    $desigA = (int) get_field('order', $a->getTermDesign()[0]) ?? 100;
    $desigB = (int) get_field('order', $b->getTermDesign()[0]) ?? 100;
    // NEW CODE end
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
    $postsArray = iterator_to_array($this->posts);
    usort($postsArray, array($this, "desigOrderCompare"));
    $groups = array();
    // Splits the queried posts by designation, using the slugs as keys
    foreach($this->posts as $post) {
      // PRIOR CODE:
      // $design_slug = $post->getTermDesign()[0]->slug;
      // NEW CODE:
      $design_slug = '';
      if(!empty($post->getTermDesign()[0])) {
        $design_slug = $post->getTermDesign()[0]->slug;
      }
      // NEW CODE end
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

  // Used to reorder the team members for the admin and tech vertical.
  public function regroupForTech() {
  
    $tech = array();
    // Slug used to group leadership for top of tech page
    $leadership= array();

    // Splits the posts between leadership positions and remaining staff
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->title, [
          'Ellery Wadman-Goetsch', 
          'Victoria Hernandez'
        ], true)) {
          array_push($leadership, $post);
        } else {
          array_push($tech, $post);
        }
    }
    
    // Orders leadership team.
    $order = array(
      'Ellery Wadman-Goetsch', 
      'Victoria Hernandez'
    );
    usort($leadership, function ($a, $b) use ($order) {
      $pos_a = array_search($a->title, $order);
      $pos_b = array_search($b->title, $order);
      return $pos_b - $pos_a;
    });

    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($tech, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $tech);                             
  }

    
  // Used to reorder the team members for the science communication vertical.
  public function regroupForScienceComm() {
    
    $science_comm = array();
    // Slug used to group leadership for top of science comm page
    $leadership= array();

    // Splits the posts between leadership positions and remaining staff
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->title, [
          'Anthony Dudo', 
          'Lucy Atkinson', 
          'Lee Ann Kahlor'
        ], true)) {
          array_push($leadership, $post);
        } else {
          array_push($science_comm, $post);
        }
    }
    
    // Orders leadership team.
    $order = array(
      'Lee Ann Kahlor', 
      'Lucy Atkinson', 
      'Anthony Dudo' 
    );
    usort($leadership, function ($a, $b) use ($order) {
      $pos_a = array_search($a->title, $order);
      $pos_b = array_search($b->title, $order);
      return $pos_b - $pos_a;
    });

    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($science_comm, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $science_comm);                             
  }

  // Used to reorder the team members for the media ethics vertical.
  public function regroupForMediaEthics() {
    
    $media_ethics = array();
    // Slug used to group leadership for top of media ethics page
    $leadership= array();

    // Splits the posts between leadership positions and remaining staff
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->title, [
          "Scott R. Stroud",
          "Leah Ransom", 
          "Kat Williams",

        ], true)) {
          array_push($leadership, $post);
        } else {
          array_push($media_ethics, $post);
        }
    }
        
// Orders leadership team, placing Leah Ransome in the second spot
$order = array(
  "Scott R. Stroud",
  "Leah Ransome",
  "Kat Williams",
  "Finja Augsburg"
);
// Orders leadership team, from top to bottom
usort($leadership, function ($a, $b) {
  if ($a->title === "Scott R. Stroud") {
      return -1; // $a comes before $b
  } elseif ($b->title === "Scott R. Stroud") {
      return 1; // $b comes before $a
  } elseif ($a->title === "Leah Ransom") {
      return -1; // $a comes before $b
  } elseif ($b->title === "Leah Ransom") {
      return 1; // $b comes before $a
  } else {
      return 0; // No change in order for other team members
  }
});

    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($media_ethics, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $media_ethics);                             
  }

  // regroups team members for propaganda vertical.
  public function regroupForPropaganda() {
    
    $prop = array();
    $leadership = array();
    
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->title, [
          'Samuel C. Woolley', 
          'Inga Kristina Trauthig', 
          'Jo Lukito', 
          'Craig R. Scott'
        ], true)) {
          array_push($leadership, $post);
        } else {
          array_push($prop, $post);
        }
      }
    $order = array(
      'Samuel C. Woolley',
      'Craig R. Scott',  
      'Inga Kristina Trauthig',
      'Jo Lukito'
    );
    usort($leadership, function ($a, $b) use ($order) {
      $pos_a = array_search($a->title, $order);
      $pos_b = array_search($b->title, $order);
      return $pos_b - $pos_a;
    });
    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($prop, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $prop); 
                                          
  }

  public function regroupForJournalism() {
    $journalism = array();
    // Slug used to group leadership for top of media ethics page
    $leadership= array();

    // Splits the posts between leadership positions and remaining staff
    foreach($this->posts as $post) {
        // Check to see if current member should be included in leadership array
        if (in_array($post->title, [
          'Natalie (Talia) Jomini Stroud', 
          'Gina M. Masullo', 
          'Matt Lease', 
          'Ashwin Rajadesingan', 
          'Anita Varma'
        ], true)) {
          array_push($leadership, $post);
        } else {
          array_push($journalism, $post);
        }
    }

    // Orders leadership team.
    $order = array(
      "Anita Varma", 
      "Ashwin Rajadesingan", 
      "Matt Lease", 
      "Gina M. Masullo", 
      "Natalie (Talia) Jomini Stroud"
    );
    usort($leadership, function ($a, $b) use ($order) {
      $pos_a = array_search($a->title, $order);
      $pos_b = array_search($b->title, $order);
      return $pos_b - $pos_a;
    });

    // Merge the two groups (leadership and remaining members) into one array
    $this->posts = array();
    usort($journalism, array($this, "lastNameCompare"));
    $this->posts = array_merge($leadership, $journalism);                             
  }

  public function regroupByLeadershipPosition() {
    $order = array(
      "Katalina Deaven", 
      "Samuel C. Woolley", 
      "Scott R. Stroud", 
      "Anthony Dudo", 
      "Melody Avant", 
      "Gina M. Masullo", 
      "Natalie (Talia) Jomini Stroud"
    );
    
    // Convert Timber\PostQuery to array
    $postsArray = iterator_to_array($this->posts);
    usort($postsArray, function ($a, $b) use ($order) {
        $pos_a = array_search($a->title, $order);
        $pos_b = array_search($b->title, $order);
        
        // Debugging output
        // var_dump("Comparing {$a->name} ({$pos_a}) with {$b->name} ({$pos_b})" );
        return $pos_b - $pos_a;
    });

    // Set the sorted array back to $this->posts
    $this->posts = $postsArray;
  }
}
