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
     * Holograph client
     *
     * @var \Holograph\Client
     */
    protected $_client;

    /**
     * Source directory
     *
     * @var string
     */
    protected $_sourceDir = '';

    /**
     * Destination directory
     *
     * @var string
     */
    protected $_destinationDir = '';

    /**
     * Constructor
     *
     * @param \Holograph\Client $client
     * @return void
     */
    public function __construct($client)
    {
        $this->_client = $client;
    }

    /**
     * Set source directory
     *
     * @param string $source Source directory
     * @return self
     */
    public function setSourceDir($source)
    {
        $this->_sourceDir = $source;
        return $this;
    }

    /**
     * Get sourceDir
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->_sourceDir;
    }

    /**
     * Set DestinationDir
     *
     * @param string $destination Destination dir
     * @return self
     */
    public function setDestinationDir($destination)
    {
        $this->_destinationDir = $destination;
        return $this;
    }
    
    /**
     * Get DestinationDir
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->_destinationDir;
    }

    /**
     * Execute
     *
     * @return void
     */
    abstract public function execute($options = array());
}
