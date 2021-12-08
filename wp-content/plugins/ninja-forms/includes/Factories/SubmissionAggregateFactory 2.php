<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Handlers\SubmissionAggregate;

use NinjaForms\Includes\Handlers\SubmissionAggregateCsvExportAdapter;

use NinjaForms\Includes\Database\CalderaSubmissionDataSource as CalderaSubmissionDataSource;
use NinjaForms\Includes\Database\CptSubmissionDataSource as CptSubmissionDataSource;

class SubmissionAggregateFactory
{

    /**
     * Construct SubmissionAggregate class with data sources
     * 
     * @return SubmissionAggregate 
     */
    public function submissionAggregate( ): SubmissionAggregate
    {
        $submissionAggregate = new SubmissionAggregate();

        if($this->cfTablesExist()){

            $submissionAggregate->addDataSource($this->makeCalderaDataSource());
        }
        
        $submissionAggregate->addDataSource($this->makeCptSubmissionDataSource());
   
        return $submissionAggregate;
    }

    /**
     * Constructs SubmissionAggregateCsvExportAdapter with SubmissionAggregate
     * 
     * @return SubmissionAggregateCsvExportAdapter 
     */
    public function SubmissionAggregateCsvExportAdapter( ): SubmissionAggregateCsvExportAdapter
    {
        $submissionAggregate = $this->submissionAggregate();

        $submissionAggregateCsvExportAdapter = new SubmissionAggregateCsvExportAdapter($submissionAggregate);

        return $submissionAggregateCsvExportAdapter;
    }

    /**
     * Construct a Caldera submissions data source
     *
     * @return CalderaSubmissionDataSource
     */
    public function makeCalderaDataSource(): CalderaSubmissionDataSource
    {
        return new CalderaSubmissionDataSource();
    }

    /**
     * Construct a Ninja Forms CPT data source
     *
     * @return CptSubmissionDataSource
     */
    public function makeCptSubmissionDataSource(): CptSubmissionDataSource
    {
        return new CptSubmissionDataSource();
    }

    /**
     * Check that both CF entry and values tables exist
     *
     * @return boolean
     */
    protected function cfTablesExist( ): bool
    {
        global $wpdb;

        $return = false;
        $entriesTable = $wpdb->prefix . 'cf_form_entries';
        $valuesTable = $wpdb->prefix . 'cf_form_entry_values';

        $entriesQuery = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $entriesTable ) );
        $valuesQuery = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $valuesTable ) );
        
        if (  $wpdb->get_var( $entriesQuery ) == $entriesTable &&  $wpdb->get_var( $valuesQuery ) == $valuesTable ) {
            $return = true;
        }

        return $return;
    }
}
