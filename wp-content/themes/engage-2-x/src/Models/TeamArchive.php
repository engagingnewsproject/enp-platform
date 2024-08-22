<?php
/**
* Set data needed for teams page
*/
namespace Engage\Models;

class TeamArchive extends TileArchive
{

 
    /**
      * Constructor for the TeamArchive class.
      *
      * Initializes the object by calling the parent constructor with the provided query and options.
      * Based on the context of the current archive (team post type archive), the function reorganizes
      * the posts using various regrouping methods.
      *
      * If the current post type archive is "team" and there is a matching vertical or category:
      * - Reorganizes the posts according to "Team Leadership" ACF field group based on the vertical taxonomy.
      * - Applies different regrouping methods based on the vertical term slug:
      *   - 'center-leadership': Regroups posts by the 'team_leadership_center' ACF field.
      *   - 'journalism': Regroups posts by the 'team_leadership_journalism' ACF field.
      *   - 'propaganda': Regroups posts by the 'team_leadership_propaganda' ACF field.
      *   - 'media-ethics': Regroups posts by the 'team_leadership_media_ethics' ACF field.
      *   - 'science-communication': Regroups posts by the 'team_leadership_sci_comm' ACF field.
      * - If the current category is 'administrative-and-technology', regroups posts by the
      *   'team_leadership_admin_tech' ACF field.
      * - If no specific vertical is matched, the posts are regrouped by designation.
      *
      * If the conditions do not match a specific vertical or category, the posts are sorted
      * alphabetically by last name using the "lastNameCompare" method.
      *
      * @param array $options Options passed to the parent constructor and used for post regrouping.
      * @param mixed $query Optional. The WP_Query object or false if none is provided.
      */
    public function __construct($options, $query = false)
    {

        parent::__construct($query, $options);
        if(is_post_type_archive('team') && $this->vertical or $this->category) {
            $vertical = get_query_var('verticals', false);
        
            switch ($vertical) {
                case 'center-leadership':
                    $this->regroupByACFField('team_leadership_center');
                    break;
                case 'journalism':
                    $this->regroupByACFField('team_leadership_journalism');
                    break;
                case 'propaganda':
                    $this->regroupByACFField('team_leadership_propaganda');
                    break;
                case 'media-ethics':
                    $this->regroupByACFField('team_leadership_media_ethics');
                    break;
                case 'science-communication':
                    $this->regroupByACFField('team_leadership_sci_comm');
                    break;
                default:
                    if ($this->category && $this->slug == 'administrative-and-technology') {
                        $this->regroupByACFField('team_leadership_admin_tech');
                    } else {
                        $this->regroupByDesignation();
                    }
                    break;
            }
        } else {
            usort($this->posts, [$this, 'lastNameCompare']);
        }
    }
  
    public function lastNameCompare($a, $b)
    {
        // Gets the last name of each Team Member
        $nameA = explode(' ', $a->title);
        $nameB = explode(' ', $b->title);
        return strcmp(end($nameA), end($nameB));
    }

    public function desigOrderCompare($a, $b)
    {
        // Gets the order # of each posts designation
        // OLD CODE:
        // $desigA = get_field('order', $a->getTermDesign()[0]) ?? 100;
        // $desigB = get_field('order', $b->getTermDesign()[0]) ?? 100;
        // NEW CODE:
        // var_dump( 'disignation term==> ', $a->getTermDesign()[0] );
        $desigA = (int) get_field('order', $a->getTermDesign()[0]) ?? 100;
        $desigB = (int) get_field('order', $b->getTermDesign()[0]) ?? 100;
        // NEW CODE end
        if ($desigA < $desigB) {
            return -1;
        } elseif ($desigA > $desigB) {
            return 1;
        } else {
            return 0;
        }
    }
  
    /**
     * Regroups posts by a specified ACF relationship field.
     *
     * This method is used to reorder posts based on a custom field specified by the
     * `$acf_field_name` parameter. The function separates the posts into two groups:
     * selected team members and other members. The selected members are ordered
     * according to the order specified in the ACF relationship field, while the other
     * members are sorted alphabetically by last name.
     *
     * The function then merges these two groups, placing the selected members at the top
     * followed by the alphabetically sorted remaining members.
     *
     * @param string $acf_field_name The name of the ACF relationship field used to determine the order of selected team members.
     */
    public function regroupByACFField($acf_field_name)
    {
        // Get selected team members from the ACF relationship field
        $selected_team_members = get_field($acf_field_name, 'option');

        $other_members = [];
        $leadership = [];

        foreach($this->posts as $post) {
            // If the post is in the selected team members, add to the leadership array
            if ($selected_team_members && in_array($post->ID, wp_list_pluck($selected_team_members, 'ID'))) {
                array_push($leadership, $post);
            } else {
                array_push($other_members, $post);
            }
        }

        // Ensure the selected team members are in the correct order as per ACF relationship field
        $ordered_leadership = [];
        foreach ($selected_team_members as $selected_member) {
            foreach ($leadership as $member) {
                if ($member->ID === $selected_member->ID) {
                    $ordered_leadership[] = $member;
                    break;
                }
            }
        }

        // Sort the remaining team members alphabetically by last name
        usort($other_members, [$this, 'lastNameCompare']);

        // Merge the leadership and remaining team members arrays
        $this->posts = array_merge($ordered_leadership, $other_members);
    }

    /**
     * Reorganizes posts by their designation (taxonomy terms)
     * and then sorts each group alphabetically by last name.
     * `getTermDesign` method fetches the designation term(s) for each post.
     *
     * @return void
     */
    public function regroupByDesignation()
    {
        // Sorts all posts by designation order
        $postsArray = iterator_to_array($this->posts);
        usort($postsArray, [$this, 'desigOrderCompare']);
        $groups = [];
        // Splits the queried posts by designation, using the slugs as keys
        foreach($this->posts as $post) {
            // PRIOR CODE:
            // $design_slug = $post->getTermDesign()[0]->slug;
            // NEW CODE:
            // var_dump( $post->getTermDesign()[0] );
            $design_slug = '';
            if(!empty($post->getTermDesign()[0])) {
                $design_slug = $post->getTermDesign()[0]->slug;
                // var_dump( $design_slug );
            }
            // NEW CODE end
            if (!in_array($design_slug, ['director', 'assistant-director'], true)) {
                $design_slug = 'other';
            }

            if (!array_key_exists($design_slug, $groups)) {
                $groups[$design_slug] = [$post];
            } else {
                array_push($groups[$design_slug], $post);
            }
        }

        // Sorts each designation group alphabetically then merges back to posts
        $this->posts = [];
        foreach($groups as $group) {
            usort($group, [$this, 'lastNameCompare']);
            $this->posts = array_merge($this->posts, $group);
        }
    }
}
