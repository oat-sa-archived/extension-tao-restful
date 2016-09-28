<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2016  (original work) Open Assessment Technologies SA;
 *
 * @author Alexander Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoRestAPI\test\v1\Mocks;


use InvalidArgumentException;
use Slim\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Uri;

/**
 * Class EnvironmentTestTrait
 * @package oat\taoRestAPI\test
 *
 * @author Alexander Zagovorichev <zagovorichev@gmail.com>
 */
trait EnvironmentTrait
{
    /**
     * @var App
     */
    private $app = null;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Http request
     *
     * if route == $requestUri It can be used with three parameters request('GET', '/items', function(){})
     *
     * @param string $requestMethod [GET, POST, OPTIONS, PUT, PATCH, DELETE]
     * @param string $route - '/item/{id}'
     * @param string $requestUri ('/item/1')
     * @param $callable - function for route
     * @return mixed
     */
    public function request($requestMethod = '', $route = '', $requestUri = '', $callable = null)
    {
        $numArgs = func_num_args();
        $argList = func_get_args();
        if ($numArgs == 3) {
            list($requestMethod, $requestUri, $callable) = $argList;
            $route = $requestUri;
        } elseif ($numArgs == 4) {
            list($requestMethod, $route, $requestUri, $callable) = $argList;
        } else {
            throw new InvalidArgumentException('Number of arguments can be 3 or 4');
        }

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('$callable must be a closure function');
        }

        $app = new App();

        // for running unit test in slim container
        $app->any($route, function ($req, $res, $args) use ($callable) {
            return $callable($req, $res, $args);
        });

        $queryString = '';
        if (strpos($requestUri, '?')) {
            list($requestUri, $queryString) = explode('?', $requestUri);
        }

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => $requestUri,
            'QUERY_STRING' => $queryString,
            'REQUEST_METHOD' => $requestMethod,
        ]);

        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $this->request = new Request($requestMethod, $uri, $headers, $cookies, $serverParams, $body);
        $this->response = new Response();

        // Invoke app
        return $app($this->request, $this->response);
    }
}
