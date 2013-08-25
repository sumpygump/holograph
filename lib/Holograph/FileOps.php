<?php
/**
 * File ops class file
 *
 * @package Holograph
 */

namespace Holograph;

/**
 * File operations class
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class FileOps
{
    /**
     * Read file from disk
     *
     * @param string $filename Filename
     * @return string
     */
    public function readFile($filename)
    {
        return file_get_contents($filename);
    }

    /**
     * Write file to disk
     *
     * @param string $filename Filename
     * @param string $contents Contents of file to write
     * @return bool
     */
    public function writeFile($filename, $contents)
    {
        return file_put_contents($filename, $contents);
    }

    /**
     * Recursive Glob
     * 
     * @param string $pattern Pattern
     * @param int $flags Flags to pass to glob
     * @param string $path Path to glob in
     * @return void
     */
    public static function rglob($pattern, $flags = 0, $path = '')
    {
        if ($path == '\\' || $path == '/') {
            // We don't want to try to find all the paths from root
            // It takes too long
            return array();
        }

        if (!$path && ($dir = dirname($pattern)) != '.') {
            if ($dir == '\\' || $dir == '/') {
                // This means the pattern starts with root
                // This takes too long
                return array();
            }
            return self::rglob(
                basename($pattern),
                $flags, $dir . DIRECTORY_SEPARATOR
            );
        }

        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);

        foreach ($paths as $p) {
            $files = array_merge(
                $files, self::rglob($pattern, $flags, $p . DIRECTORY_SEPARATOR)
            );
        }

        return $files;
    }

    /**
     * Ensure path exists
     *
     * @param string $path Path
     * @return bool
     */
    public static function ensurePathExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
            return true;
        }

        return true;
    }
}
