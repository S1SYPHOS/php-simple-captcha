<?php

namespace SimpleCaptcha\Helpers;

use SimpleCaptcha\Helpers\F;

use Exception;


/**
 * The `Dir` class provides methods
 * for dealing with directories on the
 * file system level, like creating,
 * listing, moving, copying or
 * evaluating directories etc.
 *
 * For the Cms, it includes methods,
 * that handle scanning directories
 * and converting the results into
 * children, files and other page stuff.
 *
 * @package   Kirby Filesystem
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier GmbH
 * @license   https://opensource.org/licenses/MIT
 */
class Dir
{
    /**
     * Creates a new directory
     *
     * @param string $dir The path for the new directory
     * @param bool $recursive Create all parent directories, which don't exist
     * @return bool True: the dir has been created, false: creating failed
     * @throws \Exception If a file with the provided path already exists or the parent directory is not writable
     */
    public static function make(string $dir, bool $recursive = true): bool
    {
        if (empty($dir) === true) {
            return false;
        }

        if (is_dir($dir) === true) {
            return true;
        }

        if (is_file($dir) === true) {
            throw new Exception(sprintf('A file with the name "%s" already exists', $dir));
        }

        $parent = dirname($dir);

        if ($recursive === true) {
            if (is_dir($parent) === false) {
                static::make($parent, true);
            }
        }

        if (is_writable($parent) === false) {
            throw new Exception(sprintf('The directory "%s" cannot be created', $dir));
        }

        return mkdir($dir);
    }


    /**
     * Removes a folder including all containing files and folders
     *
     * @param string $dir
     * @return bool
     */
    public static function remove(string $dir): bool
    {
        $dir = realpath($dir);

        if (is_dir($dir) === false) {
            return true;
        }

        if (is_link($dir) === true) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $childName) {
            if (in_array($childName, ['.', '..']) === true) {
                continue;
            }

            $child = $dir . '/' . $childName;

            if (is_link($child) === true) {
                unlink($child);
            } elseif (is_dir($child) === true) {
                static::remove($child);
            } else {
                F::remove($child);
            }
        }

        return rmdir($dir);
    }
}
