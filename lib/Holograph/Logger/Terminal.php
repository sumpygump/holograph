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
    protected $terminal;

    /**
     * Quiet mode
     *
     * @var bool
     */
    protected $quiet = false;

    /**
     * Verbose mode
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Constructor
     *
     * @param Qi_Console_Terminal $terminal Terminal
     * @return void
     */
    public function __construct($terminal)
    {
        $this->terminal = $terminal;
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
        $this->displayWarning($message);
    }

    /**
     * Log notice (normal message)
     *
     * @param string $message Message
     * @return void
     */
    public function notice($message)
    {
        if ($this->quiet) {
            return false;
        }

        $this->displayMessage($message, true, 7);
    }

    /**
     * Log information (verbose)
     *
     * @param string $message Message
     * @return void
     */
    public function info($message)
    {
        if ($this->quiet || !$this->verbose) {
            return false;
        }

        $this->displayMessage(">> " . $message, true, 4);
    }

    /**
     * Set quiet mode flag
     *
     * @param bool $value Flag value
     * @return self
     */
    public function setQuiet($value)
    {
        $this->quiet = (bool) $value;
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
        $this->verbose = (bool) $value;
        return $this;
    }

    /**
     * Display a warning message
     *
     * @param string $message Warning message
     * @param bool $ensureNewline Whether a new line should be appended
     * @return void
     */
    protected function displayWarning($message, $ensureNewline = true)
    {
        $this->displayMessage($message, $ensureNewline, 3); //yellow
    }

    /**
     * Display a message
     *
     * @param mixed $message Message
     * @param mixed $ensureNewline Whether a new line should be appended
     * @param int $color Color to use
     * @return void
     */
    protected function displayMessage(
        $message,
        $ensureNewline = true,
        $color = 2
    ) {
        if ($ensureNewline && substr($message, -1) != "\n") {
            $message .= "\n";
        }

        $this->terminal->setaf($color);
        echo $message;
        $this->terminal->op();
    }

    /**
     * Display an error
     *
     * @param string $message Error message
     * @return void
     */
    protected function displayError($message)
    {
        echo "\n";
        $this->terminal->pretty_message($message, 7, 1);
        echo "\n";
    }
}
