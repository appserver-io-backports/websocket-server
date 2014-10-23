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
use TechDivision\ApplicationServer\AbstractManagerFactory;

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
class HandlerManagerFactory extends AbstractManagerFactory
{

    /**
     * The main method that creates new instances in a separate context.
     *
     * @return void
     */
    public function run()
    {

        while (true) { // we never stop

            $this->synchronized(function ($self) {

                // make instances local available
                $instances = $self->instances;
                $application = $self->application;
                $initialContext = $self->initialContext;

                // register the default class loader
                $initialContext->getClassLoader()->register(true, true);

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
                $instances[] = $handlerManager;

                // wait for the next instance to be created
                $self->wait();

            }, $this);
        }
    }
}
