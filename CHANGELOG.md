# Version 0.3.4

## Bugfixes

* Inject all Stackable instances instead of initialize them in HandlerManager::__construct => pthreads 2.x compatibility

## Features

* None

# Version 0.3.3

## Bugfixes

* None

## Features

* Switch to new ClassLoader + ManagerInterface
* Add configuration parameters to manager configuration

# Version 0.3.2

## Bugfixes

* None

## Features

* Replace container notify() call with member variable that holds the server state

# Version 0.3.1

## Bugfixes

* None

## Features

* Refactoring ANT PHPUnit execution process
* Composer integration by optimizing folder structure (move bootstrap.php + phpunit.xml.dist => phpunit.xml)
* Switch to new appserver-io/build build- and deployment environment