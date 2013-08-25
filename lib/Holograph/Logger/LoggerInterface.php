<?php
/**
 * Holograph logger interface file
 *
 * @package Holograph
 */

namespace Holograph\Logger;

/**
 * LoggerInterface
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
interface LoggerInterface
{
    /**
     * error
     *
     * @param string $message Message
     * @return void
     */
    public function error($message);

    /**
     * warning
     *
     * @param string $message Message
     * @return void
     */
    public function warning($message);

    /**
     * notice
     *
     * @param string $message Message
     * @return void
     */
    public function notice($message);

    /**
     * info
     *
     * @param string $message Message
     * @return void
     */
    public function info($message);
}
