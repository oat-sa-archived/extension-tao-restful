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

namespace oat\taoRestAPI\test\v1\http\methods;


use oat\taoRestAPI\test\v1\Mocks\Response;
use oat\taoRestAPI\test\v1\RestApiUnitTestRunner;
use Slim\Http\Environment;
use Slim\Http\Request;

class PutTest extends RestApiUnitTestRunner
{
    /**
     * Update full resource data
     * Update only fields that is set, all the other fields will be deleted
     */
    public function testHttpPut()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources/1',
            'REQUEST_METHOD' => 'PUT',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);
        
        // add Attribute in request
        $this->request = $this->request->withAttribute('id', 1);
        $putData = [
            'id' => 1,
            'title' => 'beef',
            'type' => 'meat',
            'form' => 'circle',
            'color' => 'brown',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->response = new Response();

        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals($putData, $this->getStorage()->searchInstances()[0]);
    }

    public function testHttpPartialPut()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources/1',
            'REQUEST_METHOD' => 'PUT',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $this->request = $this->request->withAttribute('id', 1);
        $putData = [
            'id' => 1,
            'title' => 'Potato',
            'color' => 'vegetable',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->response = new Response();
        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals($putData, $this->getStorage()->searchInstances()[0]);
        $this->assertEquals(3, count($this->getStorage()->searchInstances()[0]));
    }

    public function testHttpPutInvalidData()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources/1',
            'REQUEST_METHOD' => 'PUT',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $this->request = $this->request->withAttribute('id', 1);
        $putData = [
            'title' => 'Potato',
            'color' => 'vegetable',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->response = new Response();
        
        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertNotEquals($putData, $this->getStorage()->searchInstances()[0]);
    }
}
