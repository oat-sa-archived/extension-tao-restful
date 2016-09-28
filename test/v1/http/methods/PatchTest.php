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

class PatchTest extends RestApiUnitTestRunner
{
    /**
     * Partial data update
     * Update only the specified data
     */
    public function testHttpPatch()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources/1',
            'REQUEST_METHOD' => 'PATCH',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $this->request = $this->request->withAttribute('id', 1);
        $putData = [
            'title' => 'Carrot',
            'color' => 'orange',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->response = new Response();
        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(5, count($this->getStorage()->searchInstances()[0]));
        $this->assertEquals('Carrot', $this->getStorage()->searchInstances()[0]['title']);
        $this->assertEquals('orange', $this->getStorage()->searchInstances()[0]['color']);
    }

    public function testHttpPatchOnListOfTheData()
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources',
            'REQUEST_METHOD' => 'PATCH',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $putData = [
            'title' => 'Carrot',
            'color' => 'orange',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->response = new Response();
        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals('{"errors":["Forbidden to updating list of the resources"]}', (string)$this->response->getBody());
    }

    public function testHttpPatchWithError()
    {
        // replace default body data
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources/1',
            'REQUEST_METHOD' => 'PATCH',
        ]);

        $this->request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $this->request = $this->request->withAttribute('id', 1);
        $putData = [
            'id' => 2,
            'title' => 'Carrot',
            'color' => 'orange',
        ];
        $this->request = $this->request->withParsedBody($putData);
        $this->request = $this->request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->response = new Response();
        $this->routerRunner($this->request, $this->response);

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals('{"errors":["You can not change id of the resource"]}', (string)$this->response->getBody());
    }
}
