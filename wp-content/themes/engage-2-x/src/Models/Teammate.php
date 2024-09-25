<?php
namespace Engage\Models;

/**
 * Class Teammate
 *
 * Represents a team member and extends the Article class.
 * Provides methods to retrieve custom meta fields and taxonomy terms
 * specific to team members such as designation, email, phone, external link,
 * vertical, and various taxonomy terms related to the team.
 *
 * @package Engage\Models
 */

class Teammate extends Article
{

    /**
     * @var string $name The name of the team member.
     * @var string|bool $designation The designation of the team member.
     * @var string|bool $email The email of the team member.
     * @var string|bool $phone The phone number of the team member.
     * @var string|bool $external_link The external link associated with the team member.
     * @var string|bool $link_option The option to use the external link.
     * @var array|bool $vertical The vertical associated with the team member.
     * @var array|bool $termCat The team category associated with the team member.
     * @var array|bool $termDesign The team designation term associated with the team member.
     * @var array|bool $termSemester The team semester term associated with the team member.
     */
    public $name;
    public $designation = false;
    public $email = false;
    public $phone = false;
    public $external_link = false;
    public $link_option = false;
    public $vertical = false;
    public $termCat = false;
    public $termDesign = false;
    public $termSemester = false;

    /**
     * Initializes the Teammate object.
     *
     * Calls the parent constructor to initialize the object with the given post ID
     * and sets the name property to the title of the post.
     *
     * @param int|null $postID The ID of the post (optional).
     */
    public function init($postID = null)
    {
        parent::__construct($postID);
        $this->name = $this->title;
    }

    /**
     * Gets the designation of the team member.
     *
     * Retrieves the 'member_designation' meta field for the post.
     *
     * @return string|bool The designation of the team member, or false if not set.
     */
    public function getDesignation()
    {
        if($this->designation === false) {
            $this->designation = get_post_meta($this->ID, 'member_designation', true);
        }
        return $this->designation;
    }

    /**
     * Gets the email of the team member.
     *
     * Retrieves the 'member_email' meta field for the post.
     *
     * @return string|bool The email of the team member, or false if not set.
     */
    public function getEmail()
    {
        if($this->email === false) {
            $this->email = get_post_meta($this->ID, 'member_email', true);
        }
        return $this->email;
    }

    /**
     * Gets the phone number of the team member.
     *
     * Retrieves the 'member_telephone' meta field for the post.
     *
     * @return string|bool The phone number of the team member, or false if not set.
     */
    public function getPhone()
    {
        if($this->phone === false) {
            $this->phone = get_post_meta($this->ID, 'member_telephone', true);
        }
        return $this->phone;
    }

    /**
     * Gets the external link associated with the team member.
     *
     * Retrieves the 'member_external_link' meta field for the post.
     *
     * @return string|bool The external link, or false if not set.
     */
    public function getExternalLink()
    {
        if($this->external_link === false) {
            $this->external_link = get_post_meta($this->ID, 'member_external_link', true);
        }
        return $this->external_link;
    }

    /**
     * Gets the option to use the external link.
     *
     * Retrieves the 'external_link_checkbox' meta field for the post.
     *
     * @return string|bool The link option, or false if not set.
     */
    public function getLinkOption()
    {
        if ($this->link_option == false) {
            $this->link_option = get_post_meta($this->ID, 'external_link_checkbox', true);
        }
        return $this->link_option;
    }

    public function getMemberDisplayLink()
    {
        if ($this->member_link_option == false) {
            $this->member_link_option = get_post_meta($this->ID, 'member_display_link', true);
        }
        return $this->member_link_option;
    }

    /**
     * Gets the vertical associated with the team member.
     *
     * Retrieves the 'vertical' taxonomy terms associated with the post.
     *
     * @return array|bool The vertical terms, or false if not set.
     */
    public function getVertical()
    {
        if($this->vertical === false) {
            $this->vertical = get_the_terms($this->ID, 'vertical');
        }
        return $this->vertical;
    }

    /**
     * Gets the team category associated with the team member.
     *
     * Retrieves the 'team_category' taxonomy terms associated with the post.
     *
     * @return array|bool The team category terms, or false if not set.
     */
    public function getTermCat()
    {
        if($this->termCat === false) {
            $this->termCat = get_the_terms($this->ID, 'team_category');
        }
        return $this->termCat;
    }

    /**
     * Gets the team designation term associated with the team member.
     *
     * Retrieves the 'team_designation' taxonomy terms associated with the post.
     *
     * @return array|bool The team designation terms, or false if not set.
     */
    public function getTermDesign()
    {
        if($this->termDesign === false) {
            $this->termDesign = get_the_terms($this->ID, 'team_designation');
        }
        return $this->termDesign;
    }

    /**
     * Gets the team semester term associated with the team member.
     *
     * Retrieves the 'team_semester' taxonomy terms associated with the post.
     *
     * @return array|bool The team semester terms, or false if not set.
     */
    public function getTermSemester()
    {
        if($this->termSemester === false) {
            $this->termSemester = get_the_terms($this->ID, 'team_semester');
        }
        return $this->termSemester;
    }
}
