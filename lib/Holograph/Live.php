<?php
/**
 * Live class file
 *
 * @package Holograph
 */

namespace Holograph;

use Symfony\Component\Yaml\Yaml;

/**
 * Live
 *
 * Complete a build process and return the contents for a given URI of the
 * styleguide.
 *
 * Usage:
 * Create a file index.php in the destination dir with the following PHP code:
 * <pre>
 *     chdir('..');
 *     require_once '/path/to/holograph/vendor/autoload.php';
 *     $contents = \Holograph\Live::reload($_SERVER['REQUEST_URI']);
 *     print $contents;
 * </pre>
 *
 * @package
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Live
{
    /**
     * Reload the page.
     *
     * This is a convenient way to simply reload the page in your browser
     * without having to run 'holograph build' because this class does that for
     * you.
     *
     * E.g. in a browser, go to
     * http://localhost:3232/ and it will serve up the index.html file after
     * regenerating the styleguide build.
     *
     * Current downside is you can't provide an alternate config filename; it
     * always assumes holograph.yml
     *
     * @param mixed $requestUri
     * @return void
     */
    public static function reload($requestUri)
    {
        $configFile = 'holograph.yml';

        $config = Yaml::parseFile($configFile);

        $logger = new Logger\Stdout();
        $builder = new Builder($config, $logger);

        $builder->execute();
        $destination = $builder->getConfig('destination');

        $fileio = new FileOps();

        $uri = self::transformRequestUri($requestUri);
        if ($uri == '') {
            $uri = "index.html";
        }
        $contents = $fileio->readFile($destination . '/' .  $uri);

        return $contents;
    }

    /**
     * transformRequestUri
     *
     * Maybe this isn't needed anymore?
     *
     * @param string $requestUri
     * @return string
     */
    public static function transformRequestUri($requestUri)
    {
        // URIs should look something like '/docs/index.php/filename.html'
        $request = str_replace('index.php/', '', $_SERVER['REQUEST_URI']);

        // Also strip off leading slash
        $request = ltrim($request, '/');

        if (basename($request) == 'index.php') {
            $request = str_replace('index.php', 'index.html', $request);
        }

        return basename($request);
    }
}
