<?php
/**
 * Stdout logger class file
 *
 * @package Holograph
 */

namespace Holograph\Logger;

/**
 * Stores log message in memory + write to stdout
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Stdout extends Memory
{
    /**
     * error
     *
     * @param string $message Message
     * @return void
     */
    public function error($message)
    {
        $this->_messages['error'][] = $message;
        file_put_contents("php://stdout", "ERROR: $message\n");
    }

    /**
     * warning
     *
     * @param string $message Message
     * @return void
     */
    public function warning($message)
    {
        $this->_messages['warning'][] = $message;
        file_put_contents("php://stdout", "WARNING: $message\n");
    }
}
