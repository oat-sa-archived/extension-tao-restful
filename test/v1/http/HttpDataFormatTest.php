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

namespace oat\taoRestAPI\test\v1\httpRequest;


use oat\tao\test\TaoPhpUnitTestRunner;
use oat\taoRestAPI\model\v1\http\Request\DataFormat;

class HttpDataFormatTest extends TaoPhpUnitTestRunner
{
    /**
     * @expectedException \oat\taoRestAPI\exception\HttpRequestException
     */
    public function testIncorrectMimeType()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xls';
        (new DataFormat)->encoder();
    }

    public function testDefaultEncoder()
    {
        $encoder = (new DataFormat)->encoder();
        $this->assertInstanceOf('oat\taoRestAPI\model\v1\dataEncoder\JsonEncoder', $encoder);
    }
    
    public function testJsonEncoder()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $encoder = (new DataFormat)->encoder();
        $this->assertInstanceOf('oat\taoRestAPI\model\v1\dataEncoder\JsonEncoder', $encoder);
        $this->assertEquals('[]', $encoder->encode([]));
        $this->assertEquals('{"key":"val"}', $encoder->encode(['key' => 'val']));
        $this->assertEquals('[{"key":{"key":"val"}},{"key":"val"}]', $encoder->encode([['key' => ['key' => 'val']], ['key' => 'val']]));
    }
    
    public function testXmlEncoder()
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/xml';
        $encoder = (new DataFormat)->encoder();
        $this->assertInstanceOf('oat\taoRestAPI\model\v1\dataEncoder\XmlEncoder', $encoder);
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root/>\n", $encoder->encode([]));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root>\n  <key>val</key>\n</root>\n", $encoder->encode(['key' => 'val']));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root>\n  <element>\n    <key>\n      <key>val</key>\n    </key>\n  </element>\n  <element>\n    <key>val</key>\n  </element>\n</root>\n", $encoder->encode([['key' => ['key' => 'val']], ['key' => 'val']]));
    }
    
    public function testRdfEncoder()
    {
        //...
    }
}
