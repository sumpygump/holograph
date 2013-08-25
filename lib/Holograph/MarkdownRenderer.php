<?php
/**
 * Markdown renderer for Holograph
 *
 * @package Holograph
 */

namespace Holograph;

/**
 * MarkdownRenderer
 *
 * This is a specific Markdown parser for Holograph, which converts fenced code 
 * blocks containing the word 'example' into markup that showcases the snippet 
 * itself as well as the code block.
 *
 * Example:
 *
 * The following markdown:
 * ```html_example
 * <h1>Hello World</h1>
 * ```
 *
 * Will render the following HTML:
 * <div class="codeExample">
 *     <div class="exampleOutput">
 *         <h1>Hello World</h1>
 *     </div>
 *     <div class="codeBlock>
 *         <pre class="language-html_example">
 *             &lt;h1&gt;Hello World&lt;/h1&gt;
 *         </pre>
 *     </div>
 * </div>
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

        $codeblock = preg_replace_callback(
            '/^\n+/',
            array(&$this, '_doFencedCodeBlocks_newlines'),
            $codeblock
        );

        if (strpos($matches[2], 'example')) {
            $cb = '<div class="codeExample">'
                . '<div class="exampleOutput">' . $matches[4] . '</div>';

            $cb .= '<div class="codeBlock">';
            $cb .= empty($matches[3])
                ? "<pre" : "<pre class=\"linenums:$matches[3]\"";

            $cb .= empty($matches[2])
                ? ">" : " class=\"lang-$matches[2] prettyprint\">";

            $cb .= $codeblock . "</pre>";
            $cb .= "</div></div>";
        } else {
            $cb = empty($matches[3])
                ? "<pre" : "<pre class=\"linenums:$matches[3]\"";

            $cb .= empty($matches[2])
                ? ">" : " class=\"lang-$matches[2] prettyprint\">";

            $cb .= "$codeblock</pre>";
        }

        return "\n\n".$this->hashBlock($cb)."\n\n";
    }
}
