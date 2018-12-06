<?php
namespace Engage\Models;
use Timber\User;

class TeamUser extends User {

	public $_team_path;

	public function __construct($uid = null)
    {
        parent::__construct($uid);
    }

		/**
		 * @api
		 * @return string ex: /team/santa-claus
		 */
		public function team_path(){
			return "Hello, World (this post was made by the TeamUser.php gang)";
		}
}
