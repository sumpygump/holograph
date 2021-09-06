<?php

namespace Holograph;

use Parsedown;

/**
 * ParsedownRenderer
 *
 * Adds specific conversion process for markdown for holograph. This will
 * accommodate the markup correctly for a fenced code block with or without an
 * optional language directive
 *
 * @uses Parsedown
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 */
class ParsedownRenderer extends Parsedown
{
    /**
     * blockFencedCode
     *
     * Method that gets called when the first line of the fenced code block is
     * parsed
     *
     * @param string $Line The line being parsed
     * @return array
     */
    protected function blockFencedCode($Line)
    {
        $char = $Line['text'][0];

        // Supports code block fence like so
        // ```html,21
        // The comma and then a number will set the line numbers for the pretty
        // print display
        if (preg_match('/^[' . $char . ']{3,}[ ]*([\w-]+)?(?:,[ ]?(\d+))?[ ]*$/', $Line['text'], $matches)) {
            $language = isset($matches[1]) ? $matches[1] : '';
            $lineNumber = isset($matches[2]) ? $matches[2] : '';

            if (strpos($language, 'example')) {
                // e.g. ```html_example
                $isExample = true;
            } else {
                // e.g. ```html
                $isExample = false;
            }

            return $this->createRegularBlockFencedCode($Line, $isExample, $language, $lineNumber);
        }
    }

    /**
     * createRegularBlockFencedCode
     *
     * Creates the element level data for the fenced code block
     *
     * @param string $line The line being parsed
     * @param string $language Language of the fenced code block
     * @param string $lineNumber Optional line number to display
     * @return array
     */
    protected function createRegularBlockFencedCode($line, $isExample, $language = '', $lineNumber = '')
    {
        $char = $line['text'][0];
        $classes = ['prettyprint'];

        if ($language) {
            $classes[] = 'language-' . $language;
        }

        if ($lineNumber) {
            $classes[] = 'linenums:' . $lineNumber;
        }

        $element = [
            'name' => 'pre',
            'text' => '',
            'attributes' => [
                'class' => implode(' ', $classes),
            ],
        ];

        $block = [
            'char' => $char,
            'isExample' => $isExample,
            'element' => [
                'name' => 'div',
                'handler' => 'element',
                'text' => $element,
                'attributes' => [
                    'class' => 'codeBlock',
                ],
            ],
        ];

        return $block;
    }

    /**
     * blockFencedCodeComplete
     *
     * Gets called when the fenced code block is completed during parsing
     *
     * @param array $Block Definition of data for this block
     * @return array
     */
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['text']['text'];

        if ($Block['isExample'] == false) {
            return $Block;
        }

        $exampleOutputDiv = [
            'name' => 'div',
            'rawHtml' => $text,
            'attributes' => [
                'class' => 'exampleOutput',
            ],
        ];

        $wrapDiv = [
            'name' => 'div',
            'attributes' => [
                'class' => 'codeExample',
            ],
            'handler' => 'elements',
            'text' => [$exampleOutputDiv, $Block['element']],
        ];

        $Block['element'] = $wrapDiv;
        return $Block;
    }
}
