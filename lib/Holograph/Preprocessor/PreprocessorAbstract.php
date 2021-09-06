<?php

/**
 * Preprocessor abstract class file
 *
 * @package Holograph
 */

namespace Holograph\Preprocessor;

/**
 * PreprocessorAbstract
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
abstract class PreprocessorAbstract
{
    /**
     * Source directory
     *
     * @var string
     */
    protected $sourceDir = '';

    /**
     * Destination directory
     *
     * @var string
     */
    protected $destinationDir = '';

    /**
     * Set source directory
     *
     * @param string $source Source directory
     * @return self
     */
    public function setSourceDir($source)
    {
        $this->sourceDir = $source;
        return $this;
    }

    /**
     * Get sourceDir
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * Set DestinationDir
     *
     * @param string $destination Destination dir
     * @return self
     */
    public function setDestinationDir($destination)
    {
        $this->destinationDir = $destination;
        return $this;
    }

    /**
     * Get DestinationDir
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Execute
     *
     * @param array $options Options array to pass during execution
     * @return void
     */
    abstract public function execute($options = array());
}
