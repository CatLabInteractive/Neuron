<?php

/**
 * @author		Bram(us) Van Damme <bramus@bram.us>
 * @copyright	Copyright (c), 2013 Bram(us) Van Damme
 * @license		MIT public license
 */

namespace Neuron;

use Neuron\Exceptions\InvalidParameter;
use Neuron\Exceptions\ResponseException;
use Neuron\Interfaces\Controller;
use Neuron\Interfaces\Module;
use Neuron\Models\Router\Route;
use Neuron\Net\Request;
use Neuron\Net\Response;
use Neuron\Tools\ControllerFactory;

class Router {


    /**
     * @var array The route patterns and their handling functions
     */
    private $routes = array();

    /**
     * @var object The function to be executed when no route has been matched
     */
    private $notFound;


    /**
     * @var string Current baseroute, used for (sub)route mounting
     */
    private $baseroute = '';


    /**
     * @var string The Request Method that needs to be handled
     */
    private $method = '';

    /**
     * @var \Neuron\Net\Request
     */
    private $request;

    /** @var Module|null */
    private $module = null;

    /** @var [callable[],int] */
    private $filters = array ();

    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $app
     */
    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function match ($methods, $pattern, $fn) {

        $pattern = $this->baseroute . '/' . trim($pattern, '/');
        $pattern = $this->baseroute ? rtrim($pattern, '/') : $pattern;

        $route = new Route ($pattern);
        $route->setFunction ($fn);
        $route->setModule ($this->module);

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = $route;
        }

        return $route;
    }

    /**
     * Set the module that will be used in the constructor for all Controllers
     * that match the path of all future matches.
     * @param Module $module
     */
    private function setModule (Module $module = null)
    {
        $this->module = $module;
    }

    /**
     * Shorthand for a route accessed using GET
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function get($pattern, $fn) {
        return $this->match('GET', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using POST
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function post($pattern, $fn) {
        return $this->match('POST', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using PATCH
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function patch($pattern, $fn) {
        return $this->match('PATCH', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using DELETE
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function delete($pattern, $fn) {
        return $this->match('DELETE', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using PUT
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function put($pattern, $fn) {
        return $this->match('PUT', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using OPTIONS
     *
     * @param string $pattern A route pattern such as /about/system
     * @param mixed $fn The handling function to be executed
     * @return Route
     */
    public function options($pattern, $fn) {
        return $this->match('OPTIONS', $pattern, $fn);
    }

    /**
     * @param $prefix
     * @param Interfaces\Module $module
     */
    public function module ($prefix, Module $module)
    {
        $module->initialize ($prefix);

        $this->setModule ($module);
        $module->setRoutes ($this, $prefix);
        $this->setModule (null);
    }

    /**
     * Execute the router: Loop all defined before middlewares and routes, and execute the handling function if a mactch was found
     *
     * @param Request $request
     */
    public function run (Request $request)
    {
        try {

            // Define which method we need to handle
            $this->method = $request->getMethod();

            // Set request
            $this->request = $request;

            // Handle all routes
            $numHandled = 0;
            if (isset($this->routes[$this->method]))
                $numHandled = $this->handle($this->routes[$this->method], true);

            // If no route was handled, trigger the 404 (if any)
            if ($numHandled == 0) {
                if ($this->notFound) {
                    //call_user_func($this->notFound);
                    $this->handleMatch($this->notFound, array());
                } else {
                    $request = Response::error('Page not found.', Response::STATUS_NOTFOUND);
                    $request->output();
                }
            }

            // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
            if ($_SERVER['REQUEST_METHOD'] == 'HEAD') ob_end_clean();

        } catch (ResponseException $e) {
            $e->getResponse()->output();
        }
    }

    /**
     * During execution of the dispatch, this method will return the request.
     * @return Request|null
     */
    public function getRequest ()
    {
        return $this->request;
    }

    /**
     * Set the 404 handling function
     * @param $fn
     * @return Route|object
     */
    public function set404($fn) {
        $this->notFound = new Route ("404");
        $this->notFound->setFunction ($fn);

        return $this->notFound;
    }

    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     * @param array $routes Collection of route patterns and their handling functions
     * @throws InvalidParameter
     * @return \Neuron\Net\Response The response
     */
    private function handle ($routes) {

        // The current page URL
        $numHandled = 0;

        // Loop all routes
        foreach ($routes as $route) {

            if (!$route instanceof Route)
                throw new InvalidParameter ("Route contains invalid models.");

            // we have a match!
            if ($params = $this->request->parseRoute ($route)) {

	            if (!is_array ($params)) {
		            $params = array ();
	            }

                // call the handling function with the URL parameters
                $this->handleMatch ($route, $params);
                //call_user_func_array($route['fn'], $params);

                // yay!
                $numHandled ++;

                // If we need to quit, then quit
                //if ($quitAfterRun) break;

            }

        }

        return $numHandled;

    }

    /**
     * Add a filter that can be added.
     * @param string $filtername
     * @param callable $method
	 * @param int $priority
     */
    public function addFilter ($filtername, callable $method = null, $priority = 0)
    {
        $this->filters[$filtername] = [
        	'callback' => $method,
			'priority' => $priority
		];
    }

    /**
     * @param Route $route
     * @param $params
     * @throws InvalidParameter
     */
    private function handleMatch (Route $route, $params)
    {
        $function = $route->getFunction ();

        // Check for additional parameters
        foreach ($route->getParameters () as $v) {
            $params[] = $v;
        }

        // First handle the filters

		// Find all filters that need to be execued
		$filtersToExecute = [];

        foreach ($route->getFilters () as $filter)
        {
            // Check if exist
            if (!isset ($this->filters[$filter->getName ()])) {
                throw new InvalidParameter ("Filter " . $filter->getName() . " is not registered in the router.");
            }

            $filterCallback = $this->filters[$filter->getName()];

            $filtersToExecute[] = [
            	'callback' => $filterCallback['callback'],
				'priority' => $filterCallback['priority'],
				'filter' => $filter
			];
        }

        // order filters on priority
		usort($filtersToExecute, function($a, $b) {
			if ($a['priority'] == $b['priority']) {
				return 0;
			}
			return $a['priority'] < $b['priority'] ? 1 : -1;
		});

        foreach ($filtersToExecute as $v) {

        	$filter = $v['filter'];

			$filter->setRequest ($this->request);
			$response = $filter->check ($v['callback'], $params);
			$filter->clearRequest ();

			// If output was not TRUE, handle the filter return value as output.
			if ($response !== true) {
				$this->output ($response);
				return;
			}
		}

        // trigger post filter events
        $this->app->postFilters($this->request);

        if (is_callable ($function))
        {
            $response = call_user_func_array($function, $params);
        }
        else {
            if (strpos ($function, '@'))
            {
                $param = explode ('@', $function);
                if (count ($param) !== 2)
                {
                    throw new InvalidParameter ("Controller@method syntax not valid for " . $function);
                }

                $response = $this->handleController ($param[0], $param[1], $params, $route->getModule ());
            }
            else {
                throw new InvalidParameter ("Method not found.");
            }
        }

        $this->output ($response);
    }

    private function output ($response)
    {
        if ($response)
        {
            if ($response instanceof Response)
            {
                $response->output ();
            }
            else {
                echo $response;
            }
        }
    }

    /**
     * @param string $controller
     * @param string $method
     * @param array $params
     * @param Module $module
     * @throws Exceptions\DataNotFound
     * @throws InvalidParameter
     * @return mixed
     */
    private function handleController ($controller, $method, $params, $module = null)
    {
        $controller = ControllerFactory::getInstance ()->getController ($controller, $module);

        // If the found controller implements the Controller interface, we set the request.
        if ($controller instanceof Controller)
        {
            $controller->setRequest ($this->request);
        }

        if (is_callable (array ($controller, $method)))
        {
            return call_user_func_array(array ($controller, $method), $params);
        }
        else {
            throw new InvalidParameter ("Method not found.");
        }
    }

}
