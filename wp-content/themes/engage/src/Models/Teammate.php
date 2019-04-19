<?php
namespace Engage\Models;

class Teammate extends Article {

	public $name,
           $designation = false,
           $email = false,
		   		 $phone = false,
					 $terms = false;

	public function __construct($postID = null)
    {
        parent::__construct($postID);
        $this->name = $this->title;
    }

    public function getDesignation() {
    	if($this->designation === false) {
    		$this->designation = get_post_meta($this->ID, 'member_designation', true);
    	}
    	return $this->designation;
    }

    public function getEmail() {
        if($this->email === false) {
            $this->email = get_post_meta($this->ID, 'member_email', true);
        }
        return $this->email;
    }

    public function getPhone() {
        if($this->phone === false) {
            $this->phone = get_post_meta($this->ID, 'member_telephone', true);
        }
        return $this->phone;
    }

		public function getTerms() {
				if($this->terms === false) {
					$this->terms = get_the_terms($this->ID, 'team_category');
				}
				return $this->terms;
		}

}
