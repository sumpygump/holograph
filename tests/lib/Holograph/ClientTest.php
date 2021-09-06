<?php

namespace Holograph\Test;

use Holograph\Client;
use Holograph\Logger\Memory;
use PHPUnit\Framework\TestCase;
use Qi_Console_ArgV;
use Qi_Console_Terminal;

/**
 * ClientTest
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 */
class ClientTest extends TestCase
{
    /**
     * Object under test
     *
     * @var mixed
     */
    public $object;

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
        $this->logger = new Memory();
        $argv = new Qi_Console_ArgV([]);
        $terminal = new Qi_Console_Terminal();

        $this->object = new Client($argv, $terminal);
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
