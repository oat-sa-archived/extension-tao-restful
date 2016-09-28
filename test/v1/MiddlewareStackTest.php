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

namespace oat\taoRestAPI\test\v1;


use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoRestAPI\test\v1\Mocks\KernelStack;
use ReflectionProperty;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Uri;
use Slim\Http\Request;
use Slim\Http\Response;

class MiddlewareStackTest extends TaoPhpUnitTestRunner
{
    public function testSeedsMiddlewareStack()
    {
        $stack = new KernelStack();
        $stack->add(function ($req, $res, $next) {
            return $res->write('Hi');
        });
        $prop = new ReflectionProperty($stack, 'stack');
        $prop->setAccessible(true);

        $this->assertSame($stack, $prop->getValue($stack)->bottom());
    }

    public function testCallMiddlewareStack()
    {
        // Build middleware stack
        $stack = new KernelStack();
        $stack->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Request
        $uri = Uri::createFromString('https://example.com:443/foo/bar?abc=123');
        $headers = new Headers();
        $cookies = [];
        $serverParams = [];
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        // Response
        $response = new Response();

        // Invoke call stack
        $res = $stack->callMiddlewareStack($request, $response);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }
}
