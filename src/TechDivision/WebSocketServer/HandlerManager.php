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
class HandlerManager
{

    /**
     *
     * @var array
     */
    protected $handler = array();

    /**
     * The absolute path to the web application.
     *
     * @var string
     */
    protected $webappPath;

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
     */
    protected function registerHandlers()
    {

        // the phar files have been deployed into folders
        if (is_dir($folder = $this->getWebappPath())) {

            // it's no valid application without at least the handler.xml file
            if (!file_exists($web = $folder . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'handler.xml')) {
                return;
            }

            // load the application config
            $config = new \SimpleXMLElement(file_get_contents($web));

            /**
             *
             * @var $mapping \SimpleXMLElement
             */
            foreach ($config->xpath('/web-app/handler-mapping') as $mapping) {

                // try to resolve the mapped handler class
                $className = $config->xpath('/web-app/handler[handler-name="' . $mapping->{'handler-name'} . '"]/handler-class');

                if (count($className) === false) {
                    throw new InvalidApplicationArchiveException(sprintf('No handler class defined for handler %s', $mapping->{'handler-name'}));
                }

                // get the string classname
                $className = (string) array_shift($className);

                // instantiate the handler
                $handler = new $className();

                // load the url pattern
                $urlPattern = (string) $mapping->{'url-pattern'};

                // the handler is added to the dictionary using the complete request path as the key
                $this->addHandler($urlPattern, $handler);
            }
        }
    }

    /**
     *
     * @param array $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     *
     * @return array An array with the initialized web socket handlers
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Registers a handler under the passed key.
     *
     * @param string $key
     *            The handler to key to register with
     * @param \Ratchet\MessageComponentInterface $handler
     *            The handler to be registered
     */
    public function addHandler($key, MessageComponentInterface $handler)
    {
        $this->handler[$key] = $handler;
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
     * Returns the host configuration.
     *
     * @return \TechDivision\ApplicationServer\Configuration The host configuration
     */
    public function getConfiguration()
    {
        throw new \Exception(__METHOD__ . ' not implemented');
    }
}