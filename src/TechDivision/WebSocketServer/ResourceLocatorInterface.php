<?php

/**
 * TechDivision\WebSocketServer\ResourceLocatorInterface
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

use Guzzle\Http\Message\RequestInterface;

/**
 * Interface for the resource locator instances.
 *
 * @category  Library
 * @package   TechDivision_WebSocketServer
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/TechDivision_WebSocketServer
 * @link      http://www.appserver.io
 */
interface ResourceLocatorInterface
{

    /**
     * Tries to locate the handler that handles the request and returns the instance if one can be found.
     *
     * @param \TechDivision\WebSocketServer\HandlerManager $handlerManager The handler manager
     * @param \Guzzle\Http\Message\RequestInterface $request The request instance
     *
     * @return \Ratchet\MessageComponentInterface The handler that maps the request instance
     */
    public function locate(HandlerManager $handlerManager, RequestInterface $request);
}
