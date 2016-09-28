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

class SortTest extends RestApiUnitTestRunner
{

    public function testSortByOneField()
    {
        $this->request('GET', '/resources', '/resources?sort=title', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });
        
        $this->assertEquals(5, count($this->response->getResourceData()));
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());

        $titles = [];
        foreach ($this->response->getResourceData() as $item) {
            $titles[] = $item['title'];
        }
         
        $this->assertEquals(['Carrot', 'Lemon', 'Lime', 'Orange', 'Potato'], $titles);
    }

    public function testSortByManyFields()
    {
        $this->request('GET', '/resources', '/resources?sort=form,type,id', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(5, count($this->response->getResourceData()));
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());

        $form = $type = $titles = [];
        foreach ($this->response->getResourceData() as $item) {
            $titles[] = $item['title'];
            $form[] = $item['form'];
            $type[] = $item['type'];
        }

        $this->assertEquals(['Orange', 'Potato', 'Carrot', 'Lemon', 'Lime'], $titles);
        $this->assertEquals(['circle', 'circle', 'conical', 'ellipse', 'ellipse'], $form);
        $this->assertEquals(['citrus', 'vegetable', 'vegetable', 'citrus', 'citrus'], $type);
    }
    
    public function testSortDesc()
    {
        $this->request('GET', '/resources', '/resources?sort=title&desc=title', function ($req, $res, $args) {
            return $this->routerRunner($req, $res);
        });

        $this->assertEquals(5, count($this->response->getResourceData()));
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());

        $titles = [];
        foreach ($this->response->getResourceData() as $item) {
            $titles[] = $item['title'];
        }

        $this->assertEquals(['Potato', 'Orange', 'Lime', 'Lemon', 'Carrot'], $titles);   
    }
}
