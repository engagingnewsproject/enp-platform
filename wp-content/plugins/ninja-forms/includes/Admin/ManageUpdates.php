<?php

namespace NinjaForms\Includes\Admin;

/**
 * Deregister completed or incorrectly structured updates
 */
class ManageUpdates
{

    /**
     * Function to deregister already completed updates from the list of required updates.
     *
     * @since 3.3.14
     *
     * @param $updates (Array) Our array of required updates.
     * @return $updates (Array) Our array of required updates.
     */
    public function removeCompletedUpdates($updates)
    {
        $processed = $this->getRequiredUpdates();
        
        // ensure that $processed value is expected type 'array'
        if(!\is_array($processed)){
            $processed =[];
        }

        // For each update in our list...
        foreach ($updates as $slug => $update) {
           
            // If we have already processed it...
            if (isset($processed[$slug])) {
                // Remove it from the list.
                unset($updates[$slug]);
            }
        }

        if (
            isset($updates['CacheCollateFields'])
            && isset($updates['CacheFieldReconcilliation'])
            && !isset($processed['CacheFieldReconcilliation'])
        ) {

            unset($updates['CacheFieldReconcilliation']);

            $now = $this->getDate();
            // Append the current update to the array.
            $processed['CacheFieldReconcilliation'] = $now;

            // Save it.
            $this->updateRequiredUpdates($processed);
        }

        return $updates;
    }

    /**
     * Function to deregister updates that have required updates that either
     * don't exist, or are malformed

     * @param $updates (Array) Our array of required updates.
     * @return $updates (Array) Our array of required updates.
     */
    public function removeBadUpdates($updates)
    {
        $processed = get_option('ninja_forms_required_updates', array());

        $sorted = array();
        $queue = array();
        // While we have not finished removing bad updates...
        while (count($sorted) < count($updates)) {
            // For each update we wish to run...
            foreach ($updates as $slug => $update) {
                // Migrate the slug to a property.
                $update['slug'] = $slug;
                // If we've not already added this to the sorted list...
                if (!in_array($update, $sorted)) {
                    // If it has requirements...
                    if (!empty($update['requires'])) {
                        $enqueued = 0;
                        // For each requirement...
                        foreach ($update['requires'] as $requirement) {
                            // If the requirement doesn't exist...
                            if (!isset($updates[$requirement])) {
                                // unset the update b/c we are missing requirements
                                unset($updates[$slug]);

                                $nf_bad_update_transient = get_transient('nf_bad_update_requirement');

                                if (!$nf_bad_update_transient) {
                                    // send telemetry so we can keep up with these
                                    Ninja_Forms()->dispatcher()->send(
                                        'incomplete_update',
                                        array(
                                            'update' => $slug,
                                            'missing_requirement' => $requirement
                                        )
                                    );

                                    set_transient('nf_bad_update_requirement', $requirement, 30 * 3600);
                                }
                            }
                            // If the requirement has already been added to the stack...
                            if (in_array($requirement, $queue)) {
                                $enqueued++;
                            } // OR If the requirement has already been processed...
                            elseif (isset($processed[$requirement])) {
                                $enqueued++;
                            }
                        }
                        // If all requirement are met...
                        if ($enqueued == count($update['requires'])) {
                            // Add it to the list.
                            array_push($sorted, $update);
                            // Record that we enqueued it.
                            array_push($queue, $slug);
                        }
                    } // Otherwise... (It has no requirements.)
                    else {
                        // Add it to the list.
                        array_push($sorted, $update);
                        // Record that we enqueued it.
                        array_push($queue, $slug);
                    }
                }
            }
        }
        return $sorted;
    }

    /**
     * Retrieve required updates array from stored location
     *
     * Note that until retrieved value is validated as an array, we do not
     * declare return type to prevent error
     * 
     * @return array
     */
    protected function getRequiredUpdates( )
    {
        $return = get_option('ninja_forms_required_updates', array());

        return $return;
    }

    /**
     * Update value of 'required updates' in storage location
     *
     * @param array $updates
     * @return void
     */
    protected function updateRequiredUpdates(array $updates): void
    {
        update_option('ninja_forms_required_updates', $updates);
    }

    /**
     * Return formatted date string
     *
     * @return string
     */
    protected function getDate( ): string
    {
        date_default_timezone_set('UTC');
        $return = date("Y-m-d H:i:s");

        return $return;
    }
}
