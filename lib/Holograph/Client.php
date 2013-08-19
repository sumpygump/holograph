<?php
/**
 * Holograph CLI client
 *
 * @package Holograph
 */

namespace Holograph;

use Symfony\Component\Yaml\Yaml;

/**
 * Client
 *
 * @uses Qi_Console_Client
 * @package Holograph
 * @author Jansen Price <jansen.price@gmail.com>
 * @version $Id$
 */
class Client extends \Qi_Console_Client
{
    /**
     * Argument rules (for ArgV)
     *
     * @var array
     */
    public static $argRules = array(
        'arg:action' => 'Action',
        'help|h'     => 'Help',
        'quiet|q'    => 'Quiet mode',
        'conf|c:'    => 'Config file',
        'version'    => 'Display version',
    );

    /**
     * Exit status code
     * 
     * @var float
     */
    protected $_status = 0;

    /**
     * Be quiet
     *
     * @var mixed
     */
    protected $_quiet = false;

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        $this->setQuiet($this->_args->quiet);
    }

    /**
     * Set quiet level
     * 
     * @param bool $value Value
     * @return void
     */
    public function setQuiet($value)
    {
        $this->_quiet = (bool) $value;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_args->help || $this->_args->h || $this->_args->action == 'help') {
            $this->displayHelp();
            return 0;
        }

        if ($this->_args->version || $this->_args->action == 'version') {
            print Version::renderVersion();
            return 0;
        }

        $config = $this->readConfigFile('.holograph.yaml');
        $builder = new Builder($config, $this);

        try {
            $builder->execute();
        } catch (Exception $exception) {
            $this->_halt($exception->getMessage());
        }

        return $this->_status;
    }

    /**
     * Display help message
     *
     * @return void
     */
    public function displayHelp()
    {
        print Version::renderVersion();

        print "A markdown based documentation system for OOCSS\n\n";
        print "Usage: holograph <action> [OPTIONS]\n";
        print "\nActions:\n";
        print "  build : Build the style guide HTML/CSS\n";
        print "  help : Display program help and exit\n";
        print "\nOptions:\n";
        print "  -q | --quiet : Quiet mode (Don't output anything)\n";
        print "  -h | --help : Display program help and exit\n";
        print "  --version : Display program version and exit\n";
        print "  -f <file> | --file <file> : Use alternate configuration file\n";
    }

    /**
     * Read config file
     * 
     * @param string $configFile
     * @return void
     */
    public function readConfigFile($configFile)
    {
        $config = Yaml::parse($configFile);

        if ($config == $configFile) {
            return array();
        }

        return $config;
    }

    /**
     * Notify (only display if verbose)
     *
     * @param string $message Message
     * @param int $level Message level
     *      0 = warning message
     *      1 = regular
     *      2 = verbose
     *      3 = action
     * @return void
     */
    public function notify($message, $level = 1)
    {
        switch ($level) {
        case 0:
            $this->_displayWarning($message);
            $this->_status = 2;
            break;
        case 1:
            if (!$this->_quiet) {
                $this->_displayMessage($message);
            }
            break;
        case 2:
            if (self::$_verbose && !$this->_quiet) {
                $this->_displayMessage(">> " . $message, true, 3);
            }
            break;
        default:
            if ($this->_quiet) {
                return;
            }

            if (substr($message, -1) != "\n") {
                $message .= "\n";
            }

            echo $message;

            break;
        }
    }
}
