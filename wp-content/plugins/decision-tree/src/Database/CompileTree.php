<?php

/**
* Manages the rendering and sending of the correct template
* @since 0.0.1
* @author jones.jeremydavid@gmail.com
*/

namespace Cme\Database;

class CompileTree extends DB {
    protected $DB,
              $compiled;

    public function __construct($tree_id) {
        $this->DB = new DB();
        // kick off build process
        // get the tree by slug or ID
        if(\Cme\Utility\is_slug($tree_id)) {
            $tree = $this->DB->get_tree_by_slug($tree_id);
            // set the real tree id
            $tree_id = $tree['tree_id'];
        } else {
            $tree = $this->DB->get_tree($tree_id);
        }

        $this->compiled = $tree;
        $this->compiled['starts'] = $this->compile_starts($tree_id);
        $this->compiled['groups'] = $this->compile_groups($tree_id);
        $this->compiled['questions'] = $this->compile_questions($tree_id);
        $this->compiled['ends'] = $this->compile_ends($tree_id);
        // figure out total paths and longest path
        $this->compiled['stats'] = $this->compute_paths($this->compiled['questions']);
        // encode to JSON
        $pretty_json = json_encode($this->compiled, JSON_PRETTY_PRINT);
        $minified_json = json_encode($this->compiled);
        // write to file
        $this->write_file($tree['tree_slug'], $pretty_json);
        $this->write_file($tree['tree_slug'].'.min', $minified_json);
        // return the json, if they need it
        return $pretty_json;
    }

    protected function write_file($filename, $contents) {
        file_put_contents(TREE_PATH.'/data/'.$filename.'.json', $contents);
    }

    protected function compile_starts($tree_id) {
        return $this->DB->get_starts($tree_id);
    }

    protected function compile_groups($tree_id) {
        $groups = $this->DB->get_groups($tree_id);
        $i = 0;

        foreach($groups as $group) {

            $groups[$i]['questions'] = $this->DB->get_questions_by_group($group['group_id']);
            $i++;
        }


        return $groups;

    }


    protected function compile_questions($tree_id) {
        $questions = $this->DB->get_questions($tree_id);
        $i = 0;

        foreach($questions as $question) {
            $questions[$i]['content'] = addslashes($questions[$i]['content']);
            $questions[$i]['description'] = addslashes($questions[$i]['description']);
            $questions[$i]['options'] = $this->compile_options($question['question_id']);
            $i++;
        }


        return $questions;

    }

    protected function compile_options($question_id) {
        return $this->DB->get_options($question_id);
    }

    protected function compile_ends($tree_id) {
        return $this->DB->get_ends($tree_id);
    }

    /**
    * Starts with the first question and computes all possible paths
    *
    */
    protected function compute_paths($questions) {
        // empty array to hold the paths
        $paths = [];
        $path_i = 0;

        // add the first question as a new array item in that array
        $paths[$path_i][] = 'Question '.$questions[0]['question_id'];
        $paths = $this->process_paths($paths, $path_i, $this->DB->get_options($questions[0]['question_id']));
        return ['total_paths'=>count($paths),'longest_path'=>$this->largest_array_count($paths), 'path_ends'=>$this->path_end_numbers($paths)];
        /*
        return $paths;*/
    }

    /**
    * Recursive function to follow a question's options through to the end and add it to the $paths array
    * @param $paths = array of paths
    * @param $path_i = index of where we're at in the paths array
    * @param $options the options for the question you want to process
    * @return $paths array with all paths added to it
    */
    protected function process_paths($paths, $path_i, $options) {
        // clone our path ahead of time so we have a clean one in case we need to clone it
        $cloned_path = $paths[$path_i];
        $option_i = 0;

        foreach($options as $option) {
            // clone it if it's not the first option. It's a new path
            if($option_i !== 0) {
                // increase our path counter to the length of the array +1.
                // we can't do $path_i++ because it'll likely already be taken by another recursive
                // loop on the same function
                $path_i = count($paths);
                // set the path in the array
                $paths[$path_i] = $cloned_path;
            }
            // add the destination to the path
            $paths[$path_i][] = $option['destination_type'] . ' '. $option['destination_id'];

            // now recursively process ITS paths if it's a question
            if($option['destination_type'] === 'question') {

                $paths = $this->process_paths($paths, $path_i, $this->DB->get_options($option['destination_id']));
            }

            $option_i++;
        }
        // return all the paths
        return $paths;
    }

    public function largest_array_count($arr) {
        $most = 0;
        foreach($arr as $a) {
            $count = count($a);
            if($most < $count) {
                $most = $count;
            }
        }
        return $most;
    }

    public function path_end_numbers($paths) {
        $ends = $this->compiled['ends'];
        $path_ends = [];
        foreach($ends as $end) {
            $path_ends[$end['end_id']] = ['title'=>$end['title']];
            $path_count = 0;
            foreach($paths as $path) {
                $path_end = array_pop($path);
                if($path_end === 'end '.$end['end_id']) {
                    $path_count++;
                }
            }
            // add it to the count
            $path_ends[$end['end_id']]['count'] = $path_count;
            $path_ends[$end['end_id']]['percentage'] = round($path_count/count($paths) * 100, 2);
        }
        return $path_ends;
    }


}
