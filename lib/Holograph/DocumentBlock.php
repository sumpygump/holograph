<?php
/**
 * Document block class file
 *
 * @package Holograph
 */

namespace Holograph;

/**
 * DocumentBlock
 *
 * A document block represents a comment block from a CSS file that should be
 * included in the style guide. A document block will contain some YML front
 * matter with settings and some markdown documentation.
 *
 * By convention, any code fenced blocks containing the word 'example' (e.g.
 * ```html_example ...  ```) will be treated in such a way to include the
 * example itself followed by the code block. See Holograph\MarkdownRenderer.
 *
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class DocumentBlock
{
    /**
     * Name of this block
     *
     * @var string
     */
    public $name = '';

    /**
     * Title for this block
     *
     * @var string
     */
    public $title = '';

    /**
     * Category for this block
     *
     * @var string
     */
    public $category = 'Index';

    /**
     * The markdown documentation content for this block
     *
     * @var string
     */
    public $markdown = '';

    /**
     * Output filename for this block
     *
     * @var string
     */
    public $outputFile = '';

    /**
     * The parent block name (for a child block)
     *
     * @var string
     */
    public $parent = '';

    /**
     * Child Document Blocks
     *
     * @var array
     */
    public $children = array();

    /**
     * Constructor
     *
     * @param array $settings Settings
     * @param string $markdown Markdown content
     * @return void
     */
    public function __construct($settings, $markdown)
    {
        if (!isset($settings['name'])) {
            throw new \Exception(
                "Required parameter 'name' not found in comment block."
            );
        }

        if ($settings['name']) {
            $this->name = $settings['name'];
        }

        if (isset($settings['title'])) {
            $this->title = $settings['title'];
        }

        if (isset($settings['category'])) {
            $this->category = $settings['category'];
        }

        if (isset($settings['parent'])) {
            $this->parent = $settings['parent'];
        }

        if (isset($settings['outputFile'])) {
            $this->outputFile = $settings['outputFile'];
        }

        // If no title was provided, use the name as the title
        if (!$this->title) {
            $this->title = ucfirst($this->name);
        }
        $this->markdown = $markdown;
    }
}
