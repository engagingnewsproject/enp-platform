<?php

/**
 * Writes temporary files for attachment and uploading
 */
class NF_Exports_TempFileWriter {

    /**
     * Content to be written to file
     * 
     * Can send a single string or an array of stringed content
     * @var string|array
     */
    protected $content;

    /**
     *
     * @var resource
     */
    protected $tempFile;

    /**
     * Directory of final file location 
     * @var array
     */
    protected $dir = [];

    /**
     * Temp file name at time of upload, before renaming
     * @var array
     */
    protected $basename = [];

    /**
     * Full file name with path as attached to email
     * @var array
     */
    protected $attachmentFilename = [];

    /**
     * File path information
     * @var array
     */
    protected $fileinfo = [];

    /**
     * Upload directory path
     * @var string
     */
    protected $uploadDir;

    /**
     * Construct with the content to be written
     * @param string $content
     */
    public function __construct($content) {
        if (is_array($content)) {

            $this->content = $content;
        } else {
            $this->content = [$content];
        }
        // set upload director to /uploads
        $dir = wp_upload_dir();
        $this->uploadDir = $dir['path'];
    }

    /**
     * Write files to temporary location
     * @return NfScheduledSubmissionExports\Storage\TempFileWriter
     */
    public function writeFiles() {
        $this->writeTempFile();
        $this->renameFile();
        return $this;
    }

    /**
     * Returns array of temp filenames, first file name if single
     * @param bool $single
     */
    public function getFileInfo(bool $single = false) {
        $this->constructFileInfo();
        if ($single && isset($this->fileinfo[0])) {
            $return = $this->fileinfo[0];
        } else {
            $return = $this->fileinfo;
        }

        return $return;
    }

    public function getAttachmentNames(bool $single = false) {
        if ($single && !empty($this->attachmentFilename)) {
            $arrayKeys = array_keys($this->attachmentFilename);
            $return = $this->attachmentFilename[$arrayKeys[0]];
        } else {
            $return = $this->attachmentFilename;
        }

        return $return;
    }

    protected function constructFileInfo() {
        $this->fileinfo = [];
        foreach ($this->attachmentFilename as $index => $filename) {

            $this->fileinfo[$index] = array_merge(pathinfo($filename), wp_upload_dir());
        }
    }

    /**
     * Generate the FileInfo for a given filename
     * 
     * @param string $filename
     * @return array
     */
    public static function generateFileInfo($filename) {
        $array= array_merge(pathinfo($filename), wp_upload_dir());
        $array['attachmentName']=$filename;
        return $array;
    }

    /**
     * Write contents to temporary file location
     */
    protected function writeTempFile() {
        $path = trailingslashit($this->uploadDir);

        foreach (array_keys($this->content) as $index) {
            // create temporary file
            $tempFilename = tempnam($path, 'Sub');
            $pathinfo = pathinfo($tempFilename);
            $this->dir[$index] = $pathinfo['dirname'];
            $this->basename[$index] = $pathinfo['basename'];

            $this->tempFile[$index] = fopen($tempFilename, 'r+');

            // write to temp file
            fwrite($this->tempFile[$index], $this->content[$index]);
            fclose($this->tempFile[$index]);
        }
    }

    /**
     * Rename temp file to permanent file name
     * @param string $filename
     * @return string
     */
    protected function renameFile() {
        $filename = apply_filters('ninja_forms_submission_csv_name', 'ninja-forms-submission');
        foreach (array_keys($this->content) as $index) {
            // remove a file if it already exists
            if (file_exists($this->dir[$index] . '/' . $filename . "_$index.csv")) {
                unlink($this->dir[$index] . '/' . $filename . "_$index.csv");
            }

            $this->attachmentFilename[$index] = $this->dir[$index] . '/' . $filename . "_$index.csv";
            // move file
            rename($this->dir[$index] . '/' . $this->basename[$index], $this->attachmentFilename[$index]);
        }
    }

    /**
     * Delete file from directory after email with attachment has been sent
     */
    public function dropAttachmentFiles() {
        foreach (array_keys($this->attachmentFilename) as $index) {
            // remove a file if it already exists
            self::dropAttachmentFile($this->attachmentFilename[$index]);
        }
    }

    /**
     * Drop (delete) a given filename
     * 
     * @param string $filename
     */
    public static function dropAttachmentFile($filename) {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

}
