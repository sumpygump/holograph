<?php
/**
 * Base Test Case class file
 *
 * @package Holograph
 */

use PHPUnit\Framework\TestCase;

/**
 * Base Test Case
 * 
 * @uses TestCase
 * @package Holograph
 * @subpackage Tests
 * @author Jansen Price <jansen.price@gmail.com>
 */
class BaseTestCase extends TestCase
{
    /**
     * Storage of object being tested
     *
     * @var object
     */
    protected $_object;
}
