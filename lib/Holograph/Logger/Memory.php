<?php
/**
 * Memory logger class file
 *
 * @package Holograph
 */

namespace Holograph\Logger;

/**
 * Stores log message in memory
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Memory implements LoggerInterface
{
    /**
     * Messages
     *
     * @var array
     */
    protected $_messages = array(
        'error'   => array(),
        'warning' => array(),
        'notice'  => array(),
        'info'    => array(),
    );

    /**
     * error
     *
     * @param string $message Message
     * @return void
     */
    public function error($message)
    {
        $this->_messages['error'][] = $message;
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
    }

    /**
     * notice
     *
     * @param string $message Message
     * @return void
     */
    public function notice($message)
    {
        $this->_messages['notice'][] = $message;
    }

    /**
     * info
     *
     * @param string $message Message
     * @return void
     */
    public function info($message)
    {
        $this->_messages['info'][] = $message;
    }
}
