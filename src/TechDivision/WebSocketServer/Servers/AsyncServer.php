<?php

/**
 * \TechDivision\WebSocketServer\Servers\AsyncServer
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Webserver
 * @package    TechDivision_WebSocketServer
 * @subpackage Servers
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebSocketServer
 */

namespace TechDivision\WebSocketServer\Servers;

use TechDivision\WebServer\Dictionaries\ModuleVars;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\WebServer\Interfaces\ServerConfigurationInterface;
use TechDivision\WebServer\Interfaces\ServerContextInterface;
use TechDivision\WebServer\Interfaces\ServerInterface;
use TechDivision\WebServer\Interfaces\ConfigInterface;
use TechDivision\WebServer\Exceptions\ModuleNotFoundException;
use TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException;

/**
 * A asynchronous server implementation based on Ratchet.
 *
 * @category   Webserver
 * @package    TechDivision_WebSocketServer
 * @subpackage Servers
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       https://github.com/techdivision/TechDivision_WebSocketServer
 */
class AsyncServer extends \Thread implements ServerInterface
{

    /**
     * Hold's the server context instance
     *
     * @var \TechDivision\WebServer\Interfaces\ServerContextInterface The server context instance
     */
    protected $serverContext;

    /**
     * Constructs the server instance
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The server context instance
     */
    public function __construct(ServerContextInterface $serverContext)
    {
        // set context
        $this->serverContext = $serverContext;
        // start server thread
        $this->start();
    }

    /**
     * Return's the config instance
     *
     * @return \TechDivision\WebServer\Interfaces\ServerContextInterface
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Start's the server's worker as defined in configuration
     *
     * @return void
     *
     * @throws \TechDivision\WebServer\Exceptions\ModuleNotFoundException
     * @throws \TechDivision\WebServer\Exceptions\ConnectionHandlerNotFoundException
     */
    public function run()
    {
        // set current dir to base dir for relative dirs
        chdir(WEBSERVER_BASEDIR);

        // setup autoloader
        require WEBSERVER_AUTOLOADER;

        // init server context
        $serverContext = $this->getServerContext();

        // init config var for shorter calls
        $serverConfig = $serverContext->getServerConfig();

        // init server name
        $serverName = $serverConfig->getName();

        // init logger
        $logger = $serverContext->getLogger();

        $logger->debug(
            sprintf("starting %s (%s)", $serverName, __CLASS__)
        );

        // initialize the connection handler
        $connectionHandler = null;

        // initiate server connection handlers
        $connectionHandlersTypes = $serverConfig->getConnectionHandlers();
        foreach ($connectionHandlersTypes as $connectionHandlerType) {

            // check if conenction handler type exists
            if (!class_exists($connectionHandlerType)) {
                throw new ConnectionHandlerNotFoundException($connectionHandlerType);
            }
            // instantiate connection handler type
            $connectionHandler = new $connectionHandlerType(
            	$serverContext->getContainer()->getApplications()
			);

            $logger->debug(
                sprintf("%s init connectionHandler (%s)", $serverName, $connectionHandlerType)
            );

            // init connection handler with serverContext (this)
            $connectionHandler->init($serverContext);

            // inject modules
            $connectionHandler->injectModules($modules);

            // stop, because we only support one connection handler
            break;
        }

        // get class names
        $socketType = $serverConfig->getSocketType();

        // setup server bound on local adress
        $serverConnection = $socketType::getServerInstance(
       		$connectionHandler, $serverConfig->getPort(), $serverConfig->getAddress()
        );

        $logger->info(
            sprintf("%s listing on %s:%s...", $serverName, $serverConfig->getAddress(), $serverConfig->getPort())
        );

        // start the server connection
        $serverConnection->run();
    }
}