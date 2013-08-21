<?php

namespace Holograph\Preprocessor\Css;

use Holograph\Preprocessor\PreprocessorAbstract;
use Holograph\FileOps;

//include_once 'lib/Minify/CSS/Compressor.php';

/**
 * Class to minify and combine Css files
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Minify extends PreprocessorAbstract
{
    /**
     * Execute preprocessor
     *
     * This will take all CSS files from the source dir, minify each one and 
     * then combine them into one file.
     *
     * @param array $options Options for execution
     * @return void
     */
    public function execute($options = array())
    {
        $cssFiles = FileOps::rglob("*.css", 0, $this->getSourceDir());

        if (empty($cssFiles)) {
            return false;
        }

        FileOps::ensurePathExists($this->getDestinationDir());

        $mainStylesheet = $options['main_stylesheet'];

        $buffer = array();
        foreach ($cssFiles as $file) {
            $content = file_get_contents($file);
            $newContent = \Minify_CSS_Compressor::process($content);

            $newfile = str_replace(
                $this->getSourceDir(),
                $this->getDestinationDir(),
                $file
            );

            $buffer[] = $newContent;
        }

        if ($buffer) {
            file_put_contents($mainStylesheet, implode("\n", $buffer));
        }
    }
}
