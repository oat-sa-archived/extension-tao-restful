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
use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\model\v1\dataEncoder\JsonEncoder;
use oat\taoRestAPI\test\v1\Mocks\DB;
use oat\taoRestAPI\test\v1\Mocks\EnvironmentTrait;
use oat\taoRestAPI\test\v1\Mocks\Response;
use oat\taoRestAPI\test\v1\Mocks\TestHttpRoute;
use Slim\Http\Request;

abstract class RestApiUnitTestRunner extends TaoPhpUnitTestRunner
{
    use EnvironmentTrait;

    private $storage;

    public function routerRunner(Request $req, Response &$res)
    {
        $route = $this->getRouter();
        try {
            $route($req, ['type' => 'param', 'key' => 'id']);

            $res = $res->withStatus($route->getStatusCode());
            $res->write((new JsonEncoder())->encode($route->getBodyData()));
            
            // mock func for test
            $res->setResourceData($route->getBodyData());
        } catch (RestApiException $e) {
            $res = $res->withJson(['errors' => [$e->getMessage()]]);
            $res = $res->withStatus($e->getCode());
        }

        $this->addHeadersInResponse($route->getHeaders(), $res);
        
        return $this->response = $res;
    }

    public function getStorage()
    {
        if (!$this->storage) {
            $this->storage = new DB();
        }

        return $this->storage;
    }

    /**
     * @param $router
     * @param $encoder
     * @param $req
     * @param $res
     * @return Response
     */
    protected function runRouterTest($router, $encoder, $req, &$res)
    {
        $router($req, ['type' => 'param', 'key' => 'id']);
        $res = $res->withStatus($router->getStatusCode());
        $this->addHeadersInResponse($router->getHeaders(), $res);
        $res->write($encoder->encode($router->getBodyData()));
        return $res;
    }
    
    private function addHeadersInResponse(array $addHeaders, Response &$res)
    {
        if (count($addHeaders)) {
            foreach ($addHeaders as $name => $header) {
                $res = $res->withHeader($name, $header);
            }
        }
    }

    /**
     * @return TestHttpRoute
     */
    protected function getRouter()
    {
        return new TestHttpRoute($this->getStorage());
    }
}
