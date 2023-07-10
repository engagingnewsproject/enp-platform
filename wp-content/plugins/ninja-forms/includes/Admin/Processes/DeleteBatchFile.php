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
        $dir = wp_get_upload_dir();
        $fileinfo = pathinfo($filePath);
        $allowed_dir = $dir['basedir'] . '/ninja-forms-tmp' === $fileinfo['dirname'];
        if(!$allowed_dir) {
            $return = __('Permission denied to delete a file in that directory.', 'ninja-forms');
        } else if (\file_exists($filePath)) {

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
