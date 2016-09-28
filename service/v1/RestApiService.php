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

namespace oat\taoRestAPI\service\v1;


use oat\oatbox\service\ConfigurableService;
use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\model\AuthenticationInterface;
use oat\taoRestAPI\model\DataEncoderInterface;
use oat\taoRestAPI\model\HttpDataFormatInterface;
use oat\taoRestAPI\model\HttpRouterInterface;
use oat\taoRestAPI\model\v1\dataEncoder\JsonEncoder;
use oat\taoRestAPI\service\RestApiInterface;

/**
 * Class RestApiServiceV1
 * @package oat\taoRestAPI\model\restApi
 */
class RestApiService extends ConfigurableService implements RestApiInterface
{

    /**
     * @var AuthenticationInterface
     */
    private $authenticator;

    /**
     * @var HttpRouterInterface
     */
    private $router;

    /**
     * @var DataEncoderInterface
     */
    private $encoder;
    
    public function execute($callable)
    {

        if (isset($this->authenticator)) {
            $this->authenticator->authenticate();
        }
        
        if (!is_callable($callable)) {
            throw new RestApiException('$callable must be a closure function', 500);
        }
        
        if (!isset($this->router)) {
            throw new RestApiException('HttpRouter is not set', 500);
        }
        
        if (!isset($this->encoder)) {
            $this->encoder = new JsonEncoder();
        }
        
        $callable($this->router, $this->encoder);
    }
    
    public function setAuth(AuthenticationInterface $auth)
    {
        $this->authenticator = $auth;
        return $this;
    }
    
    public function setRouter(HttpRouterInterface $router)
    {
        $this->router = $router;
        return $this;
    }
    
    public function setEncoder(HttpDataFormatInterface $dataFormat) 
    {
        $this->encoder = $dataFormat->encoder();
        return $this;
    }
}
