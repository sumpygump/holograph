<?php

/**
 * Version class
 *
 * @package Holograph
 */

namespace Holograph;

/**
 * Version
 *
 * This class will track the current version of Holograph
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Version
{
    /**
     * Current version
     */
    public const VERSION = '0.9';

    /**
     * Render version
     *
     * @return string
     */
    public static function renderVersion()
    {
        return 'Holograph ' . self::VERSION . "\n";
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        return self::renderVersion();
    }
}
