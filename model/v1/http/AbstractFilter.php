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

namespace oat\taoRestAPI\model\v1\http;


abstract class AbstractFilter
{

    /**
     * Http Response status code
     * @var int
     */
    protected $statusCode = 0;

    /**
     * Headers for responses
     * 
     * # example ['Content-Range' => ['0-4/5']]
     * 
     * @var array
     */
    protected $headers = [];
    
    /**
     * Options for filters
     * @var array
     *  [
     *      query => string|array from Response
     *  ]
     */
    protected $options = ['query' => ''];

    /**
     * Filters constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->prepare();
    }

    /**
     * Set Http status code
     * @param int $code
     */
    protected function setStatusCode($code=200)
    {
        $this->statusCode = (int)$code;
    }
    
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    protected function addHeader($name='', $values)
    {
        
        if (!is_array($values)) {
            $values = [$values];
        }
        $this->headers = array_merge($this->headers, [$name => $values]);
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Prepare data for filtering
     * @return mixed
     */
    abstract protected function prepare();
}
