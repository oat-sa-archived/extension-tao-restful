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

class PaginateTest extends RestApiUnitTestRunner
{
    /**
     * Get all resources
     * with limit, if provided in controller
     */
    public function testHttpGetListAll()
    {
        $this->request('GET', '/resources', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals('[{"id":1,"title":"Potato","type":"vegetable","form":"circle","color":"brown"},{"id":2,"title":"Lemon","type":"citrus","form":"ellipse","color":"yellow"},{"id":3,"title":"Lime","type":"citrus","form":"ellipse","color":"green"},{"id":4,"title":"Carrot","type":"vegetable","form":"conical","color":"orange"},{"id":5,"title":"Orange","type":"citrus","form":"circle","color":"orange"}]', (string)$this->response->getBody());
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(5, count($this->response->getResourceData()));
        $this->assertEquals(['0-4/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(0, count($this->response->getHeader('Link')));
    }

    /**
     * Range is biggest, than allowed
     */
    public function testHttpGetListEnormousRange()
    {
        $this->request('GET', '/resources', '/resources?range=0-50', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals('', $this->response->getResourceData());
        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals([], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('{"errors":["Invalid range"]}', (string)$this->response->getBody());
        $this->assertEquals(0, count($this->response->getHeader('Link')));
    }

    /**
     * Outside request
     */
    public function testHttpGetListOutsideRange()
    {
        $this->request('GET', '/resources', '/resources?range=50-51', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals('', $this->response->getResourceData());
        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals([], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('{"errors":["Invalid range"]}', (string)$this->response->getBody());
        $this->assertEquals(0, count($this->response->getHeader('Link')));
    }

    public function testHttpGetListInvalidRange()
    {
        $this->request('GET', '/resources', '/resources?range=3-2', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals('', $this->response->getResourceData());
        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals([], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('{"errors":["Invalid range"]}', (string)$this->response->getBody());
        $this->assertEquals(0, count($this->response->getHeader('Link')));
    }
    
    public function testHttpGetListRangeLessThen0()
    {
        // less then 0
        $this->request('GET', '/resources', '/resources?range=-1-2', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals('', $this->response->getResourceData());
        $this->assertEquals(400, $this->response->getStatusCode());
        $this->assertEquals('Bad Request', $this->response->getReasonPhrase());
        $this->assertEquals([], $this->response->getHeader('Content-Range'));
        $this->assertEquals([], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('{"errors":["Incorrect range parameter. Try to use: ?range=0-25"]}', (string)$this->response->getBody());
        $this->assertEquals(0, count($this->response->getHeader('Link')));
    }

    /**
     * Last data page
     */
    public function testHttpGetListLastRange()
    {
        $this->request('GET', '/resources', '/resources?range=3-7', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(2, count($this->response->getResourceData()));
        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['3-4/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(4, count($this->response->getHeader('Link')));
        $this->assertEquals([
                '&lt;http://localhost/resources?range=0-1&gt;; rel="first"',
                '&lt;http://localhost/resources?range=3-4&gt;; rel="last"',
                '&lt;http://localhost/resources?range=1-2&gt;; rel="prev"',
                '&lt;http://localhost/resources?range=0-1&gt;; rel="next"',
        ], $this->response->getHeader('Link'));
    }

    /**
     * Range of resources
     */
    public function testHttpGetListRange()
    {
        $this->request('GET', '/resources', '/resources?range=0-3', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(4, count($this->response->getResourceData()));
        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['0-3/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(4, count($this->response->getHeader('Link')));
        $this->assertEquals([
            '&lt;http://localhost/resources?range=0-3&gt;; rel="first"',
            '&lt;http://localhost/resources?range=1-4&gt;; rel="last"',
            '&lt;http://localhost/resources?range=1-4&gt;; rel="prev"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="next"',
        ], $this->response->getHeader('Link'));
    }

    public function testHttpGetListZeroResource()
    {
        $this->request('GET', '/resources', '/resources?range=0-0', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(1, count($this->response->getResourceData()));
        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['0-0/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(4, count($this->response->getHeader('Link')));
        $this->assertEquals([
            '&lt;http://localhost/resources?range=0-0&gt;; rel="first"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="last"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="prev"',
            '&lt;http://localhost/resources?range=1-1&gt;; rel="next"',
        ], $this->response->getHeader('Link'));
    }

    public function testHttpGetListNonZeroResource()
    {
        $this->request('GET', '/resources', '/resources?range=3-3', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(1, count($this->response->getResourceData()));
        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['3-3/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(4, count($this->response->getHeader('Link')));
        $this->assertEquals([
            '&lt;http://localhost/resources?range=0-0&gt;; rel="first"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="last"',
            '&lt;http://localhost/resources?range=2-2&gt;; rel="prev"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="next"',
        ], $this->response->getHeader('Link'));
    }

    public function testHttpGetListLastResource()
    {
        $this->request('GET', '/resources', '/resources?range=4-4', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(1, count($this->response->getResourceData()));
        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['4-4/5'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals(4, count($this->response->getHeader('Link')));
        $this->assertEquals([
            '&lt;http://localhost/resources?range=0-0&gt;; rel="first"',
            '&lt;http://localhost/resources?range=4-4&gt;; rel="last"',
            '&lt;http://localhost/resources?range=3-3&gt;; rel="prev"',
            '&lt;http://localhost/resources?range=0-0&gt;; rel="next"',
        ], $this->response->getHeader('Link'));
    }
}
