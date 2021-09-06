<?php
/**
 * Terminal Logger
 *
 * @package Holograph
 */

namespace Holograph\Logger;

/**
 * Terminal Logger
 *
 * @uses LoggerInterface
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Terminal implements LoggerInterface
{
    /**
     * Terminal instance object
     *
     * @var Qi_Console_Terminal
     */
    protected $_terminal;

    /**
     * Quiet mode
     *
     * @var bool
     */
    protected $_quiet = false;

    /**
     * Verbose mode
     *
     * @var bool
     */
    protected $_verbose = false;

    /**
     * Constructor
     *
     * @param Qi_Console_Terminal $terminal Terminal
     * @return void
     */
    public function __construct($terminal)
    {
        $this->_terminal = $terminal;
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     * @return void
     */
    public function error($message)
    {
    }

    /**
     * Log warning
     *
     * @param string $message Message
     * @return void
     */
    public function warning($message)
    {
        $this->_displayWarning($message);
    }

    /**
     * Log notice (normal message)
     *
     * @param string $message Message
     * @return void
     */
    public function notice($message)
    {
        if ($this->_quiet) {
            return false;
        }

        $this->_displayMessage($message, true, 7);
    }

    /**
     * Log information (verbose)
     *
     * @param string $message Message
     * @return void
     */
    public function info($message)
    {
        if ($this->_quiet || !$this->_verbose) {
            return false;
        }

        $this->_displayMessage(">> " . $message, true, 4);
    }

    /**
     * Set quiet mode flag
     *
     * @param bool $value Flag value
     * @return self
     */
    public function setQuiet($value)
    {
        $this->_quiet = (bool) $value;
        return $this;
    }

    /**
     * Set verbose mode flag
     *
     * @param bool $value Verbose flag
     * @return void
     */
    public function setVerbose($value)
    {
        $this->_verbose = (bool) $value;
        return $this;
    }

    /**
     * Display a warning message
     *
     * @param string $message Warning message
     * @param bool $ensureNewline Whether a new line should be appended
     * @return void
     */
    protected function _displayWarning($message, $ensureNewline = true)
    {
        $this->_displayMessage($message, $ensureNewline, 3); //yellow
    }

    /**
     * Display a message
     *
     * @param mixed $message Message
     * @param mixed $ensureNewline Whether a new line should be appended
     * @param int $color Color to use
     * @return void
     */
    protected function _displayMessage(
        $message,
        $ensureNewline = true,
        $color = 2
    ) {
        if ($ensureNewline && substr($message, -1) != "\n") {
            $message .= "\n";
        }

        $this->_terminal->setaf($color);
        echo $message;
        $this->_terminal->op();
    }

    /**
     * Display an error
     *
     * @param string $message Error message
     * @return void
     */
    protected function _displayError($message)
    {
        echo "\n";
        $this->_terminal->pretty_message($message, 7, 1);
        echo "\n";
    }
}
