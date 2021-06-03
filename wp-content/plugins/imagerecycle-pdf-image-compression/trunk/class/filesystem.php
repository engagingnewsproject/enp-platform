<?php

namespace ImageRecycle\Standalone;


define('IR_ITERATOR_ONLYDIR', 1);
define('IR_ITERATOR_ONLYFILE', 2);


class Filesystem
{
    public static function listFiles($dir, $filetype = null, $flags = 0)
    {
        $result = array();

        if (!file_exists($dir) || !is_dir($dir)) {
            return $result;
        }

        //lowercase for filetype
        if (is_array($filetype)) {
            $filetype = array_map('strtolower', $filetype);
        }

        //Try to list using os system commands
        $result = self::listFilesSystem($dir, $filetype, $flags);

        //System function failed or is not available
        if ($result === false) {
            $result = self::listFilesIterator($dir, $filetype, $flags);
        }

        //Remove search path
        foreach ($result as &$file) {
            $file = substr($file, strlen($dir));
        }
        //Remove blank items
        $result = array_filter($result);

        //If result is array of file then sort it
        if (is_array($result)) {
            usort($result, array('ImageRecycle\Standalone\Filesystem', 'alphaSort'));
        }
        return $result;
    }

    private static function alphaSort($a, $b)
    {
        if ($a === $b) {
            return 0;
        }
        $a = strtolower($a);
        $b = strtolower($b);
        $temp = null;
        if (strlen($b) < strlen($a)) {
            $len = strlen($b);
        } else {
            $len = strlen($a);
        }
        for ($i = 0; $i < $len; $i++) {
            if ($a[$i] === $b[$i]) {
                continue;
            }
            return ord($a[$i]) > ord($b[$i]);
        }
        if (strlen($a) < strlen($b)) {
            return -1;
        }
        return 1;
    }

    protected static function listFilesSystem($dir, $filetype = null, $flags = 0)
    {
        //Check if exec function is available
        if (
            ini_get('safe_mode') == 1 || //Not available in safe mode
            !function_exists('exec') || //Check if function exists
            in_array('exec', array_map('trim', explode(', ', ini_get('disable_functions')))) // Check if function is not disabled
        ) {
            return false;
        }

        switch (PHP_OS) {
            case 'Linux':
            case 'Darwin': //OSX
                break;
            case 'Windows':
            case 'WINNT':
            default:
                return false;
        }

        $files = array();

        $cmd = null;
        if ((IR_ITERATOR_ONLYDIR & $flags) || (is_array($filetype) && !(IR_ITERATOR_ONLYFILE & $flags))) {
            $cmd = 'find ' . escapeshellarg($dir) . ' -type d';
        } elseif ((IR_ITERATOR_ONLYFILE & $flags) && !is_array($filetype)) {
            $cmd = 'find ' . escapeshellarg($dir) . ' -type f';
        } elseif (!(IR_ITERATOR_ONLYFILE & $flags) || !is_array($filetype)) {
            $cmd = 'find ' . escapeshellarg($dir);
        }
        if ($cmd) {
            $output = '';
            exec($cmd, $output, $cmdStatus);
            if ($cmdStatus == 0 && is_array($output)) {
                $files = $output;
            } else {
                return false;
            }
        }

        //If the files are filtered we have to run again the search filtering by type
        if (!(IR_ITERATOR_ONLYDIR & $flags) && is_array($filetype)) {
            $cmd = 'find ' . escapeshellarg($dir) . ' -regex ';
            $cmd .= escapeshellarg('.*\.\(' . implode('\|', $filetype) . '\)');
            $output = '';
            exec($cmd, $output, $cmdStatus);
            if ($cmdStatus == 0 && is_array($output)) {
                $files = array_merge($files, $output);
            } else {
                return false;
            }
        }

        return $files;
    }

    /**
     * List files using php directory iterator
     * @param string $dir to start listing inside
     * @param array $filetype filter by filetype
     * @param int $flags
     * @return array of listed files
     */
    protected static function listFilesIterator($dir, $filetype = null, $flags = 0)
    {
        //Load first directory iterator instance
        if (is_string($dir)) {
            $iterator = new \DirectoryIterator($dir);
            return self::listFilesIterator($iterator, $filetype, $flags);
        }

        $files = array();

        foreach ($dir as $node) {
            //Ignore . and ..
            if ($node->isDot()) {
                continue;
            }

            //If flags set to only dir do not process files
            if ((IR_ITERATOR_ONLYDIR & $flags) && !$node->isDir()) {
                continue;
            }

            //Check if filetype is allowed
            if ($node->isFile() && is_array($filetype) && !in_array(strtolower(pathinfo($node->getFilename(), PATHINFO_EXTENSION)), $filetype)) {
                continue;
            }

            //Check if flags provided allow to add file/directory to list
            if (!(IR_ITERATOR_ONLYFILE & $flags) || (IR_ITERATOR_ONLYFILE & $flags) && $node->isFile()) {
                //Add the file/directory to listing
                $files[] = $node->getPathname();
            }

            //In case of directory iterate files inside again
            if ($node->isDir()) {
                $iterator = new \DirectoryIterator($node->getPathname());
                $result = self::listFilesIterator($iterator, $filetype, $flags);
                $files = array_merge($files, $result);
            }
        }

        //Remove search path
        foreach ($files as &$file) {
            $file = substr($file, strlen($dir));
            if ($file === '') {
                unset($file);
            }
        }

        return $files;
    }
}