<?php
/**
 * File
 * API for interacting with files
 *
 * @author      Jack McDade
 * @author      Fred LeBlanc
 * @author      Mubashar Iqbal
 * @package     API
 * @copyright   2013 Statamic
 */
class File
{

    /**
     * Determine if a file exists.
     *
     * @param  string  $path
     * @return bool
     */
    public static function exists($path)
    {
        return file_exists($path);
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
     * Write to a file.
     *
     * @param  string  $path  Path of file to store
     * @param  string  $data  Content to store
     * @return int
     */
    public static function put($path, $data)
    {
        umask(0);
        Folder::make(dirname($path));
        return file_put_contents($path, $data, LOCK_EX);
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
     * Delete a file.
     *
     * @param  string  $path  Path of file to delete
     * @return bool
     */
    public static function delete($path)
    {
        if (static::exists($path)) {
            return @unlink($path);
        }
    }


    /**
     * Move a file to a new location.
     *
     * @param  string  $path  Path of file to move
     * @param  string  $destination  Destination path for the file
     * @return resource
     */
    public static function move($path, $destination)
    {
        return rename($path, $destination);
    }

    /**
     * Upload a file.
     *
     * @param string  $file  Name of file
     * @param string  $destination  Destination of file
     * @param string  $filename  Name of new file
     * @return bool
     **/
    public static function upload($file, $destination, $filename = null)
    {
        Folder::make($destination);
        return move_uploaded_file($file, $destination . '/' . $filename);
    }


    /**
     * Copy a file to a new location.
     *
     * @param  string  $path  Path of file to copy
     * @param  string  $destination  Destination path for the file copy
     * @return resource
     */
    public static function copy($path, $destination)
    {
        return copy($path, $destination);
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
     * Get the file's last modification time.
     *
     * @param  string  $path  Path of file
     * @return int
     */
    public static function getLastModified($path)
    {
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

}