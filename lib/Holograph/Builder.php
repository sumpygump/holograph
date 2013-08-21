<?php
/**
 * Holograph builder file
 *
 * @package Holograph
 */

namespace Holograph;

use Symfony\Component\Yaml\Yaml;

/**
 * Builder
 *
 * The builder class contains the logic to read the source files, generate a 
 * mapping of output files and writing the output files to the destiantion 
 * directory.
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Builder
{
    /**
     * Client object
     *
     * @var Holograph\Client
     */
    protected $_client;

    /**
     * Configuration
     *
     * @var array
     */
    protected $_config = array(
        'title'               => "Style Guide",
        'source'              => "./components",
        'destination'         => "./docs",
        'documentationAssets' => "./templates",
        'dependencies'        => array("./build"),
        'compatMode'          => false,
    );

    /**
     * Doc blocks
     *
     * @var array
     */
    protected $_docBlocks = array();

    /**
     * Pages
     *
     * @var array
     */
    protected $_pages = array();

    /**
     * Storage of navigation items
     *
     * @var array
     */
    protected $_navigationItems = array();

    /**
     * Constructor
     *
     * @param mixed $config
     * @return void
     */
    public function __construct($config, $client = null)
    {
        $this->setClient($client);

        foreach ($config as $param => $value) {
            $this->_config[$param] = $value;
        }

        $this->notify(
            "Using configuration:\n" . Yaml::dump($this->_config),
            Client::NOTIFY_VERBOSE
        );
    }

    /**
     * Set client object
     *
     * @param Client $client
     * @return void
     */
    public function setClient(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $files = $this->getSourceFilelist();
        $this->parseSourceFiles($files);

        $this->buildPages($this->_docBlocks);

        $this->writeOutputFiles();
    }

    /**
     * Get a list of files from source directory
     *
     * @return array
     */
    public function getSourceFilelist()
    {
        $sourceDir = $this->_config['source'];

        $this->notify("Reading source dir '$sourceDir'...");

        $cssFiles = self::rglob("*.css", 0, $sourceDir);
        $mdFiles = self::rglob("*.md", 0, $sourceDir);

        $files = array_merge($cssFiles, $mdFiles);

        sort($files);

        $this->notify(
            sprintf("Found %s files in source dir", count($files)),
            Client::NOTIFY_VERBOSE
        );

        return $files;
    }

    /**
     * Parse source files and build pages and document blocks
     *
     * @param array $files List of files to read
     * @return void
     */
    public function parseSourceFiles($files)
    {
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension == 'md') {
                $this->notify(sprintf("Reading file '%s'", $file), Client::NOTIFY_VERBOSE);
                $filename = pathinfo($file, PATHINFO_FILENAME) . ".html";
                $this->_pages[$filename] = file_get_contents($file);
            } else {
                $this->parseSourceFile($file);
            }
        }
    }

    /**
     * Parse source file and create document blocks
     *
     * @param string $file Filename
     * @return void
     */
    public function parseSourceFile($file)
    {
        $this->notify("Reading file '$file'", Client::NOTIFY_VERBOSE);
        $contents = file_get_contents($file);

        // Find all the desired commment blocks (/*doc ... */)
        $commentBlockCount = preg_match_all(
            "#^\s*/\*doc(.*?)\*/#ms", $contents, $matches
        );
        if (!$commentBlockCount) {
            return;
        }

        foreach ($matches[1] as $commentBlock) {
            $block = $this->createDocumentBlock($commentBlock, $file);
            if (!$block) {
                continue;
            }
            $this->addDocumentBlock($block);
        }
    }

    /**
     * Create a document block from a comment block
     *
     * If there is no metadata portion, ignore this block.
     *
     * The meta data is yaml data at the top of the comment, formatted thusly:
     * ---
     * name: nameOfBlock
     * ---
     *
     * @param string $commentBlock Comment block from file
     * @param string $filename Filename being parsed
     * @return false | DocumentBlock
     */
    public function createDocumentBlock($commentBlock, $filename)
    {
        if (!preg_match("#\s*---\s(.*?)\s---$#ms", $commentBlock, $matches)) {
            return false;
        }

        $pos = strlen($matches[0]);
        $markdown = substr($commentBlock, $pos);

        $settings = Yaml::parse($matches[1]);

        if (!is_array($settings)) {
            // Invalid yml returns a string, we'll just make that be the name.
            $this->notify(
                sprintf("Invalid yaml found in file %s: %s", $filename, $matches[1]),
                Client::NOTIFY_WARNING
            );
            $settings = array("name" => $settings);
        }

        $block = new DocumentBlock($settings, $markdown);

        return $block;
    }

    /**
     * Add a document block to our collection of blocks
     *
     * @param DocumentBlock $documentBlock Document block object
     * @return void
     */
    public function addDocumentBlock($documentBlock)
    {
        if (!$documentBlock->parent) {
            // parent block
            $documentBlock->outputFile = $documentBlock->category;

            // Prepend the title as markdown heading
            $documentBlock->markdown = "\n\n# " . $documentBlock->title . "\n"
                . $documentBlock->markdown;

            if (isset($this->_docBlocks[$documentBlock->name])) {
                $this->notify(
                    sprintf("Warning: Overwriting block with name '%s'", $documentBlock->name),
                    Client::NOTIFY_WARNING
                );
            }

            $this->_docBlocks[$documentBlock->name] = $documentBlock;
        } else {
            // child block
            if (isset($this->_docBlocks[$documentBlock->parent])) {
                // Prepend the title as markdown sub-heading
                $documentBlock->markdown = "\n\n## " . $documentBlock->title . "\n"
                    . $documentBlock->markdown;

                $parentBlock = $this->_docBlocks[$documentBlock->parent];

                $parentBlock->children[$documentBlock->name] = $documentBlock;
            } else {
                $this->_docBlocks[$documentBlock->parent] = new DocumentBlock();
            }
        }
    }

    /**
     * Build the pages array
     *
     * @param mixed $docBlocks
     * @param string $outputFile
     * @return void
     */
    public function buildPages($docBlocks, $outputFile = '')
    {
        foreach ($docBlocks as $documentBlock) {
            if ($documentBlock->outputFile) {
                $pageName = $documentBlock->outputFile;
                $outputFile = strtolower(trim($documentBlock->outputFile . ".html"));
                $outputFile = str_replace(' ', '_', $outputFile);

                $this->_navigationItems[$outputFile] = $pageName;
            }

            if (!isset($this->_pages[$outputFile])) {
                $this->_pages[$outputFile] = '';
            }

            $this->_pages[$outputFile] .= "\n" . $documentBlock->markdown;

            if ($documentBlock->children) {
                $this->buildPages($documentBlock->children, $outputFile);
            }
        }
    }

    /**
     * Write documentation
     *
     * @return void
     */
    public function writeOutputFiles()
    {
        $destination = $this->_config['destination'];

        if (!file_exists($destination)) {
            mkdir($destination);
        }

        $this->notify(sprintf("Writing to dest dir '%s'...", $destination));

        $markdownParser = new MarkdownRenderer();
        $documentationAssets = $this->_config['documentationAssets'];

        // Compat mode uses header and footer files. Compatible with hologram.
        if ($this->_config['compatMode']) {
            $header = $this->getHeader();
            $footer = $this->getFooter();
        } else {
            $layout = $this->getLayout();
        }

        foreach ($this->_pages as $filename => $content) {
            $filename = $destination . DIRECTORY_SEPARATOR . $filename;
            $this->notify(
                sprintf("Writing file '%s'", $filename),
                Client::NOTIFY_VERBOSE
            );
            $htmlContent = $markdownParser->transform($content);

            if ($this->_config['compatMode']) {
                file_put_contents($filename, $header . $htmlContent . $footer);
            } else {
                file_put_contents($filename, str_replace("{{content}}", $htmlContent, $layout));
            }
        }

        // Copy templates/* and dependencies to destination dir
        $this->notify(sprintf("Copying assets to dest dir '%s'...", $destination));

        $assetDirs = glob(
            $this->_config['documentationAssets'] . DIRECTORY_SEPARATOR . '*',
            GLOB_ONLYDIR
        );

        $assets = array_merge($this->_config['dependencies'], $assetDirs);

	// When there are no custom template files to use, let's include
	// holograph's default template dir.
        if (count($assets) == 1 && !file_exists($assets[0])) {
            $assets[] = $layoutFilename = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'default-templates' . DIRECTORY_SEPARATOR . 'static';
        }

        foreach ($assets as $path) {
            if (file_exists($path) && is_dir($path)) {
                $basename = pathinfo($path, PATHINFO_BASENAME);

                $cmd = sprintf(
                    "rm -rf %s",
                    escapeshellarg($destination . DIRECTORY_SEPARATOR . $basename)
                );
                $this->notify($cmd, Client::NOTIFY_VERBOSE);
                passthru($cmd);

                $cmd = sprintf(
                    "cp -r %s %s",
                    escapeshellarg($path),
                    escapeshellarg($destination . DIRECTORY_SEPARATOR . $basename)
                );
                $this->notify($cmd, Client::NOTIFY_VERBOSE);
                passthru($cmd);
            }
        }
    }

    /**
     * Get layout
     *
     * @return string
     */
    public function getLayout()
    {
        $layoutFilename = $this->_config['documentationAssets'] . DIRECTORY_SEPARATOR . 'layout.html';
        if (!file_exists($layoutFilename)) {
            $layoutFilename = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'default-templates' . DIRECTORY_SEPARATOR . 'layout.html';
        }

        $layout = file_get_contents($layoutFilename);

        $layout = str_replace("{{title}}", $this->_config['title'], $layout);

        $navigation = "";
        foreach ($this->_navigationItems as $filename => $pageName) {
            $navigation .= sprintf('<li><a href="%s">%s</a></li>', $filename, $pageName) . "\n";
        }
        $layout = str_replace("{{navigation}}", $navigation, $layout);

        return $layout;
    }

    /**
     * Get header
     *
     * Only for hologram compatible mode
     *
     * @return string
     */
    public function getHeader()
    {
        $headerFilename = $this->_config['documentationAssets'] . DIRECTORY_SEPARATOR . 'header.html';
        if (!file_exists($headerFilename)) {
            $this->notify(sprintf("Header file '%s' not found.", $headerFilename), Client::NOTIFY_WARNING);
            return '<html><head></head><body>';
        }

        return file_get_contents($headerFilename);
    }

    /**
     * Get footer
     *
     * Only for hologram compatible mode
     *
     * @return string
     */
    public function getFooter()
    {
        $footerFilename = $this->_config['documentationAssets'] . DIRECTORY_SEPARATOR . 'footer.html';
        if (!file_exists($footerFilename)) {
            $this->notify(sprintf("Footer file '%s' not found.", $footerFilename), Client::NOTIFY_WARNING);
            return '</body></html>';
        }

        return file_get_contents($footerFilename);
    }

    /**
     * Notify
     *
     * @param mixed $message
     * @param int $level
     * @return void
     */
    public function notify($message, $level = Client::NOTIFY_MESSAGE)
    {
        if ($this->_client) {
            $this->_client->notify($message, $level);
        } else {
            printf("[Holograph] %s\n", trim($message));
        }
    }

    /**
     * Recursive Glob
     * 
     * @param string $pattern Pattern
     * @param int $flags Flags to pass to glob
     * @param string $path Path to glob in
     * @return void
     */
    public static function rglob($pattern, $flags = 0, $path = '')
    {
        if ($path == '\\' || $path == '/') {
            // We don't want to try to find all the paths from root
            // It takes too long
            return array();
        }

        if (!$path && ($dir = dirname($pattern)) != '.') {
            if ($dir == '\\' || $dir == '/') {
                // This means the pattern starts with root
                // This takes too long
                return array();
            }
            return self::rglob(
                basename($pattern),
                $flags, $dir . DIRECTORY_SEPARATOR
            );
        }

        $paths = glob($path . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $files = glob($path . $pattern, $flags);

        foreach ($paths as $p) {
            $files = array_merge(
                $files, self::rglob($pattern, $flags, $p . DIRECTORY_SEPARATOR)
            );
        }

        return $files;
    }
}
