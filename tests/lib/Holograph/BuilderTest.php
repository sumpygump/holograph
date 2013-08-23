<?php
/**
 * Builder Test
 *
 * @package Holograph
 */

namespace Holograph\Test;

use \BaseTestCase;
use Holograph\Builder;

/**
 * BuilderTest
 *
 * @uses BaseTestCase
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class BuilderTest extends BaseTestCase
{
    /**
     * Set up before test
     *
     * @return void
     */
    public function setUp()
    {
        $this->_object = new Builder(array());
    }

    /**
     * Tear down after test
     *
     * @return void
     */
    public function tearDown()
    {
        // clean up
        passthru("rm -rf test-source");
        passthru("rm -rf test-destination");
        passthru("rm -rf test-build");
    }

    /**
     * Test constructor
     *
     * @return void
     * @expectedException PHPUnit_Framework_Error_Warning
     */
    public function testConstruct()
    {
        $builder = new Builder();
    }

    /**
     * Test constructor with empty array as config
     *
     * @return void
     */
    public function testConstructWithConfigEmpty()
    {
        $builder = new Builder(array());
    }

    /**
     * Test constructor with invalid object
     *
     * @return void
     * @expectedException PHPUnit_Framework_Error
     */
    public function testConstructWithIncorrectClient()
    {
        $c = new \StdClass();
        $builder = new Builder(array(), $c);
    }

    public function testConstructWithMockClient()
    {
        $argv = $this->getMock("Qi_Console_ArgV", array(), array(array('foo')));
        $terminal = $this->getMock("Qi_Console_Terminal");

        $client = $this->getMock("Holograph\\Client", array(), array($argv, $terminal));
        $client->expects($this->once())
            ->method("notify")
            ->will($this->returnValue(null));

        $builder = new Builder(array("a: b"), $client);
    }

    public function testGetConfig()
    {
        $config = $this->_object->getConfig();
        $expected = array(
            'title'                => "Style Guide",
            'source'               => "./components",
            'destination'          => "./docs",
            'documentation_assets' => "./templates",
            'compat_mode'          => false,
            'dependencies'         => array("./build"),
            'preprocessor'         => "minify",
            'build'                => "./build/css",
            'main_stylesheet'      => "build/css/screen.css",
        );

        $this->assertEquals($expected, $config);
    }

    public function testGetConfigWhenAddedTo()
    {
        $config = array('source' => 'FFFFFFFFF');

        $builder = new Builder($config);

        $expected = 'FFFFFFFFF';
        $resultConfig = $builder->getConfig();
        $actual = $resultConfig['source'];

        $this->assertEquals($expected, $actual);
    }

    public function testGetConfigAnnotated()
    {
        $doc = $this->_object->getConfigAnnotated();

        $this->assertContains("Holograph configuration", $doc);
        $this->assertContains("Directory to build the final", $doc);
    }

    public function testExecute()
    {
        $result = $this->_object->execute();

        $this->assertEquals(1, $result);
    }

    public function testExecuteSimple()
    {
        mkdir("test-source");
        file_put_contents("test-source/test.css", ".main { color: red}");
        file_put_contents("test-source/test.md", "# Hi there");

        $config = array(
            'source' => 'test-source',
            'destination' => 'test-destination',
            'build' => 'test-build/css',
            'main_stylesheet' => 'test-build/css/screen.css',
        );

        $builder = new Builder($config);
        $result = $builder->execute();

        $this->assertTrue(file_exists("test-destination/static/css/doc.css"));
        $this->assertTrue(file_exists("test-build/css/screen.css"));
        $this->assertTrue(file_exists("test-destination/test.html"));
        $this->assertEquals(0, $result);
    }

    public function testParseSourceFileWithoutYml()
    {
        mkdir("test-source");
        file_put_contents("test-source/test.css", "/*doc\nfoobar\n*/");

        $config = array(
            'source' => 'test-source',
            'destination' => 'test-destination',
            'build' => 'test-build/css',
            'main_stylesheet' => 'test-build/css/screen.css',
        );

        $builder = new Builder($config);

        $added = $builder->parseSourceFile('test-source/test.css');

        $this->assertEquals(0, $added);
    }

    public function testParseSourceFileWithYml()
    {
        mkdir("test-source");
        file_put_contents("test-source/test.css", "/*doc\n---\nfoobar\n---\n*/");

        $config = array(
            'source' => 'test-source',
            'destination' => 'test-destination',
            'build' => 'test-build/css',
            'main_stylesheet' => 'test-build/css/screen.css',
        );

        $builder = new Builder($config);

        $added = $builder->parseSourceFile('test-source/test.css');

        $this->assertEquals(1, $added);
    }

    public function testRunPreprocessorWithNone()
    {
        $config = array(
            'preprocessor' => 'none',
        );

        $builder = new Builder($config);

        $result = $builder->runPreprocessor(array());
        $this->assertNull($result);
    }

    public function testCreateDocumentBlockNoMatch()
    {
        $result = $this->_object->createDocumentBlock('abc', 'def');
        $this->assertFalse($result);
    }

    public function testCreateDocumentBlockWithYml()
    {
        $block = "---\nname: foobar\n---";
        $result = $this->_object->createDocumentBlock($block, 'def');

        $this->assertEquals("Holograph\\DocumentBlock", get_class($result));
    }

    public function testCreateDocumentBlockWithInvalidYml()
    {
        $block = "---\nhi\n---";
        $result = $this->_object->createDocumentBlock($block, 'def');

        $this->assertEquals("Holograph\\DocumentBlock", get_class($result));
    }
}
