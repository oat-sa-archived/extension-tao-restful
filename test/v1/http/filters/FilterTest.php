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

namespace oat\taoRestAPI\test\v1\http\Response;


use oat\taoRestAPI\test\v1\RestApiUnitTestRunner;

class FilterTest extends RestApiUnitTestRunner
{

    public function testFilter()
    {
        $this->request('GET', '/resources', '/resources?type=citrus', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });
        
        $this->assertEquals(3, count($this->response->getResourceData()));
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-2/3'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));

        foreach ($this->response->getResourceData() as $item) {
            $this->assertEquals('citrus', $item['type']);
        }
    }

    public function testMultipleFilter()
    {
        $this->request('GET', '/resources', '/resources?type=citrus,vegetable&form=circle', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(2, count($this->response->getResourceData()));
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());

        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-1/2'], $this->response->getHeader('Content-Range'));

        foreach ($this->response->getResourceData() as $item) {
            $this->assertTrue(in_array($item['type'], ['citrus', 'vegetable']));
            $this->assertTrue(in_array($item['form'], ['circle']));
        }
    }
}
