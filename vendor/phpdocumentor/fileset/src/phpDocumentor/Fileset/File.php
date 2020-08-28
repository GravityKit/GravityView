<?php

namespace phpDocumentor\Fileset;

class File extends \SplFileInfo
{
    /**
     * Open file for reading, and if it doesn't exist create it.
     *
     * @param string|\SplFileInfo $file
     *
     * @throws \InvalidArgumentException if an invalid type was passed
     */
    public function __construct($file)
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }

        if (!is_string($file)) {
            throw new \InvalidArgumentException(
                'Expected filename or object of type SplFileInfo but received '
                . get_class($file)
            );
        }

        if (empty($file)) {
            throw new \InvalidArgumentException(
                'Expected filename or object of type SplFileInfo but received nothing at all'
            );
        }

        parent::__construct($file);
    }

    /**
     * Returns the mime type for this file.
     *
     * @throws \RuntimeException if finfo failed to load
     *                           and/or mime_content_type is unavailable
     * @throws \LogicException if the mime type could not be interpreted
     *                         from the output of finfo_file
     *
     * @return string
     */
    public function getMimeType()
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            if (!$finfo) {
                throw new \RuntimeException('Failed to open finfo');
            }

            $actualInfo = @finfo_file($finfo, $this->getPathname());
            if (false === $actualInfo) {
                throw new \RuntimeException('Failed to read file info via finfo');
            }
            $mime = strtolower($actualInfo);
            finfo_close($finfo);

            if (!preg_match(
                '/^([a-z0-9]+\/[a-z0-9\-\.]+);\s+charset=(.*)$/', $mime, $matches
            )) {
                throw new \LogicException(
                    'An error parsing the MIME type "'.$mime.'".'
                );
            }

            return $matches[1];
        } elseif (function_exists('mime_content_type')) {
            return mime_content_type($this->getPathname());
        }

        throw new \RuntimeException(
            'The finfo extension or mime_content_type function are needed to '
            .'determine the Mime Type for this file.'
        );
    }

    /**
     * Returns the file contents as a string.
     *
     * @return string
     * @throws \RuntimeException if unable to open the file
     */
    public function fread()
    {
        try {
            $file = $this->openFile('r');
        } catch (\Exception $exc) {
            throw new \RuntimeException('Unable to open file', 0, $exc);
        }

        $result = '';
        foreach ($file as $line) {
            $result .= $line;
        }

        return $result;
    }

    /**
     * Returns the filename, relative to the given root.
     *
     * @param string $root_path The root_path of which this file is composed.
     *
     * @throws \InvalidArgumentException if file is not in the project root.
     *
     * @return string
     *
     * @todo this protected method is unused in this class... can it be removed?
     */
    protected function getFilenameRelativeToRoot($root_path)
    {
        // strip path from filename
        $result = ltrim(
            substr($this->getPathname(), strlen($root_path)),
            DIRECTORY_SEPARATOR
        );

        if ($result === '') {
            throw new \InvalidArgumentException(
                'File "' . $this->getPathname() . '" is not present in the '
                .'given root: ' . $root_path
            );
        }

        return $result;
    }

}
