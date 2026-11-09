<?php

namespace NinjaForms\Includes\Admin\Processes;

/**
 * Scheduled housekeeping for abandoned submission CSV export files.
 *
 * Submission exports are written into the uploads directory and are normally
 * deleted the moment the browser finishes downloading them. When that
 * hand-off breaks - the tab is closed, the export errors, the delete request
 * never arrives - the file is left on disk with submission data in it. This
 * task sweeps those abandoned files once they are old enough that no download
 * can still be using them.
 *
 * @since 3.16.1
 * @see https://github.com/Saturday-Drive/ninja-forms/issues/7856
 */
class ExportFileCleanup
{
    /**
     * Scheduled event that runs the sweep.
     */
    public const HOOK = 'nf_export_file_cleanup';

    /**
     * Directory, below the uploads base directory, used by the batch export.
     */
    public const TEMP_DIRECTORY = 'ninja-forms-tmp';

    /**
     * Age, in seconds, at which an export file is treated as abandoned.
     *
     * Twenty four hours is deliberately generous: a slow export on a large
     * form must never have its file swept out from under an active download.
     */
    public const RETENTION_SECONDS = 86400;

    /**
     * Exact filenames written by the legacy batch export.
     *
     * Legacy exports land in the customer's own uploads folders, alongside
     * their media library, so only these exact shapes may be deleted.
     */
    public const LEGACY_PATTERN = '/^all-subs(-\d+)?\.csv$/';

    /**
     * Schedule the daily sweep and listen for it.
     */
    public function __construct()
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'daily', self::HOOK);
        }

        add_action(self::HOOK, array($this, 'sweep'));
    }

    /**
     * Remove the scheduled sweep.
     *
     * Called on deactivation so a deactivated plugin leaves nothing behind on
     * the site's schedule.
     *
     * @return void
     */
    public static function clearSchedule()
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * Delete every abandoned export file Ninja Forms owns.
     *
     * @return int Number of files deleted.
     */
    public function sweep()
    {
        $deleted = 0;

        foreach ($this->findAbandonedFiles() as $path) {
            if ($this->deleteFile($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Collect every abandoned export file in both export locations.
     *
     * @return array Absolute file paths.
     */
    protected function findAbandonedFiles()
    {
        $baseDir = $this->baseDir();

        if ('' === $baseDir) {
            return array();
        }

        return array_merge(
            $this->findAbandonedTempFiles($baseDir),
            $this->findAbandonedLegacyFiles($baseDir)
        );
    }

    /**
     * Collect abandoned files from the batch export's own directory.
     *
     * That directory belongs exclusively to Ninja Forms, so anything in it old
     * enough to be abandoned is an abandoned export.
     *
     * @param string $baseDir Uploads base directory, without a trailing slash.
     * @return array Absolute file paths.
     */
    protected function findAbandonedTempFiles($baseDir)
    {
        $tempDir = $baseDir . '/' . self::TEMP_DIRECTORY;

        if (!is_dir($tempDir)) {
            return array();
        }

        $found = array();

        foreach ($this->glob($tempDir . '/*') as $path) {
            if (is_file($path) && $this->isAbandoned($path)) {
                $found[] = $path;
            }
        }

        return $found;
    }

    /**
     * Collect abandoned files written by the legacy batch export.
     *
     * The legacy export writes into the customer's normal uploads folders, so
     * a candidate must match a Ninja Forms export name exactly and must not be
     * a file the customer added to their media library.
     *
     * @param string $baseDir Uploads base directory, without a trailing slash.
     * @return array Absolute file paths.
     */
    protected function findAbandonedLegacyFiles($baseDir)
    {
        $candidates = array_merge(
            $this->glob($baseDir . '/all-subs*.csv'),
            $this->glob($baseDir . '/[0-9][0-9][0-9][0-9]/[0-9][0-9]/all-subs*.csv')
        );

        $found = array();

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }

            if (!preg_match(self::LEGACY_PATTERN, basename($path))) {
                continue;
            }

            if (!$this->isAbandoned($path)) {
                continue;
            }

            if ($this->isMediaLibraryFile($baseDir, $path)) {
                continue;
            }

            $found[] = $path;
        }

        return $found;
    }

    /**
     * Has this file been on disk long enough that no download can want it?
     *
     * @param string $path Absolute file path.
     * @return bool
     */
    protected function isAbandoned($path)
    {
        $modified = filemtime($path);

        if (false === $modified) {
            return false;
        }

        return ($modified + self::RETENTION_SECONDS) < time();
    }

    /**
     * Is this file registered in the customer's media library?
     *
     * A file whose status can't be checked counts as registered, so it is kept.
     *
     * @param string $baseDir Uploads base directory, without a trailing slash.
     * @param string $path    Absolute file path.
     * @return bool
     */
    protected function isMediaLibraryFile($baseDir, $path)
    {
        $uploads = $this->uploads();

        if (empty($uploads['baseurl'])) {
            return true;
        }

        $relative = ltrim(substr($path, strlen($baseDir)), '/\\');
        $url = rtrim($uploads['baseurl'], '/\\') . '/' . str_replace('\\', '/', $relative);

        return 0 < (int) attachment_url_to_postid($url);
    }

    /**
     * Delete a file, tolerating a filesystem that will not allow it.
     *
     * @param string $path Absolute file path.
     * @return bool Whether the file is gone.
     */
    protected function deleteFile($path)
    {
        if (!is_writable($path)) {
            return false;
        }

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        @unlink($path);

        return !file_exists($path);
    }

    /**
     * Matching paths for a glob pattern, as an array in every case.
     *
     * @param string $pattern
     * @return array
     */
    protected function glob($pattern)
    {
        $matches = glob($pattern, GLOB_NOSORT);

        return is_array($matches) ? $matches : array();
    }

    /**
     * The uploads base directory, without a trailing slash.
     *
     * @return string Empty when the uploads directory cannot be resolved.
     */
    protected function baseDir()
    {
        $uploads = $this->uploads();

        if (empty($uploads['basedir'])) {
            return '';
        }

        return rtrim($uploads['basedir'], '/\\');
    }

    /**
     * Uploads directory information.
     *
     * @return array
     */
    protected function uploads()
    {
        $uploads = wp_get_upload_dir();

        return is_array($uploads) ? $uploads : array();
    }
}
