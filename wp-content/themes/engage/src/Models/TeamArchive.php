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
      if(is_post_type_archive("team") && $this->vertical) {
        $this->regroupByDesignation();
      }
      else {
        usort($this->posts, [$this,"lastNameCompare"]);
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
    $desigA = get_field('order', $a->getTermDesign()[0]);
    $desigB = get_field('order', $b->getTermDesign()[0]);
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
      }
      else {
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
}
