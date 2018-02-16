<?php

namespace Cme\Database;
use Cme\Utility as Utility;

/**
 * Add a question to the database
 */
class SaveInteraction extends DB {
    public $DB;

    function __construct() {
        $this->DB = new \Cme\Database\DB();
    }

    /**
     *
     * @param $data (ARRAY) needs all the info to be saved
     * @return ARRAY with saved data or Errors
     */
    public function save($data) {

        // decide how to save it...
        return $this->insert($data);
    }

    /* Validates data structure
     * @param $data (ARRAY) needs all the info to be saved
     *    [
     *        'interaction'=> [
     *            'type'=> 'load', // load, reload, start, restart, option, history
     *            'id' => null // null, option_id
     *        ],
     *        'destination' => [
     *            'type'  => 'question', // question, end, overview, intro
     *            'id'    => (INT)
     *        ],
     *        'user_id' => '123dsfa1231sdfa'
     *    ]
     *
     * @return true or false
     */
    protected function validate($data) {
        $is_valid = false;

        // check if we have all the data we need
        if(!isset($data['interaction'])) {
            $this->errors[] = 'No interaction data.';
        }

        if(!isset($data['interaction']['type'])) {
            $this->errors[] = 'No interaction type.';
        }

        if(!isset($data['interaction']['id'])) {
            $this->errors[] = 'No interaction id.';
        }

        if(!isset($data['destination'])) {
            $this->errors[] = 'No destination data.';
        }

        if(!isset($data['destination']['type'])) {
            $this->errors[] = 'No destination type.';
        }

        if(!isset($data['destination']['id'])) {
            $this->errors[] = 'No destination id.';
        }

        if(!isset($data['user_id'])) {
            $this->errors[] = 'No user id.';
        }

        if(!isset($data['tree_id'])) {
            $this->errors[] = 'No tree id.';
        }

        if(!isset($data['site']['embed_id'])) {
            $this->errors[] = 'No embed id.';
        }

        // if we have any errors, return false. Passes first round of being the correct data structure
        if(!empty($this->errors)) {
            return $is_valid;
        }

        // we have all the data, now to validate it
        $tree_id = $data['tree_id'];
        $user_id = $data['user_id'];
        $interaction_type = $data['interaction']['type'];
        $interaction_id = $data['interaction']['id'];
        $destination_type = $data['destination']['type'];
        $destination_id = $data['destination']['id'];
        $embed_id = $data['site']['embed_id'];

        // check that it's a valid Tree
        if($this->DB->validate_tree_id($tree_id) === false) {
            $this->errors[] = 'Invalid tree_id.';
            // return here because the next ones will get messed up if this isn't valid
            return false;
        }

        // check that it's a valid Embed ID
        if($this->DB->validate_embed($embed_id) === false) {
            $this->errors[] = 'Invalid embed_id.';
        }

        // check that it's a valid interaction type
        if($this->DB->validate_interaction_type($interaction_type) === false) {
            $this->errors[] = 'Invalid interaction type.';
		}

        // check that it's a valid destination type
        if($this->DB->validate_state_type($destination_type) === false) {
            $this->errors[] = 'Invalid destination type.';
		}

        // if it's an option interaction, check that it's a valid option_id
        if($interaction_type === 'question' && $this->DB->validate_el_type_id($tree_id, $interaction_id, $interaction_type) === false) {
            $this->errors[] = 'Invalid interaction id.';
        }

        // if it's a question or end destination, check that it's a valid id for that type
        if(($destination_type === 'question' || $destination_type === 'end') && $this->DB->validate_el_type_id($tree_id, $destination_id, $destination_type) === false) {
            $this->errors[] = 'Invalid destination id.';
        }

        // check that it's a valid user_id
        if(Utility\is_slug($user_id) === false) {
            $this->errors[] = 'Invalid user_id.';
        }

        // if we have don't have any errors, it's valid!
        if(empty($this->errors)) {
            $is_valid = true;
        }

        return $is_valid;

    }

    /**
    * Inserts a new interaction
    * @param $data (ARRAY) See validate for structure
    * @return (ARRAY)
    */
    protected function insert($data) {
        $response = [];
        // make sure it's valid
        $is_valid = $this->validate($data);

        // if validation doesn't pass, return the errors
        if($is_valid !== true) {
            return $this->errors;
        }

        $tree_id = $data['tree_id'];
        $user_id = $data['user_id'];
        $interaction_type = $this->DB->get_interaction_type($data['interaction']['type']);
        $interaction_id = $data['interaction']['id'];
        $destination_type = $this->DB->get_state_type($data['destination']['type']);
        $destination_id = $data['destination']['id'];
        $embed_id = $data['site']['embed_id'];

        // Get our Parameters ready
        $params = [
                    ':tree_id'              => $tree_id,
                    ':user_id'              => $user_id,
                    ':embed_id'             => $embed_id,
                    ':interaction_type_id'  => $interaction_type['interaction_type_id'],
                    ':state_type_id'        => $destination_type['state_type_id'],
                  ];
        // write our SQL statement
        $sql = 'INSERT INTO '.$this->DB->tables['tree_interaction'].' (
                                            tree_id,
                                            user_id,
                                            embed_id,
                                            interaction_type_id,
                                            state_type_id
                                        )
                                        VALUES(
                                            :tree_id,
                                            :user_id,
                                            :embed_id,
                                            :interaction_type_id,
                                            :state_type_id
                                        )';
        // insert the mc_option into the database
        $stmt = $this->DB->query($sql, $params);

        // success!
        if($stmt !== false) {
            $inserted_interaction_id = $this->DB->lastInsertId();

            $response = [
                            'interaction_id'   => $inserted_interaction_id,
                            'status'           => 'success',
                            'action'           => 'insertInteraction'
                        ];

            // if it's one that has a state_id with it, then let's save that too
            $interactions = ['option', 'history', 'start'];
            if(in_array($interaction_type['interaction_type'], $interactions) && $destination_type['state_type'] !== 'overview') {
                $this->insertState($inserted_interaction_id, $destination_id);
            }

            // if we interacted with an option, save the interaction_id to the elment_interactions
            $interactions = ['option'];
            if(in_array($interaction_type['interaction_type'], $interactions)) {
                $this->insertInteractionElement($inserted_interaction_id, $interaction_id);
            }
        } else {
            // handle errors
            $this->errors[] = 'Insert interaction failed.';
            $this->errors['status'] = 'error';
        }


        if(!empty($this->errors)) {
            return $this->errors;
        }

        return $response;
    }

    /**
    * Saves a state related to an interaction_id. Example:
    * Clicking an option will bring you to a new question state. We want to know
    * what the resulting state (or destination) of this interaction on the option brought someone to.
    * This insertion will allow us to track that.
    *
    * @param $interaction_id (STRING/INT) ID from the `tree_interaction` table
    * @param $state_id (STRING/INT) from the el_id of the resulting state (usually a question_id)
    * @return (MIXED) false on error, (STRING) of the inserted row on success
    */
    private function insertState($interaction_id, $state_id) {
        /**************************************
        **         WARNING!!!!!!             **
        **                                   **
        **   NO VALIDATION OCCURS HERE.      **
        **   ONLY CALL FROM $this->insert()  **
        ***************************************/
        // save the state too
        $params = [
                    ':interaction_id'  => $interaction_id,
                    ':el_id'           => $state_id
                  ];
        // write our SQL statement
        $sql = 'INSERT INTO '.$this->DB->tables['tree_state'].' (
                                            interaction_id,
                                            el_id
                                        )
                                        VALUES(
                                            :interaction_id,
                                            :el_id
                                        )';
        // insert the mc_option into the database
        $stmt = $this->DB->query($sql, $params);

        if($stmt !== false) {
            // return the inserted ID
            return $this->DB->lastInsertId();
        } else {
            // handle errors
            $this->errors[] = 'Insert state failed.';
            return false;
        }
    }

    /**
    * Saves the element interacted with (like a click) related to an interaction_id. Example:
    * Clicking an option will bring you to a new question state. We want to know
    * what element (option) was clicked on. This insertion will allow us to track that.
    *
    * @param $interaction_id (STRING/INT) ID from the `tree_interaction` table
    * @param $el_id (STRING/INT) of the element interacted with (usually an option_id)
    * @return (MIXED) false on error, (STRING) of the inserted row on success
    */
    private function insertInteractionElement($interaction_id, $el_id) {
        /**************************************
        **         WARNING!!!!!!             **
        **                                   **
        **   NO VALIDATION OCCURS HERE.      **
        **   ONLY CALL FROM $this->insert()  **
        ***************************************/

        // save the interaction
        $params = [
                    ':interaction_id'  => $interaction_id,
                    ':el_id'           => $el_id
                  ];
        // write our SQL statement
        $sql = 'INSERT INTO '.$this->DB->tables['tree_interaction_element'].' (
                                            interaction_id,
                                            el_id
                                        )
                                        VALUES(
                                            :interaction_id,
                                            :el_id
                                        )';
        // insert the mc_option into the database
        $stmt = $this->DB->query($sql, $params);

        if($stmt !== false) {
            // return the inserted ID
            return $this->DB->lastInsertId();
        } else {
            // handle errors
            $this->errors[] = 'Insert interaction element failed.';
            return false;
        }
    }

    protected function update($data) {

    }

}
