<?php

/**
 * Builder Test
 *
 * @package Holograph
 */

namespace Holograph\Test;

use BaseTestCase;
use Holograph\Builder;
use Holograph\Logger\Memory;

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

        $this->_object = new Builder(array(), $this->logger);
    }

    /**
     * Tear down after test
     *
     * @return void
     */
    public function tearDown(): void
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
     */
    public function testConstruct()
    {
        $this->expectException(\ArgumentCountError::class);
        $builder = new Builder();
    }

    /**
     * Test constructor with empty array as config
     *
     * @return void
     */
    public function testConstructWithConfigEmpty()
    {
        $this->expectException(\ArgumentCountError::class);
        $builder = new Builder(array());
    }

    /**
     * Test constructor with invalid object
     *
     * @return void
     */
    public function testConstructWithIncorrectClient()
    {
        $this->expectException(\TypeError::class);
        $c = new \StdClass();

        $builder = new Builder(array(), $c);
    }

    /**
     * testConstructWithMockLogger
     *
     * @return void
     */
    public function xtestConstructWithMockLogger()
    {
        $argv = $this->getMock("Qi_Console_ArgV", array(), array(array('foo')));

        $terminal = $this->getMock("Qi_Console_Terminal");

        $logger = $this->getMock(
            "Holograph\\Logger\\Terminal",
            array(),
            array($terminal)
        );

        $logger->expects($this->once())
            ->method("info")
            ->will($this->returnValue(null));

        $builder = new Builder(array("a: b"), $logger);
    }

    /**
     * testGetConfig
     *
     * @return void
     */
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
            'port'                 => "3232",
        );

        $this->assertEquals($expected, $config);
    }

    /**
     * testGetConfigWhenAddedTo
     *
     * @return void
     */
    public function testGetConfigWhenAddedTo()
    {
        $config = array('source' => 'FFFFFFFFF');

        $builder = new Builder($config, $this->logger);

        $expected = 'FFFFFFFFF';

        $resultConfig = $builder->getConfig();

        $actual = $resultConfig['source'];

        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetConfigOneItem
     *
     * @return void
     */
    public function testGetConfigOneItem()
    {
        $config = array('source' => 'FFFFFFFFF');

        $builder = new Builder($config, $this->logger);

        $expected = 'FFFFFFFFF';

        $actual = $builder->getConfig('source');

        $this->assertEquals($expected, $actual);
    }

    /**
     * testConfigForItemNotExist
     *
     * @return void
     */
    public function testConfigForItemNotExist()
    {
        $config = array('source' => 'FFFFFFFFF');

        $builder = new Builder($config, $this->logger);

        $expected = '';

        $actual = $builder->getConfig('foobar');

        $this->assertEquals($expected, $actual);
    }

    /**
     * testGetConfigAnnotated
     *
     * @return void
     */
    public function testGetConfigAnnotated()
    {
        $doc = $this->_object->getConfigAnnotated();

        $this->assertStringContainsString("Holograph configuration", $doc);
        $this->assertStringContainsString("Directory to build the final", $doc);
    }

    /**
     * testExecute
     *
     * @return void
     */
    public function testExecute()
    {
        $result = $this->_object->execute();

        $this->assertEquals(1, $result);
    }

    /**
     * testExecuteSimple
     *
     * @return void
     */
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

        $builder = new Builder($config, $this->logger);

        $result = $builder->execute();

        $this->assertTrue(file_exists("test-destination/static/css/doc.css"));
        $this->assertTrue(file_exists("test-build/css/screen.css"));
        $this->assertTrue(file_exists("test-destination/test.html"));
        $this->assertEquals(0, $result);
    }

    /**
     * testParseSourceFileWithoutYml
     *
     * @return void
     */
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

        $builder = new Builder($config, $this->logger);

        $added = $builder->parseSourceStylesheetFile('test-source/test.css');

        $this->assertEquals(0, $added);
    }

    /**
     * testParseSourceFileWithYml
     *
     * @return void
     */
    public function testParseSourceFileWithYml()
    {
        mkdir("test-source");
        file_put_contents(
            "test-source/test.css",
            "/*doc\n---\nfoobar\n---\n*/"
        );

        $config = array(
            'source' => 'test-source',
            'destination' => 'test-destination',
            'build' => 'test-build/css',
            'main_stylesheet' => 'test-build/css/screen.css',
        );

        $builder = new Builder($config, $this->logger);

        $added = $builder->parseSourceStylesheetFile('test-source/test.css');

        $this->assertEquals(1, $added);
    }

    /**
     * testRunPreprocessorWithNone
     *
     * @return void
     */
    public function testRunPreprocessorWithNone()
    {
        $config = array(
            'preprocessor' => 'none',
        );

        $builder = new Builder($config, $this->logger);

        $result = $builder->runPreprocessor(array());
        $this->assertNull($result);
    }

    /**
     * testCreateDocumentBlockNoMatch
     *
     * @return void
     */
    public function testCreateDocumentBlockNoMatch()
    {
        $result = $this->_object->createDocumentBlock('abc', 'def');
        $this->assertFalse($result);
    }

    /**
     * testCreateDocumentBlockWithYml
     *
     * @return void
     */
    public function testCreateDocumentBlockWithYml()
    {
        $block = "---\nname: foobar\n---";

        $result = $this->_object->createDocumentBlock($block, 'def');

        $this->assertEquals("Holograph\\DocumentBlock", get_class($result));
    }

    /**
     * testCreateDocumentBlockWithInvalidYml
     *
     * @return void
     */
    public function testCreateDocumentBlockWithInvalidYml()
    {
        $block = "---\nhi\n---";

        $result = $this->_object->createDocumentBlock($block, 'def');

        $this->assertEquals("Holograph\\DocumentBlock", get_class($result));
    }

    /**
     * testAddDocumentBlock
     *
     * @return void
     */
    public function testAddDocumentBlock()
    {
        $documentBlock = new \Holograph\DocumentBlock(array('name' => 'b'), 'foo.txt');

        $this->_object->addDocumentBlock($documentBlock);

        $blocks = $this->_object->getDocBlocks();

        $block = array_pop($blocks);

        $this->assertEquals('b', $block->name);
    }

    /**
     * testAddDocumentBlockAlreadyAdded
     *
     * @return void
     */
    public function testAddDocumentBlockAlreadyAdded()
    {
        $documentBlock = new \Holograph\DocumentBlock(array('name' => 'b'), 'foo1.txt');
        $documentBlock2 = new \Holograph\DocumentBlock(array('name' => 'b'), 'foo1.txt');

        $this->_object->addDocumentBlock($documentBlock);
        $this->_object->addDocumentBlock($documentBlock2);

        $blocks = $this->_object->getDocBlocks();

        $block = array_pop($blocks);

        $this->assertEquals('b', $block->name);

        $messages = $this->logger->getMessages();
        $warning = array_pop($messages['warning']);

        $this->assertStringContainsString("Warning: Overwriting block with name", $warning);
    }

    /**
     * testAddDocumentBlockChild
     *
     * @return void
     */
    public function testAddDocumentBlockChild()
    {
        $documentBlock = new \Holograph\DocumentBlock(array('name' => 'a'), 'foo1.txt');
        $documentBlock2 = new \Holograph\DocumentBlock(array('name' => 'b', 'parent' => 'a'), 'foo1.txt');

        $this->_object->addDocumentBlock($documentBlock);
        $this->_object->addDocumentBlock($documentBlock2);

        $blocks = $this->_object->getDocBlocks();

        $blockParent = array_pop($blocks);

        $this->assertEquals('a', $blockParent->name);
        $this->assertEquals(array('b' => $documentBlock2), $blockParent->children);
    }

    /**
     * testAddDocumentBlockChildWhenParentDoesntExist
     *
     * @return void
     */
    public function testAddDocumentBlockChildWhenParentDoesntExist()
    {
        $documentBlock = new \Holograph\DocumentBlock(array('name' => 'a'), 'foo1.txt');
        $documentBlock2 = new \Holograph\DocumentBlock(array('name' => 'b', 'parent' => 'x'), 'foo1.txt');

        $this->_object->addDocumentBlock($documentBlock);
        $this->_object->addDocumentBlock($documentBlock2);

        $blocks = $this->_object->getDocBlocks();

        $blockParent = array_pop($blocks);

        $this->assertEquals('x', $blockParent->name);
        $this->assertEquals(array('b' => $documentBlock2), $blockParent->children);
    }

    public function testBuildPages()
    {
        $block = new \Holograph\DocumentBlock(array('name' => 'a'), 'foo1.txt');

        $blocks = array($block);

        $pages = $this->_object->buildPages($blocks);

        $expected = array(
            'index.html' => "\nfoo1.txt",
        );
        $this->assertEquals($expected, $pages);
    }

    public function testBuildPagesMultiple()
    {
        $blockA = new \Holograph\DocumentBlock(array('name' => 'a'), 'foo1');
        $blockB = new \Holograph\DocumentBlock(array('name' => 'b'), 'foo2');

        $blocks = array($blockA, $blockB);

        $pages = $this->_object->buildPages($blocks);

        $expected = array(
            'index.html' => "\nfoo1\nfoo2",
        );
        $this->assertEquals($expected, $pages);
    }

    public function testBuildPagesOutputFilePreset()
    {
        $blockA = new \Holograph\DocumentBlock(array('name' => 'a', 'outputFile' => 'example.html'), 'foo1');
        $blockB = new \Holograph\DocumentBlock(array('name' => 'b', 'outputFile' => 'example.html'), 'foo2');

        $blocks = array($blockA, $blockB);

        $pages = $this->_object->buildPages($blocks);

        $expected = array(
            'example.html' => "\nfoo1\nfoo2",
        );
        $this->assertEquals($expected, $pages);
    }

    public function testBuildPagesOutputFilePresetNeedsFilter()
    {
        $blockA = new \Holograph\DocumentBlock(array('name' => 'a', 'outputFile' => 'My Example'), 'foo1');
        $blockB = new \Holograph\DocumentBlock(array('name' => 'b', 'outputFile' => 'My Example'), 'foo2');

        $blocks = array($blockA, $blockB);

        $pages = $this->_object->buildPages($blocks);

        $expected = array(
            'my_example.html' => "\nfoo1\nfoo2",
        );
        $this->assertEquals($expected, $pages);
    }

    public function testBuildPagesWithChildren()
    {
        $blockA = new \Holograph\DocumentBlock(array('name' => 'a', 'outputFile' => 'index'), 'foo1');
        $blockB = new \Holograph\DocumentBlock(array('name' => 'b', 'parent' => 'a'), 'im a child');
        $blockC = new \Holograph\DocumentBlock(array('name' => 'c', 'parent' => 'a'), 'im a child2');

        $blockChildren = array($blockB, $blockC);
        $blockA->children = $blockChildren;
        $blocks = array($blockA);

        $pages = $this->_object->buildPages($blocks);

        $expected = array(
            'index.html' => "\nfoo1\nim a child\nim a child2",
        );
        $this->assertEquals($expected, $pages);
    }
}
