<?php

/**
 * File
 * API for interacting with files
 *
 * @author      Statamic
 * @package     API
 * @copyright   2014 Statamic
 */

use Symfony\Component\Filesystem\Filesystem as Filesystem;

class File
{

    public function __constructor() {

    }

    /**
     * Determine if files exist.
     *
     * @param  mixed  $path
     * @return Boolean true if the file exists, false otherwise
     */
    public static function exists($files)
    {
        $fs = new Filesystem();
        
        return $fs->exists($files);
    }


    /**
     * Get the contents of a file.
     *
     * <code>
     *      // Get the contents of a file
     *      $contents = File::get(Config::getContentRoot().'about.php');
     *
     *      // Get the contents of a file or return a default value if it doesn't exist
     *      $contents = File::get(Config::getContentRoot().'about.php', 'Default Value');
     * </code>
     *
     * @param  string  $path  Path to get file
     * @param  mixed   $default  Default value if path is not found or content cannot be loaded
     * @return string
     */
    public static function get($path, $default = null)
    {
        return (File::exists($path)) ? file_get_contents($path) : Helper::resolveValue($default);
    }


    /**
     * Atomically dump content into a file.
     *
     * @param  string  $filename  Path of file to store
     * @param  string  $content   Content to store
     * @return void
     */
    public static function put($filename, $content, $mode = 0666)
    {
        $fs = new Filesystem();
        
        $fs->dumpFile($filename, $content, $mode);
    }


    /**
     * Append to a file.
     *
     * @param  string  $path  Path of file to append
     * @param  string  $data  Data to append to file
     * @return int
     */
    public static function append($path, $data)
    {
        Folder::make(dirname($path));
        return file_put_contents($path, $data, LOCK_EX | FILE_APPEND);
    }


    /**
     * Prepend to a file.
     *
     * @param  string  $path  Path of file to prepend
     * @param  string  $data  Data to prepend to file
     * @return int
     */
    public static function prepend($path, $data)
    {
        Folder::make(dirname($path));
        return file_put_contents($path, $data . File::get($path, ""), LOCK_EX);
    }


    /**
     * Removes files or directories
     *
     * @param string|array|  $files  A filename or an array of files
     * @return fixed
     */
    public static function delete($files)
    {
        $fs = new Filesystem();
        
        $fs->remove($files);
    }


    /**
     * Move a file to a new location.
     *
     * @param string  $origin    The origin filename or directory
     * @param string  $target    The new filename or directory
     * @param Boolean $overwrite Whether to overwrite the target if it already exists
     * @return resource
     */
    public static function move($origin, $target, $overwrite = false)
    {
        $fs = new Filesystem();

        $fs->rename($origin, $target, $overwrite);
    }

    /**
     * Rename a file or directory
     *
     * @param string  $origin    The origin filename or directory
     * @param string  $target    The new filename or directory
     * @param Boolean $overwrite Whether to overwrite the target if it already exists
     * @return resource
     */
    public static function rename($origin, $target, $overwrite = false)
    {
        $fs = new Filesystem();

        if ( ! self::inBasePath($origin)) {
            $origin = Path::assemble(BASE_PATH, $origin);
        } 

        if ( ! self::inBasePath($target)) {
            $target = Path::assemble(BASE_PATH, $target);
        } 

        $fs->rename($origin, $target, $overwrite);
    }


    /**
     * Check if a path is inside Statamic's BASE_PATH
     *
     * @param string  $path  The filename or directory
     * @return boolean
     */
    public static function inBasePath($path) {
        return stripos($path, BASE_PATH);
    }

    /**
     * Upload a file.
     *
     * @param string  $file  Name of file
     * @param string  $target  target of file
     * @param string  $filename  Name of new file
     * @return bool
     **/
    public static function upload($file, $target, $filename = null)
    {
        Folder::make($target);
        return move_uploaded_file($file, $target . '/' . $filename);
    }


    /**
     * Copies a file.
     *
     * This method only copies the file if the origin file is newer than the target file.
     *
     * By default, if the target already exists, it is not overridden.
     *
     * @param string  $originFile The original filename
     * @param string  $targetFile The target filename
     * @param boolean $override   Whether to override an existing file or not
     */
    public static function copy($originFile, $targetFile, $override = false)
    {
        $fs = new Filesystem();

        $fs->copy($originFile, $targetFile, $override);
    }


    /**
     * Builds a file with YAML front-matter
     *
     * @param array  $data  Front-matter data
     * @param string  $content  Content
     * @return string
     */
    public static function buildContent(Array $data, $content)
    {
        $file_content  = "---\n";
        $file_content .= preg_replace('/\A^---\s/ism', "", YAML::dump($data));
        $file_content .= "---\n";
        $file_content .= $content;

        return $file_content;
    }


    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path  Path of file to extract
     * @return string
     */
    public static function getExtension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }


    /**
     * Get the file type of a given file.
     *
     * @param  string  $path  Path of file to check for type
     * @return string
     */
    public static function getType($path)
    {
        return filetype($path);
    }


    /**
     * Get the file size of a given file.
     *
     * @param  string  $path  Path of file
     * @return int
     */
    public static function getSize($path)
    {
        return filesize($path);
    }

    /**
     * Get the human file size of a given file.
     *
     * @param  string  $path  Path of file
     * @return int
     */
    public static function getHumanSize($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }


    /**
     * Get the file's last modification time.
     *
     * @param  string  $path  Path of file
     * @return int
     */
    public static function getLastModified($path) {
        return filemtime($path);
    }


    /**
     * Checks to see if a given $file is writable
     *
     * @param string  $file  File to check
     * @return bool
     */
    public static function isWritable($file)
    {
        return is_writable($file);
    }


    /**
     * Checks to see if $file_1 is newer than $file_2
     *
     * @param string  $file  File to compare
     * @param string  $compare_against  File to compare against
     * @return bool
     */
    public static function isNewer($file, $compare_against)
    {
        return (File::getLastModified($file) > File::getLastModified($compare_against));
    }


    /**
     * Get a file MIME type by extension.
     *
     * <code>
     *      // Determine the MIME type for the .tar extension
     *      $mime = File::mime('tar');
     *
     *      // Return a default value if the MIME can't be determined
     *      $mime = File::mime('ext', 'application/octet-stream');
     * </code>
     *
     * @param  string  $extension
     * @param  string  $default
     * @return string
     */
    public static function getMime($extension, $default = 'application/octet-stream')
    {
        $mimes = Config::get('mimes');

        if ( ! array_key_exists($extension, $mimes)) return $default;

        return (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
    }

    /**
     * Resolves a path's MIME type
     *
     * @param string  $path  Path to resolve
     * @return string
     */
    public static function resolveMime($path) {
        $extension = self::getExtension($path);

        return self::getMime($extension);
    }

    /**
     * Cleans up a file name
     *
     * @param string  $path  Path and file name to clean up
     * @return string
     */
    public static function cleanFilename($path)
    {
        $extension = self::getExtension($path);
        $path = str_replace('.'.$extension, '', $path);

        return Slug::make($path) . '.' . $extension;
    }

    /**
     * Removes any filesystem path outside of the site root
     *
     * @param string  $path  Path to trim
     * @return string
     */
    public static function cleanURL($path)
    {
        return str_replace(Path::standardize(BASE_PATH), "", $path);
    }

    /**
     * Determine if a file is of a given type.
     *
     * The Fileinfo PHP extension is used to determine the file's MIME type.
     *
     * <code>
     *      // Determine if a file is a JPG image
     *      $jpg = File::is('jpg', 'path/to/file.jpg');
     *
     *      // Determine if a file is one of a given list of types
     *      $image = File::is(array('jpg', 'png', 'gif'), 'path/to/file.jpg');
     * </code>
     *
     * @param  array|string  $extensions
     * @param  string        $path
     * @return bool
     */
    public static function is($extensions, $path)
    {
        $mimes = Config::get('mimes');

        if (self::exists($path)) {

            if (function_exists('finfo_file')) {
                $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
            } elseif (function_exists('mime_content_type')) {
                $mime = mime_content_type($path);
            } else {
                Log::warn("Your PHP config is missing both `finfo_file()` and `mime_content_type()` functions. We recommend enabling one of them.", "system", "File");

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = array_get($mimes, $ext);

                if (is_array($mime)) {
                    $mime = $mime[0];
                }
            }

            // The MIME configuration file contains an array of file extensions and
            // their associated MIME types. We will loop through each extension the
            // developer wants to check and look for the MIME type.
            foreach ((array) $extensions as $extension)
            {
                if (isset($mimes[$extension]) && in_array($mime, (array) $mimes[$extension]))
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if a file is an image
     *
     * @param  string  $file  File to evaluate
     * @return bool
     **/
    public static function isImage($file)
    {
        return self::is(array('jpg', 'jpeg', 'png', 'gif'), $file);
    }


    /**
     * Returns whether the file path is an absolute path.
     *
     * @param string $file A file path
     *
     * @return Boolean
     */
    public function isAbsolutePath($file)
    {
        $fs = new Filesystem();

        return $fs->isAbsolutePath($file);
    }

}