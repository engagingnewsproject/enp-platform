<?php
namespace Engage\Models;

class Teammate extends Article {

	public $name,
           $designation = false,
           $email = false,
		   		 $phone = false,
					 $vertical = false,
					 $termCat = false,
					 $termDesign = false;

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

		public function getVertical() {
        if($this->vertical === false) {
            $this->vertical = get_the_terms($this->ID, 'vertical');
        }
        return $this->vertical;
		}

		public function getTermCat() {
				if($this->termCat === false) {
					$this->termCat = get_the_terms($this->ID, 'team_category');
				}
				return $this->termCat;
		}

		public function getTermDesign() {
			if($this->termDesign === false) {
				$this->termDesign = get_the_terms($this->ID, 'team_designation');
			}
			return $this->termDesign;
		}

}
