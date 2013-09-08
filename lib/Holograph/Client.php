<?php
/**
 * Holograph CLI client
 *
 * @package Holograph
 */

namespace Holograph;

use Symfony\Component\Yaml\Yaml;
use Holograph\Logger\Terminal as TerminalLogger;

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

    /**
     * Notify constants
     *
     * @var int
     */
    const NOTIFY_WARNING = 0;
    const NOTIFY_MESSAGE = 1;
    const NOTIFY_VERBOSE = 2;

    /**
     * Exit status constants
     *
     * @var int
     */
    const STATUS_SUCCESS = 0;
    const STATUS_ERROR   = 1;

    /**
     * @var Holograph\Logger\Terminal
     */
    public $logger;

    /**
     * Exit status code
     * 
     * @var float
     */
    protected $_status = self::STATUS_SUCCESS;

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
        $this->logger = new TerminalLogger($this->_terminal);
        $this->logger->setQuiet($this->_args->quiet);
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        if ($this->_args->verbose) {
            $this->logger->setVerbose(true);
        }

        $action = $this->_args->action ? $this->_args->action : "help";

        if ($this->_args->help || $this->_args->h || $action == 'help') {
            $this->displayHelp();
            return self::STATUS_SUCCESS;
        }

        if ($this->_args->version || $action == 'version') {
            print Version::renderVersion();
            return self::STATUS_SUCCESS;
        }

        $this->logger->info("Current path: " . getcwd());

        $configFileOverride = false;
        if ($this->_args->conf) {
            $this->_configFilename = $this->_args->conf;
            // If a specified file fails, we want to bail since there are 
            // destructive commands run (rm -rf) on paths that the user may not 
            // have intended with a custom config file
            $configFileOverride = true;
        }

        switch ($action) {
        case 'init':
            $this->logger->notice("Initializing environment for Holograph");
            $this->writeConfig();
            break;
        case 'config':
            $this->showConfig();
            break;
        case 'build':
            $config = $this->readConfigFile(
                $this->_configFilename, $configFileOverride
            );

            if ($this->_args->compat) {
                $config['compatMode'] = $this->_args->compat;
            }
            $builder = new Builder($config, $this->logger);

            try {
                $builder->execute();
            } catch (\Exception $exception) {
                $this->_halt($exception->getMessage());
            }

            break;
        case 'live':
            $config = $this->readConfigFile(
                $this->_configFilename, $configFileOverride
            );

            $autoloadPath = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
            if (!file_exists($autoloadPath . 'vendor/autoload.php')) {
                $autoloadPath = dirname(dirname(dirname(dirname($autoloadPath))));
            }

            $indexContents = "<?php\n";
            $indexContents .= sprintf("/* Holograph Live\nGenerated %s\n*/\n", date("Y-m-d H:i:s"));
            $indexContents .= sprintf("require_once '%s/vendor/autoload.php';\n", $autoloadPath);
            $indexContents .= "\$contents = \\Holograph\\Live::reload(\$_SERVER['REQUEST_URI']);\n";
            $indexContents .= "print \$contents;\n";

            $fileio = new FileOps();
            $fileio->writeFile($config['destination'] . DIRECTORY_SEPARATOR . "index.php", $indexContents);

            $serverCmd = 'php -S localhost:8000 -t ' . $config['destination'];
            $this->logger->info($serverCmd);
            passthru($serverCmd);
            break;
        default:
            $this->logger->warning("Unrecognized action '$action'");
            $this->_status = self::STATUS_ERROR;
            break;
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
            $this->_halt(
                sprintf(
                    "Config file already exists: '%s'",
                    $this->_configFilename
                )
            );
        }

        $defaultBuilder = new Builder(array(), $this->logger);

        $this->logger->notice(
            sprintf(
                "Writing default configuration to config file '%s'",
                $this->_configFilename
            )
        );

        file_put_contents(
            $this->_configFilename,
            $defaultBuilder->getConfigAnnotated()
        );
    }

    /**
     * Show current configuration
     *
     * @return void
     */
    public function showConfig()
    {
        $config = $this->readConfigFile($this->_configFilename);

        $this->logger->notice(
            sprintf(
                "Current config: (%s)\n---------------\n%s",
                $this->_configFilename,
                Yaml::dump($config)
            )
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * Display help message
     *
     * @return void
     */
    public function displayHelp()
    {
        print Version::renderVersion();

        print "A markdown based build and documentation system for OOCSS\n\n";
        print "Usage: holograph <action> [OPTIONS]\n";
        print "\nActions:\n";
        print "  init : Initialize environment for holograph (write conf file with defaults)\n";
        print "  config : Show current configuration parameters\n";
        print "  build : Build the style guide HTML/CSS\n";
        print "  help : Display program help and exit\n";
        print "\nOptions:\n";
        print "  -c <file> | --conf <file> : Use alternate configuration file\n";
        print "  -h | --help : Display program help and exit\n";
        print "  -q | --quiet : Quiet mode (Don't output anything)\n";
        print "  -v | --verbose : Verbose output mode\n";
        print "  --version : Display program version and exit\n";
        print "  --compat : Use hologram compatible mode (header.html/footer.html)\n";
    }
    // @codingStandardsIgnoreEnd

    /**
     * Read config file
     * 
     * @param string $configFile Path to configuration file to load
     * @param bool $throwException Whether to throw exception if file missing
     * @return array
     */
    public function readConfigFile($configFile, $throwException = false)
    {
        if (!file_exists($configFile)) {
            if ($throwException) {
                throw new \Exception(
                    sprintf(
                        "Config file '%s' not found. (Path: %s)",
                        $configFile, getcwd()
                    )
                );
            }

            $this->logger->warning(
                sprintf(
                    "Config file '%s' not found. "
                    . "Using default configuration. (Path: %s)",
                    $configFile, getcwd()
                )
            );
            return array();
        }

        $this->logger->info(sprintf("Using config file '%s'", $configFile));

        $config = Yaml::parse($configFile);

        if ($config == $configFile) {
            return array();
        }

        return $config;
    }
}
