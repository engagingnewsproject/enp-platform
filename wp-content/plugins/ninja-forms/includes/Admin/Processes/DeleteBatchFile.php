<?php

namespace NinjaForms\Includes\Admin\Processes;

/**
 * Deletes a file given file path
 * 
 * 
 */
class DeleteBatchFile
{


    /**
     * Delete a file, given a filepath
     *
     * @param string $filePath
     * @return string Result of the attempt to delete file
     */
    public function delete(string $filePath): string
    {
        $return = __('File could not be found for deletion', 'ninja-forms');

        if (\file_exists($filePath)) {

            \unlink($filePath);

            $return = __('File was deleted', 'ninja-forms');

            if (\file_exists($filePath)) {
                // couldn't delete file for some reason
                $return = __('File could not be deleted', 'ninja-forms');
            }
        }

        return $return;
    }
}
