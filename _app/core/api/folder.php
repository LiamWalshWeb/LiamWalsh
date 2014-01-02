<?php
use Symfony\Component\Finder\Finder as Finder;
use FilesystemIterator as fIterator;

/**
 * Folder
 * API for interacting with folders (directories) on the server
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class Folder
{
    /**
     * Create a new directory.
     *
     * @param  string  $path  Path of folder to create
     * @param  int     $chmod  CHMOD settings for the folder
     * @return bool
     */
    public static function make($path, $chmod = 0777)
    {
        umask(0);
        return ( ! is_dir($path)) ? mkdir($path, $chmod, TRUE) : TRUE;
    }


    /**
     * Move a directory from one location to another.
     *
     * @param  string  $source  Path of source folder to move
     * @param  string  $destination  Destination path of folder
     * @param  int     $options  Options
     * @return boolean
     */
    public static function move($source, $destination, $options = fIterator::SKIP_DOTS)
    {
        return self::copy($source, $destination, TRUE, $options);
    }


    /**
     * Recursively copy directory contents to another directory.
     *
     * @param  string  $source  Path of source folder to copy
     * @param  string  $destination  Destination path for new copy of folder
     * @param  bool    $delete  Delete the origin copy?
     * @param  int     $options  Options
     * @return bool
     */
    public static function copy($source, $destination, $delete = FALSE, $options = fIterator::SKIP_DOTS)
    {
        if ( ! is_dir($source)) return FALSE;

        // First we need to create the destination directory if it doesn't already exist.
        // Our make() method takes care of the check.
        self::make($destination);

        $items = new fIterator($source, $options);

        foreach ($items as $item)
        {
            $location = $destination.DIRECTORY_SEPARATOR.$item->getBasename();

            // If the file system item is a directory, we will recurse the
            // function, passing in the item directory. To get the proper
            // destination path, we'll add the basename of the source to
            // to the destination directory.
            if ($item->isDir())
            {
                $path = $item->getRealPath();

                if (! static::copy($path, $location, $delete, $options)) return FALSE;

                if ($delete) @rmdir($item->getRealPath());
            }
            // If the file system item is an actual file, we can copy the
            // file from the bundle asset directory to the public asset
            // directory. The "copy" method will overwrite any existing
            // files with the same name.
            else
            {
                if(! copy($item->getRealPath(), $location)) return FALSE;

                if ($delete) @unlink($item->getRealPath());
            }
        }

        unset($items);
        if ($delete) @rmdir($source);

        return TRUE;
    }


    /**
     * Recursively delete a directory.
     *
     * @param  string  $directory  Path of folder to delete
     * @param  bool    $preserve  Should we preserve the outer most directory and only empty the contents?
     * @return void
     */
    public static function delete($directory, $preserve = FALSE)
    {
        if ( ! is_dir($directory)) return;

        $items = new fIterator($directory);

        foreach ($items as $item)
        {
            // If the item is a directory, we can just recurse into the
            // function and delete that sub-directory, otherwise we'll
            // just delete the file and keep going!
            if ($item->isDir())
            {
                static::delete($item->getRealPath());
            }
            else
            {
                @unlink($item->getRealPath());
            }
        }

        unset($items);
        if ( ! $preserve) @rmdir($directory);
    }


    /**
     * Empty the specified directory of all files and folders.
     *
     * @param  string  $directory  Path of folder to empty
     * @return void
     */
    public static function wipe($directory)
    {
        self::delete($directory, TRUE);
    }


    /**
     * Get the most recently modified file in a directory.
     *
     * @param  string       $directory  Path of directory to query
     * @param  int          $options  Options
     * @return SplFileInfo
     */
    public static function latest($directory, $options = fIterator::SKIP_DOTS)
    {
        $latest = NULL;

        $time = 0;

        $items = new fIterator($directory, $options);

        // To get the latest created file, we'll simply loop through the
        // directory, setting the latest file if we encounter a file
        // with a UNIX timestamp greater than the latest one.
        foreach ($items as $item)
        {
            if ($item->getMTime() > $time)
            {
                $latest = $item;
                $time = $item->getMTime();
            }
        }

        return $latest;
    }


    /**
     * Checks to see if a given $folder is writable
     *
     * @param string  $folder  Folder to check
     * @return bool
     */
    public static function isWritable($folder)
    {
        return self::exists($folder) && is_writable($folder);
    }


    /**
     * Checks to see if a given $folder exists
     *
     * @param string  $folder  Folder to check
     * @return bool
     */
    public static function exists($folder)
    {
        return is_dir($folder);
    }
}