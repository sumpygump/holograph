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
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class DocumentBlock
{
    public $name = '';
    public $title = '';
    public $category = '';
    public $markdown = '';
    public $outputFile = '';
    public $parent = '';
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
            throw new \Exception("Required parameter 'name' not found in comment block.");
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

        $this->markdown = $markdown;
    }
}
