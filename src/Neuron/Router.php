<?php

/**
 * @author		Bram(us) Van Damme <bramus@bram.us>
 * @copyright	Copyright (c), 2013 Bram(us) Van Damme
 * @license		MIT public license
 */

namespace Neuron;

use Neuron\Exceptions\InvalidParameter;

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
     * @var \Neuron\FrontController
     */
    private $frontController;

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods
     *
     * @param string $methods Allowed methods, | delimited
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function match($methods, $pattern, $fn) {

        $pattern = $this->baseroute . '/' . trim($pattern, '/');
        $pattern = $this->baseroute ? rtrim($pattern, '/') : $pattern;

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = array(
                'pattern' => $pattern,
                'fn' => $fn
            );
        }

    }


    /**
     * Shorthand for a route accessed using GET
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function get($pattern, $fn) {
        $this->match('GET', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using POST
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function post($pattern, $fn) {
        $this->match('POST', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using PATCH
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function patch($pattern, $fn) {
        $this->match('PATCH', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using DELETE
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function delete($pattern, $fn) {
        $this->match('DELETE', $pattern, $fn);
    }


    /**
     * Shorthand for a route accessed using PUT
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function put($pattern, $fn) {
        $this->match('PUT', $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using OPTIONS
     *
     * @param string $pattern A route pattern such as /about/system
     * @param object $fn The handling function to be executed
     */
    public function options($pattern, $fn) {
        $this->match('OPTIONS', $pattern, $fn);
    }

    /**
     * @param $prefix
     * @param Interfaces\Module $module
     */
    public function module ($prefix, \Neuron\Interfaces\Module $module)
    {
        $module->initialize ();
        $module->setRoutes ($this, $prefix);
    }

    /**
     * Execute the router: Loop all defined before middlewares and routes, and execute the handling function if a mactch was found
     *
     * @param callable|null $callback Function to be executed after a matching route was handled (= after router middleware)
     * @return \Neuron\Net\Response
     */
    public function run (callable $callback = null) {

        // Set fallback template directory
        \Neuron\Core\Template::addTemplatePath (dirname (dirname (__FILE__)) . '/templates/');

        $request = \Neuron\Net\Request::fromInput ();

        $this->frontController = \Neuron\FrontController::getInstance ();
        $this->frontController->setRequest ($request);

        // Define which method we need to handle
        $this->method = $request->getMethod ();

        // Handle all routes
        $numHandled = 0;
        if (isset($this->routes[$this->method]))
            $numHandled = $this->handle($this->routes[$this->method], true);

        // If no route was handled, trigger the 404 (if any)
        if ($numHandled == 0) {
            if ($this->notFound) {
                //call_user_func($this->notFound);
                $this->handleMatch ($this->notFound, array ());
            }
            else {

                $request = \Neuron\Net\Response::template ('404.phpt');
                $request->setStatus (404);
                $request->output ();
            }
        }
        // If a route was handled, perform the finish callback (if any)
        else {
            if ($callback) $callback();
        }

        // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
        if ($_SERVER['REQUEST_METHOD'] == 'HEAD') ob_end_clean();

    }


    /**
     * Set the 404 handling function
     * @param object $fn The function to be executed
     */
    public function set404($fn) {
        $this->notFound = $fn;
    }


    /**
     * Handle a a set of routes: if a match is found, execute the relating handling function
     * @param array $routes Collection of route patterns and their handling functions
     * @param boolean $quitAfterRun Does the handle function need to quit after one route was matched?
     * @return \Neuron\Net\Response The response
     */
    private function handle($routes) {

        // The current page URL
        $uri = $this->frontController->getRequest ()->getUrl ();

        $numHandled = 0;

        // Loop all routes
        foreach ($routes as $route) {

            // we have a match!
            if (preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {

                // Rework matches to only contain the matches, not the orig string
                $matches = array_slice($matches, 1);

                // Extract the matched URL parameters (and only the parameters)
                $params = array_map(function($match, $index) use ($matches) {

                    // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                    if (isset($matches[$index+1]) && isset($matches[$index+1][0]) && is_array($matches[$index+1][0])) {
                        return trim(substr($match[0][0], 0, $matches[$index+1][0][1] - $match[0][1]), '/');
                    }

                    // We have no following paramete: return the whole lot
                    else {
                        return (isset($match[0][0]) ? trim($match[0][0], '/') : null);
                    }

                }, $matches, array_keys($matches));

                // call the handling function with the URL parameters
                $this->handleMatch ($route['fn'], $params);
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
     * @param $function
     * @param $params
     * @throws InvalidParameter
     */
    private function handleMatch ($function, $params)
    {
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

                $response = $this->handleController ($param[0], $param[1], $params);
            }
            else {
                throw new InvalidParameter ("Method not found.");
            }
        }

        if ($response)
        {
            if ($response instanceof \Neuron\Net\Response)
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
     * @throws Exceptions\DataNotFound
     * @throws InvalidParameter
     * @return mixed
     */
    private function handleController ($controller, $method, $params)
    {
        $controller = \Neuron\Tools\ControllerFactory::getInstance ()->getController ($controller);

        if (is_callable (array ($controller, $method)))
        {
            return call_user_func_array(array ($controller, $method), $params);
        }
        else {
            throw new InvalidParameter ("Method not found.");
        }
    }

}