<?php
namespace Engage\Models;

class Teammate extends Article {

	public $name,
           $designation = false,
           $email = false,
		   $phone = false;

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
            $this->phone = get_post_meta($this->ID, 'member_phone', true);
        }
        return $this->phone;
    }

}