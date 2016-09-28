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

namespace oat\taoRestAPI\model\v1\http\Request;


use common_exception_ClientException;
use oat\taoRestAPI\exception\HttpRequestException;
use oat\taoRestAPI\model\HttpDataFormatInterface;
use tao_helpers_Http;

class DataFormat implements HttpDataFormatInterface
{

    private $acceptedMimeTypes = [
        'application/json' => 'JsonEncoder',
        'text/xml' => 'XmlEncoder', 
        'application/xml' => 'XmlEncoder', 
        //'application/rdf+xml' => 'RdfEncoder', todo
    ];
    
    public function encoder()
    {
        // default
        $format = 'JsonEncoder';
        
        if(!empty($_SERVER['HTTP_ACCEPT'])){
            try {
                $accept = tao_helpers_Http::acceptHeader(array_keys($this->acceptedMimeTypes), $_SERVER['HTTP_ACCEPT']);
                $format = $this->acceptedMimeTypes[$accept];
            }
            catch (common_exception_ClientException $e) {
                throw new HttpRequestException(__('Not acceptable encoding format'), 406);
            }
        }
        
        $encoder = '\oat\taoRestAPI\model\v1\dataEncoder\\' . $format;
        return new $encoder();
    }
}
