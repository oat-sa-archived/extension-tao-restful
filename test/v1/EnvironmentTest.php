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

use InvalidArgumentException;
use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoRestAPI\test\v1\Mocks\EnvironmentTrait;

class EnvironmentTest extends TaoPhpUnitTestRunner
{
    use EnvironmentTrait;

    public function testEnvironment()
    {
        $self = $this;
        $resOut = $this->request('GET', '/list', function($req, $res) use ($self) {
            $self->assertInstanceOf('\Slim\Http\Request', $req);
            $res->write('Hello');
            return $res;
        });

        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $resOut);
        $this->assertEquals('Hello', (string)$this->response->getBody());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUnsupportedRequestMethod()
    {
        $this->request('FAILED', '/', function () {});
    }
}
