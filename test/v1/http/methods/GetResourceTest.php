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


use oat\taoRestAPI\test\v1\RestApiUnitTestRunner;

class GetResourceTest extends RestApiUnitTestRunner
{
    public function testHttpGetResource()
    {
        $this->request('GET', '/resources/{id}', '/resources/1', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(5, count($this->response->getResourceData()));
        $this->assertEquals(1, $this->response->getResourceData()['id']);
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * Get one resource partial
     */
    public function testHttpGetWithResourceWithParams()
    {
        $this->request('GET', '/resources/{id}', '/resources/5?fields=id,title,form', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(3, count($this->response->getResourceData()));
        $this->assertEquals(5, $this->response->getResourceData()['id']);
        $this->assertEquals('Orange', $this->response->getResourceData()['title']);
        $this->assertEquals('circle', $this->response->getResourceData()['form']);
        $this->assertEquals(200, $this->response->getStatusCode());
    }
}
