<?php

use Symfony\Component\Finder\Finder as Finder;

class Plugin_Get_Files extends Plugin
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Paramers
        |--------------------------------------------------------------------------
        |
        | Match overrides Extension. Exclusion applies in both cases.
        |
        */

        $match      = $this->fetchParam('match', false);
        $exclude    = $this->fetchParam('exclude', false);
        $extension  = $this->fetchParam('extension', false);
        $in         = $this->fetchParam('in', false);
        $not_in     = $this->fetchParam('not_in', false);
        $file_size  = $this->fetchParam('file_size', false);
        $file_date  = $this->fetchParam('file_date', false);
        $depth      = $this->fetchParam('depth', false);

        if ($file_size) {
            $file_size = Helper::explodeOptions($file_size);
        }

        if ($extension) {
            $extension = Helper::explodeOptions($extension);
        }

        /*
        |--------------------------------------------------------------------------
        | Finder
        |--------------------------------------------------------------------------
        |
        | Get_Files implements most of the Symfony Finder component as a clean
        | tag wrapper mapped to matched filenames.
        |
        */

        $finder = new Finder();

        $finder->in($in);

        // Finder doesn't respect multiple glob options,
        // so this will need to wait until later.
        //
        // $match = str_replace('{{', '{', $match);
        // $match = str_replace('}}', '}', $match);

        /*
        |--------------------------------------------------------------------------
        | Name
        |--------------------------------------------------------------------------
        |
        | Match is the "native" Finder name() method, which is supposed to
        | implement string, glob, and regex. The glob support is only partial,
        | so "extension" is a looped *single* glob rule iterator.
        |
        */

        if ($match) {
            $finder->name($match);
        } elseif ($extension) {
            foreach ($extension as $ext) {
                $finder->name("*.{$ext}");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Exclude
        |--------------------------------------------------------------------------
        |
        | Exclude directories from matching. Remapped to "not in" to allow more
        | intuitive differentiation between filename and directory matching.
        |
        */

        if ($not_in) {
            $finder->exclude($not_in);
        }

        /*
        |--------------------------------------------------------------------------
        | Not Name
        |--------------------------------------------------------------------------
        |
        | Exclude files matching a given pattern: string, regex, or glob.
        |
        */

        if ($exclude) {
            $finder->notName($exclude);
        }

        /*
        |--------------------------------------------------------------------------
        | File Size
        |--------------------------------------------------------------------------
        |
        | Restrict files by size. Can be chained and allows comparison operators.
        |
        */

        if ($file_size) {
            foreach($file_size as $size) {
                $finder->size($size);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | File Date
        |--------------------------------------------------------------------------
        |
        | Restrict files by last modified date. Can use comparison operators, and
        | since/after is aliased to >, and until/before to <.
        |
        */

        if ($file_date) {
            $finder->date($file_date);
        }

        /*
        |--------------------------------------------------------------------------
        | Depth
        |--------------------------------------------------------------------------
        |
        | Recursively traverse directories, starting at 0.
        |
        */

        if ($depth) {
            $finder->depth($depth);
        }

        $matches = $finder->files();

        /*
        |--------------------------------------------------------------------------
        | Return and Parse
        |--------------------------------------------------------------------------
        |
        | This tag returns the matched filenames mapped to {{ file }}.
        |
        */

        $files = array();
        foreach ($matches as $file) {
            $files[] = array(
                'file' => '/' . $in . '/' . $file->getRelativePathname()
            );

            // $files[] = YAML::parse($file->getContents());
        }

        return Parse::tagLoop($this->content, $files);
    }
}
