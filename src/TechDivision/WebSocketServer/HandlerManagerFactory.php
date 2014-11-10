<?php

/**
 * TechDivision\WebSocketServer\HandlerManagerFactory
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

use TechDivision\Storage\GenericStackable;
use TechDivision\Application\Interfaces\ApplicationInterface;
use TechDivision\Application\Interfaces\ManagerConfigurationInterface;

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
class HandlerManagerFactory
{

    /**
     * The main method that creates new instances in a separate context.
     *
     * @param \TechDivision\Application\Interfaces\ApplicationInterface          $application          The application instance to register the class loader with
     * @param \TechDivision\Application\Interfaces\ManagerConfigurationInterface $managerConfiguration The manager configuration
     *
     * @return void
     */
    public static function visit(ApplicationInterface $application, ManagerConfigurationInterface $managerConfiguration)
    {

        // initialize the stackabls
        $handlers = new GenericStackable();
        $handlerMappings = new GenericStackable();
        $initParameters = new GenericStackable();

        // initialize the handler locator
        $handlerLocator = new HandlerLocator();

        // initialize the handler manager
        $handlerManager = new HandlerManager();
        $handlerManager->injectHandlers($handlers);
        $handlerManager->injectHandlerMappings($handlerMappings);
        $handlerManager->injectInitParameters($initParameters);
        $handlerManager->injectWebappPath($application->getWebappPath());
        $handlerManager->injectHandlerLocator($handlerLocator);

        // attach the instance
        $application->addManager($handlerManager);
    }
}
