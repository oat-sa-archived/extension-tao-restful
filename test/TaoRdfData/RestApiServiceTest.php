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

namespace oat\taoRestAPI\test\v1\TaoRdfData;


use oat\generis\model\data\ModelManager;
use oat\generis\model\kernel\persistence\file\FileIterator;
use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\model\v1\http\Request\RouterAdapter\SlimRouterAdapter;
use oat\taoRestAPI\service\v1\RestApiService;
use oat\taoRestAPI\test\TaoRdfData\samples\RdfService;
use oat\taoRestAPI\test\TaoRdfData\samples\TestRdfStorageAdapter;
use oat\taoRestAPI\test\v1\Mocks\Response;
use oat\taoRestAPI\test\v1\RestApiUnitTestRunner;
use Slim\Http\Environment;
use Slim\Http\Request;
use tao_actions_form_Generis;
use tao_models_classes_ClassService;

class RestApiServiceTest extends RestApiUnitTestRunner
{
    
    private static $RDF = 'ResourcesTestSamples.rdf';

    /**
     * Resources service
     * @var tao_models_classes_ClassService
     */
    private $rdfService;

    /**
     * @var RestApiService
     */
    private $service;

    /**
     * for some unknown reason, I can not test returned content
     * because for new testrun uses new data from rdf, but order from sql fetch data always different
     * 
     * @throws \common_exception_InconsistentData
     */
    public function setUp()
    {
        parent::setUp();
        $this->rdfService = new RdfService();
        $this->service = new RestApiService();

        $rdfSamples = self::getSamplePath(self::$RDF);
        $iterator = new FileIterator($rdfSamples);
        $rdf = ModelManager::getModel()->getRdfInterface();
        /** @var \core_kernel_classes_Triple $triple */
        foreach ($iterator as $triple) {
            //make sure that the ontology is clear to avoid errors if triple is in multiple time
            $rdf->remove($triple);
            $rdf->add($triple);
        }
    }

    /**
     * for test with dependencies
     * @var bool
     */
    private $allowResource=false;
    
    public function tearDown()
    {
        $rdfSamples = self::getSamplePath(self::$RDF);
        $iterator = new FileIterator($rdfSamples);
        $rdf = ModelManager::getModel()->getRdfInterface();
        foreach ($iterator as $triple) {
            $rdf->remove($triple);
        }

        if (!$this->allowResource){
            /** @var  $resource \core_kernel_classes_Resource */
            foreach ($this->rdfService->getRootClass()->searchInstances([RDFS_LABEL => '%PHPUNIT_Resource_%'], ['like' => true]) as $resource) {
                $resource->delete(true);
            }
        }
        $this->allowResource = false;
        
        parent::tearDown();
    }

    public function testResources()
    {
        $resources = $this->rdfService->getRootClass()->searchInstances([RDFS_LABEL => '%PHPUNIT_Resource_%'], ['like' => true]);
        $this->assertEquals(10, count($resources));

        $result = [
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource1',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource10',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource2',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource3',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource4',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource5',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource6',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource7',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource8',
            'http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource9',
        ];
        sort($result);
        $resourcesKey = array_keys($resources);
        sort($resourcesKey);
        $this->assertEquals($result, $resourcesKey);
    }
        
    public function testGetList()
    {
        $this->request('GET', '/resources', function (Request $req, Response $res) {

            try {

                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-9/10'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
    }

    public function testGetListRange()
    {
        $this->request('GET', '/resources', '/resources?range=0-0', function (Request $req, Response $res) {

            try {

                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(206, $this->response->getStatusCode());
        $this->assertEquals('Partial Content', $this->response->getReasonPhrase());
        $this->assertEquals(['0-0/10'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
    }

    public function testGetListFilters()
    {
        $this->request('GET', '/resources', '/resources?'
                . urlencode('http://www.w3.org/1999/02/22-rdf-syntax-ns#type') 
                    . '=' . urlencode('http://www.tao.lu/Ontologies/TAO.rdf#TAOObject').
                '&' . urlencode('http://www.w3.org/2000/01/rdf-schema#label') . '=' . urlencode('PHPUNIT_Resource_1')
            , function (Request $req, Response $res) {

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-0/1'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource1","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_1"}]}]}]', (string)$this->response->getBody());
    }

    public function testGetListSort()
    {
        $this->request('GET', '/resources', '/resources?sort=http://www.w3.org/2000/01/rdf-schema#label', function (Request $req, Response $res) {

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-9/10'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource1","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_1"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource10","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_10"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource2","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_2"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource3","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_3"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource4","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_4"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource5","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_5"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource6","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_6"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource7","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_7"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource8","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_8"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource9","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_9"}]}]}]', (string)$this->response->getBody());
    }

    public function testGetListSortDesc()
    {
        $this->request('GET', '/resources', '/resources?sort=http://www.w3.org/2000/01/rdf-schema#label&desc=http://www.w3.org/2000/01/rdf-schema#label', function (Request $req, Response $res) {

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-9/10'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource9","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_9"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource8","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_8"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource7","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_7"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource6","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_6"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource5","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_5"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource4","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_4"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource3","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_3"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource2","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_2"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource10","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_10"}]}]},{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource1","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_1"}]}]}]', (string)$this->response->getBody());
    }

    public function testGetListFiltersPartial()
    {
        $this->request('GET', '/resources', '/resources?http://www.w3.org/1999/02/22-rdf-syntax-ns#type=http://www.tao.lu/Ontologies/TAO.rdf#TAOObject&http://www.w3.org/2000/01/rdf-schema#label=PHPUNIT_Resource_1&fields=http://www.w3.org/1999/02/22-rdf-syntax-ns#type', function (Request $req, Response $res) {

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals(['0-0/1'], $this->response->getHeader('Content-Range'));
        $this->assertEquals(['resource 50'], $this->response->getHeader('Accept-Range'));
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource1","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]}]}]', (string)$this->response->getBody());
    }
    
    public function testGetOne()
    {
        $this->request('GET', '/resources', '/resources?id=http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource7', function (Request $req, Response $res) {

            $req = $req->withAttribute('id', $req->getParam('id'));
                
            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource7","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]},{"predicateUri":"http:\/\/www.w3.org\/2000\/01\/rdf-schema#label","values":[{"valueType":"literal","value":"PHPUNIT_Resource_7"}]}]}]', (string)$this->response->getBody());
    }

    public function testGetOnePartial()
    {
        $this->request('GET', '/resources', '/resources?id=http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource7&fields=http://www.w3.org/1999/02/22-rdf-syntax-ns#type', function (Request $req, Response $res) {
            
            $req = $req->withAttribute('id', $req->getParam('id'));

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals('[{"uri":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#RestApi_testResource7","properties":[{"predicateUri":"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#type","values":[{"valueType":"resource","value":"http:\/\/www.tao.lu\/Ontologies\/TAO.rdf#TAOObject"}]}]}]', (string)$this->response->getBody());
    }
    
    /**
     * @return string
     */
    public function testPostIncorrectType()
    {
        // replace default post to post with data
        $_POST = [
            RDF_TYPE => 'Type',
            RDFS_LABEL => 'PHPUNIT_Resource_1010',
        ];

        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources',
            'REQUEST_METHOD' => 'POST',
            'HTTP_CONTENT_TYPE' => 'multipart/form-data;'
        ]);

        $request = Request::createFromEnvironment($env);
        unset($_POST);

        $response = new Response();

        $this->routerRunner($request, $response);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Bad Request', $response->getReasonPhrase());
        $this->assertEquals('{"errors":["Incorrect type of the resource"]}', (string)$response->getBody());
    }

    public function testPostWithoutBody()
    {

        $this->request('POST', '/resources', function (Request $req, Response $res) {

            $req = $req->withAttribute('id', $req->getParam('id'));

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(201, $this->response->getStatusCode());
        $this->assertEquals('Created', $this->response->getReasonPhrase());
        $body = json_decode((string)$this->response->getBody());
        
        $resource = new \core_kernel_classes_Resource($body[0]->uri);
        $resource->delete(true);
    }    
    
    /**
     * @return string
     */
    public function testPost()
    {
        // replace default post to post with data
        $_POST = [
            RDF_TYPE => tao_actions_form_Generis::DEFAULT_TOP_CLASS,
            RDFS_LABEL => 'PHPUNIT_Resource_1010',
        ];

        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources',
            'REQUEST_METHOD' => 'POST',
            'HTTP_CONTENT_TYPE' => 'multipart/form-data;'
        ]);

        $request = Request::createFromEnvironment($env);
        unset($_POST);

        $response = new Response();

        $this->routerRunner($request, $response);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Created', $response->getReasonPhrase());
        $this->assertNotFalse(strpos((string)$response->getBody(), 'PHPUNIT_Resource_1010'));
        $this->assertNotFalse(strpos(current($response->getHeader('Location')), '#i'));
        $this->assertInstanceOf('StdClass', $response->getResourceData());

        $this->allowResource = true;
        return $response->getResourceData()->uri;
    }

    /**
     * @param $resourceUri
     * @depends testPost
     */
    public function testPut($resourceUri)
    {
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources?id=' .urlencode($resourceUri),
            'REQUEST_METHOD' => 'PUT',
        ]);

        $request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $request = $request->withAttribute('id', $resourceUri);
        $putData = [
            RDF_TYPE => tao_actions_form_Generis::DEFAULT_TOP_CLASS, // should be default for rdf storage
            RDFS_LABEL => 'PHPUNIT_Resource_2020',
            RDFS_COMMENT => 'new parameter',
        ];
        $request = $request->withParsedBody($putData);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = new Response();

        $this->routerRunner($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertNotFalse(strpos((string)$response->getBody(), 'PHPUNIT_Resource_2020'));

        $this->allowResource=true;
    }

    /**
     * @param $resourceUri
     * @depends testPost
     */
    public function testPatch($resourceUri)
    {

        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/resources?id=' . $resourceUri,
            'REQUEST_METHOD' => 'PATCH',
        ]);

        $request = Request::createFromEnvironment($env);
        unset($_POST);

        // add Attribute in request
        $request = $request->withAttribute('id', $resourceUri);
        $patchData = [
            RDFS_LABEL => 'PHPUNIT_Resource_3030',
        ];
        $request = $request->withParsedBody($patchData);
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $response = new Response();

        $this->routerRunner($request, $response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertNotFalse(strpos((string)$response->getBody(), 'PHPUNIT_Resource_3030'));

        $this->allowResource=true;
    }

    /**
     * @param $resourceUri
     * @depends testPost
     */
    public function testDelete($resourceUri)
    {
        $this->request('DELETE', '/resources', '/resources?id=' . $resourceUri, function (Request $req, Response $res, $args) {

            $req = $req->withAttribute('id', $req->getParam('id'));

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertFalse((new \core_kernel_classes_Resource($resourceUri))->exists());
    }

    public function testHttpListResourceOptions()
    {
        $this->request('OPTIONS', '/resources', '/resources?id=http://www.tao.lu/Ontologies/TAO.rdf#RestApi_testResource7', function (Request $req, Response $res) {

            $req = $req->withAttribute('id', $req->getParam('id'));

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals('["GET","PUT","PATCH","DELETE","OPTIONS"]', (string)$this->response->getBody());
    }

    public function testHttpResourceOptions()
    {
        $this->request('OPTIONS', '/resources', function (Request $req, Response $res) {

            try {
                $this->service
                    ->setRouter($this->getRouter())
                    ->execute(function ($router, $encoder) use ($req, &$res) {
                        $this->runRouterTest($router, $encoder, $req, $res);
                    });
            } catch (RestApiException $e) {
                $res = $res->withStatus($e->getCode());
                $res = $res->withJson(['errors' => [$e->getMessage()]]);
            }

            return $this->response = $res;
        });

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->assertEquals('OK', $this->response->getReasonPhrase());
        $this->assertEquals('["POST","GET","OPTIONS"]', (string)$this->response->getBody());
    }
    
    /**
     * @param $fileName
     * @return string
     */
    private static function getSamplePath( $fileName )
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'samples' . DIRECTORY_SEPARATOR . $fileName;
    }

    protected function getRouter()
    {
        return new SlimRouterAdapter(new TestRdfStorageAdapter());
    }
}
