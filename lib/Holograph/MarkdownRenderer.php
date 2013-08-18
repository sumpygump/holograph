<?php

namespace Holograph;

/**
 * MarkdownRenderer
 *
 * @uses MarkdownExtendedParser
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class MarkdownRenderer extends MarkdownExtendedParser
{
    /**
     * _doFencedCodeBlocks_callback
     *
     * @param array $matches Matches
     * @return string
     */
    public function _doFencedCodeBlocks_callback($matches)
    {
        $codeblock = $matches[4];
        $codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);
        $codeblock = preg_replace_callback('/^\n+/',
            array(&$this, '_doFencedCodeBlocks_newlines'), $codeblock);

        if (strpos($matches[2], 'example')) {
            $cb = '<div class="codeExample">'
                . '<div class="exampleOutput">' . $matches[4] . '</div>';
            $cb .= '<div class="codeBlock">';
            $cb .= empty($matches[3]) ? "<pre" : "<pre class=\"linenums:$matches[3]\"";
            $cb .= empty($matches[2]) ? ">" : " class=\"language-$matches[2]\">";
            $cb .= $codeblock . "</pre>";
            $cb .= "</div></div>";
        } else {
            $cb = empty($matches[3]) ? "<pre" : "<pre class=\"linenums:$matches[3]\"";
            $cb .= empty($matches[2]) ? ">" : " class=\"language-$matches[2]\">";
            $cb .= "$codeblock</pre>";
        }

        return "\n\n".$this->hashBlock($cb)."\n\n";
    }
}
