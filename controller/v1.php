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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 * @author A. Zagovorichev <zagovorichev@1pt.com>
 */

namespace oat\taoRestAPI\controller;


use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\helpers\Response;
use oat\taoRestAPI\model\v1\http\Request\DataFormat;
use oat\taoRestAPI\model\v1\http\Request\RouterAdapter\SlimRouterAdapter;
use oat\taoRestAPI\proxy\BasicAuthentication;
use oat\taoRestAPI\service\docs\DocsService;
use oat\taoRestAPI\service\v1\RestApiService;
use oat\taoRestAPI\test\v1\Mocks\DB;
use Slim\Http\Environment;
use Slim\Http\Request;
use tao_actions_CommonModule;

/**
 * @author Open Assessment Technologies SA
 * @package taoRestAPI v1
 * @license GPL-2.0
 *
 */
class v1 extends tao_actions_CommonModule
{

    const CONFIG_NAME = 'restApi';

    /**
     * @var RestApiService
     */
    protected $service;

    /**
     * initialize the services
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getServiceManager()->get(RestApiService::SERVICE_ID);
    }

    /**
     * A possible entry point to tao
     */
    public function resources()
    {

        try {
            $this->service
                ->setEncoder(new DataFormat())
                ->setRouter(new SlimRouterAdapter(new DB()))
                ->setAuth(new BasicAuthentication())
                ->execute(function ($router, $encoder) {

                    $request = Request::createFromEnvironment(new Environment($_SERVER));
                    $router(
                        $request,
                        $request->getQueryParam('uri')
                    );


                    Response::write(
                        $router->getStatusCode(),
                        $encoder->getContentType(),
                        $router->getHeaders(),
                        $encoder->encode($router->getBodyData())
                    );
                });
        } catch (RestApiException $e) {
            Response::write($e->getCode(), 'text/plain', [], $e->getMessage());
        }
    }

    /**
     * System of rules or standard that defines the syntax, semantics and synchronization
     * of communication and possible error recovery methods
     *
     * array $config
     *      [
     *          // Auth method for access to RestApi protocol
     *          'authenticator' => '@see \oat\taoRestAPI\model\AuthenticationInterface',
     *
     *          // Default definer for the format of the data
     *          'encoder' => '@see \oat\taoRestAPI\model\HttpDataFormatInterface',
     *
     *          // Adapter for requested data from different frameworks (Slim, ClearFw)
     *          'routerAdapter' => '@see \oat\taoRestAPI\model\v1\http\Request\RouterAdapter',
     *
     *          // Adapter for data access (Array, Qti, Rdf ... types of the data storage)
     *          'storageAdapter' => '@see \oat\taoRestAPI\model\DataStorageInterface',
     *
     *          // Service which implements access to storage data
     *          // @optional
     *          'storageService' => '@see \tao_models_classes_ClassService',
     *      ]
     */
    public function protocol()
    {
        $parts = explode('/', $this->getRequest()->getRequestURI());

        $extensionId = $parts[1];
        $extension = \common_ext_ExtensionsManager::singleton()->getExtensionById($extensionId);
        $config = $extension->getConfig(self::CONFIG_NAME);

        try {

            if (isset($config['storageService'])) {
                // if str pos :: then singleton else should use service manager 
                if (is_array($config['storageService'])) {
                    $storageService = call_user_func($config['storageService']);
                } else {
                    $storageService = new $config['storageService'];
                }
                $storageAdapter = new $config['storageAdapter']( $storageService );
            } else {
                $storageAdapter = new $config['storageAdapter'];
            }

            if (isset($config['encoder'])) {
                $this->service->setEncoder(new $config['encoder']);
            }
            
            $this->service->setRouter(new $config['routerAdapter']($storageAdapter));

            if (isset($config['authenticator'])) {
                $this->service->setAuth(new $config['authenticator']);
            }
            
            $this->service->execute(function ($router, $encoder) {

                $request = Request::createFromEnvironment(new Environment($_SERVER));
                $router (
                    $request,
                    $request->getQueryParam('uri')
                );

                Response::write(
                    $router->getStatusCode(),
                    $encoder->getContentType(),
                    $router->getHeaders(),
                    $encoder->encode($router->getBodyData())
                );
            });
        } catch (RestApiException $e) {
            Response::write($e->getCode(), 'text/plain', [], $e->getMessage());
        }
    }
}
