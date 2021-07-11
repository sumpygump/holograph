<?php
namespace Holograph\Test;

use \BaseTestCase;
use Holograph\Builder;
use Holograph\Logger\Memory;

/**
 * ClientTest
 *
 * @uses BaseTestCase
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class ClientTest extends BaseTestCase
{
    /**
     * Logging object
     *
     * @var mixed
     */
    public $logger;

    /**
     * Set up before test
     *
     * @return void
     */
    public function setUp(): void
    {
        //$this->logger = new Memory();

        //$this->_object = new Builder(array(), $this->logger);
    }

    /**
     * testConstruct
     *
     * @return void
     */
    public function testConstruct()
    {
        $this->expectException(\ArgumentCountError::class);
        $client = new \Holograph\Client();
    }
}
