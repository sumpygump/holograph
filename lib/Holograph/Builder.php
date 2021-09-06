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
     * Logger object
     *
     * @var Holograph\Logger\LoggerInterface
     */
    public $logger;

    /**
     * File io object
     *
     * @var Holograph\FileOps
     */
    public $fileio;

    /**
     * Configuration
     *
     * @var array
     */
    protected $_config = array(
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

    // @codingStandardsIgnoreStart
    /**
     * Config option annotations
     *
     * @var array
     */
    protected $_configAnnotations = array(
        'title'                => "The title for this styleguide {{title}}",
        'source'               => "The directory containing the source files to parse",
        'destination'          => "The directory to generate files to",
        'documentation_assets' => "Directory location of assets needed to accompany the docs (layout.html)",
        'compat_mode'          => "Boolean indicating whether to use hologram compatibility
When true it will expect header.html and footer.html instead of layout.html",
        'dependencies'         => "Any other asset folders that need to be copied to the destination folder",
        'preprocessor'         => "Build option to actually compiles CSS files (options: none, minify)",
        'build'                => "Directory to build the final CSS files",
        'main_stylesheet'      => "The main stylesheet to be included {{main_stylesheet}}",
        'port'                 => "Http port to use when running with `holograph serve`",
    );
    // @codingStandardsIgnoreEnd

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
     * Flag for whether default layout is being used
     *
     * @var bool
     */
    protected $_usingDefaultLayout = false;

    /**
     * Constructor
     *
     * @param array $config Configuration array
     * @param Holograph\Logger\LoggerInterface $logger Logger
     * @return void
     */
    public function __construct($config, $logger)
    {
        $this->setLogger($logger);

        $this->fileio = $this->createFileIo();

        foreach ($config as $param => $value) {
            $this->_config[$param] = $value;
        }

        $this->logger->info(
            "Using configuration:\n" . Yaml::dump($this->_config)
        );
    }

    /**
     * Get doc blocks
     *
     * @return void
     */
    public function getDocBlocks()
    {
        return $this->_docBlocks;
    }

    /**
     * Create new fileio object
     *
     * @return FileOps
     */
    public function createFileIo()
    {
        return new FileOps();
    }

    /**
     * Set logging object
     *
     * @param Logger\LoggerInterface $logger Logger object
     * @return void
     */
    public function setLogger(Logger\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig($value = null)
    {
        if (null == $value) {
            return $this->_config;
        }

        if (isset($this->_config[$value])) {
            return $this->_config[$value];
        }

        return '';
    }

    /**
     * Generate config file contents annotated
     *
     * @return string
     */
    public function getConfigAnnotated()
    {
        $configContent = "# Holograph configuration\n";

        foreach ($this->_config as $name => $value) {
            if (isset($this->_configAnnotations[$name])) {
                $annotation = $this->_configAnnotations[$name];

                $configContent .= "\n# "
                    . str_replace("\n", "\n# ", $annotation) . "\n";
            }

            $configEntry = array($name => $value);

            $configContent .= Yaml::dump($configEntry);
        }

        return $configContent;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $cssFiles = $this->getSourceFilelist('*.css');

        if (empty($cssFiles)) {
            $this->logger->warning("No files found to process.");
            return 1;
        }

        $this->parseSourceFiles($cssFiles);

        $mdFiles = $this->getSourceFilelist('*.md');
        $this->parseSourceFiles($mdFiles);

        $this->runPreprocessor($cssFiles);

        $this->buildPages($this->_docBlocks);

        $this->writeOutputFiles();

        $this->logger->notice("Done.");

        return 0;
    }

    /**
     * Get a list of files from source directory
     *
     * @param string $pattern File pattern to find
     * @return array
     */
    public function getSourceFilelist($pattern = "*.css")
    {
        $sourceDir = $this->_config['source'];

        $this->logger->notice(
            "Reading source dir '$sourceDir' for '$pattern'..."
        );

        $files = FileOps::rglob($pattern, 0, $sourceDir);

        sort($files);

        $this->logger->info(
            sprintf("Found %s files in source dir", count($files))
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

            switch ($extension) {
                case 'md':
                    $this->parseSourceDocFile($file);
                    break;
                case 'css':
                    $this->parseSourceStylesheetFile($file);
                    break;
            }
        }
    }

    /**
     * Parse a documentation source file (markdown)
     *
     * @param string $file Filename
     * @return void
     */
    public function parseSourceDocFile($file)
    {
        $this->logger->info(sprintf("Reading file '%s'", $file));

        $filename = pathinfo($file, PATHINFO_FILENAME) . ".html";

        $contents = $this->fileio->readFile($file);

        $pagename = ucfirst(str_replace('.md', '', basename($file)));

        $this->_navigationItems[$filename] = $pagename;

        $this->addToPage($filename, $contents);
    }

    /**
     * Parse source file and create document blocks
     *
     * @param string $file Filename
     * @return void
     */
    public function parseSourceStylesheetFile($file)
    {
        $this->logger->info("Reading file '$file'");
        $contents = $this->fileio->readFile($file);

        // Find all the desired commment blocks (/*doc ... */)
        $commentBlockCount = preg_match_all(
            "#^\s*/\*doc(.*?)\*/#ms",
            $contents,
            $matches
        );

        if (!$commentBlockCount) {
            return;
        }

        $addedBlocks = 0;
        foreach ($matches[1] as $commentBlock) {
            $block = $this->createDocumentBlock($commentBlock, $file);
            if (!$block) {
                continue;
            }
            $this->addDocumentBlock($block);
            $addedBlocks++;
        }

        return $addedBlocks;
    }

    /**
     * Compress the source files as part of the build step
     *
     * @param array $files Array of source files
     * @return void
     */
    public function runPreprocessor($files)
    {
        if ($this->_config['preprocessor'] == 'none') {
            return;
        }

        $this->logger->notice(
            sprintf(
                "Running preprocessor '%s'...",
                $this->_config['preprocessor']
            )
        );

        $preprocessor = new \Holograph\Preprocessor\Css\Minify();

        $preprocessor->setSourceDir($this->_config['source'])
            ->setDestinationDir($this->_config['build']);

        $preprocessor->execute(
            array('main_stylesheet' => $this->_config['main_stylesheet'])
        );
    }

    /**
     * Create a document block from a comment block
     *
     * If there is no metadata portion, ignore this block.
     *
     * The meta data is yaml data at the top of the comment, formatted thusly:
     * ---
     * name: nameOfBlock
     * title: My Title
     * category: The Category
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
        $yml = $matches[1];

        $markdown = substr($commentBlock, $pos);

        $settings = Yaml::parse($yml);

        if (!is_array($settings)) {
            // Invalid yml returns a string, we'll just make that be the name.
            $this->logger->warning(
                sprintf("Invalid yaml found in file %s: %s", $filename, $yml)
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
            // Using specific class with html tag to not conflict with site's h1
            $documentBlock->markdown = "\n\n<h1 class=\"hg-hdg\">" . $documentBlock->title . "</h1>\n"
                . $documentBlock->markdown;

            if (isset($this->_docBlocks[$documentBlock->name])) {
                $this->logger->warning(
                    sprintf(
                        "Warning: Overwriting block with name '%s'",
                        $documentBlock->name
                    )
                );
            }

            $this->_docBlocks[$documentBlock->name] = $documentBlock;
        } else {
            // child block
            if (isset($this->_docBlocks[$documentBlock->parent])) {
                // Prepend the title as markdown sub-heading
                $documentBlock->markdown = "\n\n<h2 class=\"hg-hdg\"> "
                    . $documentBlock->title . "</h2>\n"
                    . $documentBlock->markdown;

                $parentBlock = $this->_docBlocks[$documentBlock->parent];

                $parentBlock->children[$documentBlock->name] = $documentBlock;
            } else {
                $parentSettings = array(
                    'name' => $documentBlock->parent,
                );
                $parentBlock = new DocumentBlock($parentSettings, $documentBlock->parent);
                $parentBlock->children[$documentBlock->name] = $documentBlock;

                $this->_docBlocks[$documentBlock->parent] = $parentBlock;
            }
        }
    }

    /**
     * Build the pages array
     *
     * @param array $docBlocks Array of document blocks
     * @param string $outputFile Output filename
     * @return array
     */
    public function buildPages($docBlocks, $outputFile = '')
    {
        $this->logger->notice("Building pages ...");

        foreach ($docBlocks as $documentBlock) {
            $this->logger->notice(
                sprintf(" * Building block %s : %s", $documentBlock->category, $documentBlock->title)
            );
            if ($documentBlock->outputFile) {
                $pageName = $documentBlock->outputFile;
                $outputFile = strtolower(trim($pageName));

                if (strpos($outputFile, '.html') === false) {
                    $outputFile .= ".html";
                }

                $outputFile = str_replace(' ', '_', $outputFile);

                $this->_navigationItems[$outputFile] = $pageName;
            }

            if ($outputFile == '') {
                $outputFile = "index.html";
            }

            $this->addToPage($outputFile, $documentBlock->markdown);

            if ($documentBlock->children) {
                $this->buildPages($documentBlock->children, $outputFile);
            }
        }

        return $this->_pages;
    }

    /**
     * Add contents to a page
     *
     * @param string $outputFile Page output name
     * @param string $content Content
     * @return void
     */
    public function addToPage($outputFile, $content)
    {
        if (!isset($this->_pages[$outputFile])) {
            $this->_pages[$outputFile] = '';
        }

        $this->_pages[$outputFile] .= "\n" . $content;

        return $this->_pages;
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

        $this->logger->notice(
            sprintf("Writing to dest dir '%s'...", $destination)
        );

        //$markdownParser = new MarkdownRenderer();
        $markdownParser = new ParsedownRenderer();

        $documentationAssets = $this->_config['documentation_assets'];

        // Compat mode uses header and footer files. Compatible with hologram.
        if ($this->_config['compat_mode']) {
            $header = $this->getHeader();
            $footer = $this->getFooter();
        } else {
            $layout = $this->getLayout();
        }

        foreach ($this->_pages as $filename => $content) {
            $filename = $destination . DIRECTORY_SEPARATOR . $filename;
            $this->logger->info(sprintf("Writing file '%s'", $filename));
            $htmlContent = $markdownParser->text($content);

            if ($this->_config['compat_mode']) {
                $contents = $header . $htmlContent . $footer;
                $this->fileio->writeFile($filename, $contents);
            } else {
                $contents = str_replace("{{content}}", $htmlContent, $layout);
                $this->fileio->writeFile($filename, $contents);
            }
        }

        // Copy templates/* and dependencies to destination dir
        $this->logger->notice(
            sprintf("Copying assets to dest dir '%s'...", $destination)
        );

        $assetDirs = glob(
            $this->_config['documentation_assets'] . DIRECTORY_SEPARATOR . '*',
            GLOB_ONLYDIR
        );

        $assets = array_merge($this->_config['dependencies'], $assetDirs);

        // When there are no custom template files to use, let's include
        // holograph's default template dir.
        if (count($assets) == 1
            && (!file_exists($assets[0])
            || $assets[0] != $this->_config['documentation_assets'])
            || $this->_usingDefaultLayout == true
        ) {
            $this->logger->warning(
                sprintf(
                    "Note: No additional assets found in '%s', "
                    . "copying default Holograph assets.",
                    $this->_config['documentation_assets']
                )
            );

            $assets[] = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'default-templates' . DIRECTORY_SEPARATOR . 'static';
        }

        foreach ($assets as $path) {
            if (file_exists($path) && is_dir($path)) {
                $basename = pathinfo($path, PATHINFO_BASENAME);

                $cmd = sprintf(
                    "rm -rf %s",
                    escapeshellarg(
                        $destination . DIRECTORY_SEPARATOR . $basename
                    )
                );
                $this->logger->info($cmd);
                passthru($cmd);

                $cmd = sprintf(
                    "cp -r %s %s",
                    escapeshellarg($path),
                    escapeshellarg(
                        $destination . DIRECTORY_SEPARATOR . $basename
                    )
                );
                $this->logger->info($cmd);
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
        $layoutFilename = $this->_config['documentation_assets']
            . DIRECTORY_SEPARATOR . 'layout.html';

        if (!file_exists($layoutFilename)) {
            $this->logger->warning(
                sprintf(
                    "Note: Layout file not found in '%s', "
                    . "using default Holograph layout instead.",
                    $layoutFilename
                )
            );

            $layoutFilename = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR
                . 'default-templates' . DIRECTORY_SEPARATOR . 'layout.html';

            $this->_usingDefaultLayout = true;
        }

        $layout = $this->fileio->readFile($layoutFilename);

        $layout = str_replace("{{title}}", $this->_config['title'], $layout);
        $layout = str_replace(
            "{{main_stylesheet}}",
            $this->_config['main_stylesheet'],
            $layout
        );

        $navigation = "";
        foreach ($this->_navigationItems as $filename => $pageName) {
            $navigation .= sprintf(
                '<li><a href="%s">%s</a></li>',
                $filename,
                $pageName
            ) . "\n";
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
        $headerFilename = $this->_config['documentation_assets']
            . DIRECTORY_SEPARATOR . 'header.html';

        if (!$this->fileio->fileExists($headerFilename)) {
            $this->logger->warning(
                sprintf("Header file '%s' not found.", $headerFilename)
            );
            return '<html><head></head><body>';
        }

        return $this->fileio->readFile($headerFilename);
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
        $footerFilename = $this->_config['documentation_assets']
            . DIRECTORY_SEPARATOR . 'footer.html';

        if (!file_exists($footerFilename)) {
            $this->logger->warning(
                sprintf("Footer file '%s' not found.", $footerFilename)
            );
            return '</body></html>';
        }

        return $this->fileio->readFile($footerFilename);
    }
}
