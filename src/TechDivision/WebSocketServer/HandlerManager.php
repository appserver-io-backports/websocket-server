<?php

/**
 * TechDivision\WebSocketServer\HandlerManager
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Library
 * @package   TechDivision_WebSocketServer
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebSocketServer
 * @link      http://www.appserver.io
 */

namespace TechDivision\WebSocketServer;

use Ratchet\MessageComponentInterface;
use TechDivision\Storage\GenericStackable;
use TechDivision\Storage\StackableStorage;
use TechDivision\WebSocketProtocol\Request;
use TechDivision\WebSocketProtocol\Handler;
use TechDivision\WebSocketProtocol\HandlerContext;
use TechDivision\WebContainer\Exceptions\InvalidApplicationArchiveException;

/**
 * The handler manager handles the handlers registered for the application.
 *
 * @category  Library
 * @package   TechDivision_WebSocketServer
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebSocketServer
 * @link      http://www.appserver.io
 */
class HandlerManager extends GenericStackable implements HandlerContext
{

    /**
     * Initializes the handler manager.
     *
     * @return void
     */
    public function __construct()
    {

        // initialize the member variables
        $this->webappPath = '';
        $this->handlerLocator = null;

        // initialize the stackabls
        $this->handlers = new GenericStackable();
        $this->handlerMappings = new GenericStackable();
        $this->initParameters = new GenericStackable();
    }

    /**
     * Injects the absolute path to the web application.
     *
     * @param string $webappPath The path to this web application
     *
     * @return void
     */
    public function injectWebappPath($webappPath)
    {
        $this->webappPath = $webappPath;
    }

    /**
     * Injects the handler locator that locates the requested handler.
     *
     * @param \TechDivision\WebSocketServer\ResourceLocatorInterface $handlerLocator The handler locator
     *
     * @return void
     */
    public function injectHandlerLocator(ResourceLocatorInterface $handlerLocator)
    {
        $this->handlerLocator = $handlerLocator;
    }

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @return \TechDivision\WebContainer\WebApplication The connected application
     */
    public function initialize()
    {
        $this->registerHandlers();
        return $this;
    }

    /**
     * Finds all handlers which are provided by the webapps and initializes them.
     *
     * @return void
     * @throws \TechDivision\WebSocketServer\InvalidHandlerClassException Is thrown if a no handler class has been defined in handler configuration
     * @throws \TechDivision\WebSocketServer\InvalidHandlerMappingException Is thrown if a no handler mapping relates to a invalid handler class
     */
    protected function registerHandlers()
    {

        // the phar files have been deployed into folders
        if (is_dir($folder = $this->getWebappPath())) {

            // it's no valid application without at least the web.xml file
            if (!file_exists($web = $folder . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'handler.xml')) {
                return;
            }

            // load the application config
            $config = new \SimpleXMLElement(file_get_contents($web));

            // initialize the context by parsing the context-param nodes
            foreach ($config->xpath('/web-app/context-param') as $contextParam) {
                $this->addInitParameter((string) $contextParam->{'param-name'}, (string) $contextParam->{'param-value'});
            }

            // initialize the handlers by parsing the handler-mapping nodes
            foreach ($config->xpath('/web-app/handler') as $handler) {

                // load the handler name and check if it already has been initialized
                $handlerName = (string) $handler->{'handler-name'};
                if (array_key_exists($handlerName, $this->handlers)) {
                    continue;
                }

                // try to resolve the mapped handler class
                $className = (string) $handler->{'handler-class'};
                if (!count($className)) {
                    throw new InvalidHandlerClassException(sprintf('No handler class defined for handler %s', $handler->{'handler-class'}));
                }

                // instantiate the handler
                $instance = new $className();

                // initialize the handler configuration
                $handlerConfig = new HandlerConfiguration();
                $handlerConfig->injectHandlerContext($this);
                $handlerConfig->injectHandlerName($handlerName);
                $handlerConfig->injectWebappPath($this->getWebappPath());

                // append the init params to the handler configuration
                foreach ($handler->{'init-param'} as $initParam) {
                    $handlerConfig->addInitParameter((string) $initParam->{'param-name'}, (string) $initParam->{'param-value'});
                }

                // initialize the handler
                $instance->init($handlerConfig);

                // the handler is added to the dictionary using the complete request path as the key
                $this->addHandler($handlerName, $instance);
            }

            // initialize the handlers by parsing the handler-mapping nodes
            foreach ($config->xpath('/web-app/handler-mapping') as $mapping) {

                // load the url pattern and the handler name
                $urlPattern = (string) $mapping->{'url-pattern'};
                $handlerName = (string) $mapping->{'handler-name'};

                // make sure that the URL pattern always starts with a leading slash
                $urlPattern = ltrim($urlPattern, '/');

                // the handler is added to the dictionary using the complete request path as the key
                if (!array_key_exists($handlerName, $this->handlers)) {
                    throw new InvalidHandlerMappingException(sprintf("Can't find handler %s for url-pattern %s", $handlerName, $urlPattern));
                }

                // append the url-pattern - handler mapping to the array
                $this->handlerMappings['/' . $urlPattern] = (string) $mapping->{'handler-name'};
            }
        }
    }

    /**
     * Sets the registered handlers.
     *
     * @param \TechDivision\Storage\GenericStackable $handler An array with the web socket handlers to be registered
     *
     * @return void
     */
    public function setHandlers($handler)
    {
        $this->handlers = $handler;
    }

    /**
     * Returns the registered handlers.
     *
     * @return \TechDivision\Storage\GenericStackable The initialized web socket handlers
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Registers a handler under the passed key.
     *
     * @param string                                  $key     The key to register with the handler with
     * @param \TechDivision\WebSocketProtocol\Handler $handler The handler to be registered
     *
     * @return void
     */
    public function addHandler($key, Handler $handler)
    {
        $this->handlers[$key] = $handler;
    }

    /**
     * Returns the handler mappings found in the
     * configuration file.
     *
     * @return array The handler mappings
     */
    public function getHandlerMappings()
    {
        return $this->handlerMappings;
    }

    /**
     * Returns the handler for the passed name.
     *
     * @param string $key The name of the handler to return
     *
     * @return \TechDivision\WebSocktContainer\Handlers\Handler The handler instance
     */
    public function getHandler($key)
    {
        if (array_key_exists($key, $this->handlers)) {
            return $this->handlers[$key];
        }
    }

    /**
     * Register's the init parameter under the passed name.
     *
     * @param string $name  Name to register the init parameter with
     * @param string $value The value of the init parameter
     *
     * @return void
     */
    public function addInitParameter($name, $value)
    {
        $this->initParameter[$name] = $value;
    }

    /**
     * Return's the init parameter with the passed name.
     *
     * @param string $name Name of the init parameter to return
     *
     * @return string The requested parameter
     */
    public function getInitParameter($name)
    {
        if (array_key_exists($name, $this->initParameter)) {
            return $this->initParameter[$name];
        }
    }

    /**
     * Returns the path to the webapp.
     *
     * @return string The path to the webapp
     */
    public function getWebappPath()
    {
        return $this->webappPath;
    }

    /**
     * Return the handler locator instance.
     *
     * @return \TechDivision\WebSocketServer\ResourceLocatorInterface The handler locator instance
     */
    public function getHandlerLocator()
    {
        return $this->handlerLocator;
    }

    /**
     * Tries to locate the handler that handles the request and returns the instance if one can be found.
     *
     * @param \TechDivision\WebSocketProtocol\Request $request The request instance
     *
     * @return \Ratchet\MessageComponentInterface The handler that maps the request instance
     * @see \TechDivision\WebSocketServer\Service\Locator\ResourceLocatorInterface::locate()
     */
    public function locate(Request $request)
    {
        return $this->getHandlerLocator()->locate($this, $request);
    }

    /**
     * Initializes the manager instance.
     *
     * @return void
     * @see \TechDivision\Application\Interfaces\ManagerInterface::initialize()
     */
    public function getIdentifier()
    {
        return HandlerContext::IDENTIFIER;
    }

    /**
     * Returns the value with the passed name from the context.
     *
     * @param string $key The key of the value to return from the context.
     *
     * @return mixed The requested attribute
     */
    public function getAttribute($key)
    {
        throw new \Exception(sprintf('%s is not implemented yes', __METHOD__));
    }
}
