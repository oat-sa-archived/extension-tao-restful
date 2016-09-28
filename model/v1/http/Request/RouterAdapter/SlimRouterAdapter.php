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

namespace oat\taoRestAPI\model\v1\http\Request\RouterAdapter;


/**
 * Adapter for Slim framework
 * Class SlimRouterAdapter
 * @package oat\taoRestAPI\model\v1\http\Request\RouterAdapter
 */
class SlimRouterAdapter extends AbstractRouterAdapter
{
    
    protected function getAttribute($key = '')
    {
        return $this->req->getAttribute($key);
    }

    /**
     * Get params from Get request
     */
    protected function getQueryParams()
    {
        if (!isset($this->queryParams)){
            $this->queryParams = [];
            foreach ($this->req->getQueryParams() as $key => $param) {
                $this->queryParams[urldecode($key)] = urldecode($param);
            }
        }
        
        return $this->queryParams;
    }
    
    protected function getUri()
    {
        $uri = (string)$this->req->getUri();
        if (strpos($uri, '?') != false) {
            $uri = mb_strcut($uri, 0, mb_strpos($uri, '?'));
        }
        return $uri;
    }
    
    protected function getResourceUrl($id=null)
    {
        return  $this->getUri(). '/' . parent::getResourceUrl($id);
    }
    
    protected function getParsedBody()
    {
        return $this->req->getParsedBody();
    }
}
