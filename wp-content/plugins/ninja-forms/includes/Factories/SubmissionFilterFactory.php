<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Entities\SubmissionFilter;

/**
 * Standardize the instantiation of a SubmissionFilter object
 */
class SubmissionFilterFactory
{


    /**
     * Empty submission filter with default properties
     *
     * @return SubmissionFilter
     */
    public function unfiltered(): SubmissionFilter
    {
        $return = new SubmissionFilter();

        return $return;
    }

    /**
     * SubmissionFilter with start and end dates
     *
     * @param integer $startDate Epoch date
     * @param integer $endDate Epoch date
     * @return SubmissionFilter
     */
    public function startEndDates(int $startDate, int $endDate): SubmissionFilter
    {
        $return = $this->unfiltered()
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        return $return;
    }

    /**
     * Add logged in user to SubmissionFilter
     *
     * If user is not logged in, value is set to -1 because all non-logged in
     * user submissions are set to user ID = 0, which is not desired output
     *
     * @return SubmissionFilter
     */
    public function loggedInUser(): SubmissionFilter
    {
        $return = $this->unfiltered();

        $userId = $this->getUserId();

        if(0!==$userId){
            $return->setUserId($userId);
        }else{
            $return->setUserId(-1);
        }

        return $return;
    }

    /**
     * Add userId to SubmissionFilter conditionally by WP filter
     *
     * Override SubmissionFilter userId default value if an applied WP filter specifies it 
     *
     * @return SubmissionFilter
     */
    public function maybeLimitByLoggedInUser(): SubmissionFilter
    {
        $current_user = wp_get_current_user();
        $isAdministrator = in_array("administrator", $current_user->roles);

        if($this->provideLimitByLoggedInUserFilter() && !$isAdministrator){
            $return = $this->loggedInUser();
        }else{
            $return = $this->unfiltered();
        }

        return $return;
    }

    /**
     * Return logged in user's ID
     *
     * @return integer If user not logged in, return 0
     */
    protected function getUserId(): int
    {
        $return = \get_current_user_id();

        return $return;
    }


    /**
     * Provide WP filter to add logged in user to SubmissionFilter
     *
     * Default is to retrieve all submissions, so return FALSE.  This applies a
     * filter that enables external code to change default behaviour.
     * 
     * @return boolean
     */
    protected function provideLimitByLoggedInUserFilter(): bool
    {
        $return = \apply_filters('ninja_forms_limit_submissions_to_logged_in_user', false);

        return $return;
    }
}
