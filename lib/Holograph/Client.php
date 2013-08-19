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
 * The client facilitates interactions from the user via a command line 
 * interface to execute functions for the holograph system.
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
        'verbose|v'  => 'Verbose output',
    );

    const NOTIFY_WARNING = 0;
    const NOTIFY_MESSAGE = 1;
    const NOTIFY_VERBOSE = 2;

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
     * Verbosity level
     *
     * @var int
     */
    protected static $_verbose = false;

    /**
     * Default configuration filename
     *
     * @var string
     */
    protected $_configFilename = 'holograph.yml';

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
        if ($this->_args->verbose) {
            self::$_verbose = true;
        }

        if ($this->_args->help || $this->_args->h || $this->_args->action == 'help') {
            $this->displayHelp();
            return 0;
        }

        if ($this->_args->version || $this->_args->action == 'version') {
            print Version::renderVersion();
            return 0;
        }

        $this->notify("Current path: " . getcwd(), self::NOTIFY_VERBOSE);

        $configFileOverride = false;
        if ($this->_args->conf) {
            $this->_configFilename = $this->_args->conf;
            // If a specified file fails, we want to bail since there are 
            // destructive commands run (rm -rf) on paths that the user may not 
            // have intended with a custom config file
            $configFileOverride = true;
        }

        if ($this->_args->action == 'init') {
            $this->notify("Initializing environment for Holograph");
            $this->writeConfig();
            return 0;
        }

        $config = $this->readConfigFile($this->_configFilename, $configFileOverride);
        $builder = new Builder($config, $this);

        try {
            $builder->execute();
        } catch (\Exception $exception) {
            $this->_halt($exception->getMessage());
        }

        return $this->_status;
    }

    /**
     * Write a config file to disk
     *
     * @return void
     */
    public function writeConfig()
    {
        if (file_exists($this->_configFilename)) {
            $this->_halt(sprintf("Config file already exists: '%s'", $this->_configFilename));
        }

        $defaultBuilder = new Builder(array(), $this);

        $this->notify(sprintf("Writing default configuration to config file '%s'", $this->_configFilename));
        file_put_contents($this->_configFilename, Yaml::dump($defaultBuilder->getConfig()));
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
        print "  init : Initialize environment for holograph (write conf file with defaults)\n";
        print "  build : Build the style guide HTML/CSS\n";
        print "  help : Display program help and exit\n";
        print "\nOptions:\n";
        print "  -c <file> | --conf <file> : Use alternate configuration file\n";
        print "  -h | --help : Display program help and exit\n";
        print "  -q | --quiet : Quiet mode (Don't output anything)\n";
        print "  -v | --verbose : Verbose output mode\n";
        print "  --version : Display program version and exit\n";
    }

    /**
     * Read config file
     * 
     * @param string $configFile Path to configuration file to load
     * @param bool $throwException Whether to throw an exception if file is missing
     * @return array
     */
    public function readConfigFile($configFile, $throwException = false)
    {
        if (!file_exists($configFile)) {
            if ($throwException) {
                throw new \Exception(
                    sprintf("Config file '%s' not found. (Path: %s)", $configFile, getcwd())
                );
            }

            $this->notify(
                sprintf(
                    "Config file '%s' not found. Using default configuration. (Path: %s)",
                    $configFile, getcwd()
                ),
                self::NOTIFY_WARNING
            );
            return array();
        }

        $this->notify(sprintf("Using config file '%s'", $configFile), self::NOTIFY_VERBOSE);

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
    public function notify($message, $level = self::NOTIFY_MESSAGE)
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
