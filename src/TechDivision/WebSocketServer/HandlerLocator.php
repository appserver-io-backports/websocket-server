<?php

/**
 * TechDivision\WebSocketServer\HandlerLocator
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

use TechDivision\WebSocketProtocol\Request;
use TechDivision\WebSocketServer\HandlerManager;
use TechDivision\WebSocketServer\HandlerNotFoundException;

/**
 * The handler resource locator implementation.
 *
 * @category  Library
 * @package   TechDivision_WebSocketServer
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebSocketServer
 * @link      http://www.appserver.io
 */
class HandlerLocator implements ResourceLocatorInterface
{

    /**
     * Tries to locate the handler that handles the request and returns the instance if one can be found.
     *
     * @param \TechDivision\WebSocketServer\HandlerManager $handlerManager The handler manager
     * @param \TechDivision\WebSocketProtocol\Request      $request        The request instance
     *
     * @return \Ratchet\MessageComponentInterface The handler that maps the request instance
     * @see \TechDivision\WebSocketServer\Service\Locator\ResourceLocatorInterface::locate()
     */
    public function locate(HandlerManager $handlerManager, Request $request)
    {

        // load the path to the (almost virtual handler)
        $handlerPath = $request->getHandlerPath();

        // iterate over all handlers and return the matching one
        foreach ($handlerManager->getHandler() as $urlPattern => $handlerName) {
            if (fnmatch($urlPattern, $handlerPath)) {
                return $handlerManager->getHandler($handlerName);
            }
        }

        // throw an exception if no servlet matches the handler path
        throw new HandlerNotFoundException(
            sprintf("Can't find handler for requested path %s", $handlerPath)
        );
    }
}
