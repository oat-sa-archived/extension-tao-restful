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


use oat\taoRestAPI\exception\HttpRequestException;
use oat\taoRestAPI\exception\HttpRequestExceptionWithHeaders;
use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\model\DataStorageInterface;
use oat\taoRestAPI\model\RouterAdapterInterface;
use oat\taoRestAPI\model\v1\http\filters\Filter;
use oat\taoRestAPI\model\v1\http\filters\Paginate;
use oat\taoRestAPI\model\v1\http\filters\Partial;
use oat\taoRestAPI\model\v1\http\filters\Sort;
use oat\taoRestAPI\model\v1\http\Request\Router;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractRouterAdapter extends Router implements RouterAdapterInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $req;


    /**
     * @var DataStorageInterface
     */
    private $storage;

    /**
     * Response status code for header()
     * @var int
     */
    private $httpStatusCode = 200;

    /**
     * Response Body Data
     * @var
     */
    protected $bodyData;

    /**
     * Response headers for header()
     * @var array
     */
    private $httpHeaders = [];

    /**
     * @var array of the query parameters
     */
    protected $queryParams = null;

    public function __construct(DataStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Rest API auto runner
     *
     * # Defines and runs the necessary methods for current Http header _method
     *
     * ## for dev test with slim, can compile http responses with correct status codes
     *
     * @param $req
     * @param array $idRule
     * @throws HttpRequestException
     */
    public function __invoke($req = null, array $idRule)
    {
        $this->req = $req;
        $this->runApiCommand($this->req->getMethod(), $this->getId($idRule));
    }

    private function getId(array $idRule)
    {
        $id = null;

        if (!isset($idRule['type']) || !isset($idRule['key']) || !in_array($idRule['type'], ['get', 'param'])) {
            throw new HttpRequestException('Incorrect definition of the rule for identifier in RestApi configuration file', 500);
        }


        switch ($idRule['type']) {
            case 'get':
                $params = $this->getQueryParams();
                if (isset($params[$idRule['key']])) {
                    $id = $params[$idRule['key']];
                }
                break;
            case 'param':
                $id = $this->getAttribute($idRule['key']);
                break;
            //case 'post' ...
        }
        
        return $id;
    }

    /**
     * @param string $key
     * @return string
     */
    abstract protected function getAttribute($key = '');

    /**
     * @return DataStorageInterface
     */
    public function storage()
    {
        return $this->storage;
    }

    public function getHeaders()
    {
        return $this->httpHeaders;
    }

    public function getStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function getBodyData()
    {
        return $this->bodyData;
    }

    protected function addHeaders(array $addHeaders)
    {
        if (count($addHeaders)) {
            foreach ($addHeaders as $name => $headers) {
                $this->httpHeaders[$name] = $headers;
            }
        }
    }

    protected function setStatusCode($status = 0)
    {
        $this->httpStatusCode = intval($status);
    }

    /**
     * @return array of the requests params
     */
    abstract protected function getQueryParams();

    protected function getList()
    {
        $queryParams = $this->getQueryParams();

        $filter = new Filter([
            'query' => $this->guessFields($queryParams, $quiet = true),
            'fields' => $this->storage()->getFields(),
        ]);

        try {
            $paginate = new Paginate([
                'query' => isset($queryParams['range']) ? $queryParams['range'] : '',
                'total' => count($this->storage()->searchInstances()),
                'paginationUrl' => $this->getUri() . '?range=',
            ]);
        } catch (HttpRequestExceptionWithHeaders $e) {
            // add failed headers if exists
            $this->addHeaders($e->getHeaders());
            throw new HttpRequestException($e->getMessage(), $e->getCode());
        }

        $partial = new Partial([
            'query' => isset($queryParams['fields']) ? $queryParams['fields'] : '',
            'fields' => $this->storage()->getFields(),
        ]);

        $sort = new Sort(['query' => $queryParams]);

        $this->bodyData = $this->storage()->searchInstances([

            // use filter by values
            'filters' => $filter->getFilters(),

            // columns
            'fields' => $partial->getFields(),

            // sort
            'sortBy' => $sort->getSorting(),

            // pagination
            'offset' => $paginate->offset(),
            'limit' => $paginate->length(),
        ]);

        $beforePaginationCount = count($this->storage()->searchInstances(['filters' => $filter->getFilters()]));

        $paginate->correctPaginationHeader(count($this->bodyData), $beforePaginationCount);

        if ($this->getStatusCode() == 200 && $paginate->getStatusCode()) {
            $this->setStatusCode($paginate->getStatusCode());
        }

        $this->addHeaders($paginate->getHeaders());
    }

    protected function getOne()
    {
        $queryParams = $this->getQueryParams();
        $partialFields = isset($queryParams['fields']) ? $queryParams['fields'] : '';

        $partial = new Partial([
            'query' => $partialFields,
            'fields' => $this->storage()->getFields(),
        ]);

        $this->bodyData = $this->storage()->getOne($this->getResourceId(), $partial->getFields());
    }

    public function post()
    {
        parent::post();

        $id = $this->storage()->post($this->getResourceData(false));

        // return new resource
        $this->bodyData = $this->storage()->getOne($id, $this->storage()->getFields());
        $this->setStatusCode(201);
        $this->addHeaders(['Location' => $this->getResourceUrl($id)]);
    }

    public function put()
    {
        parent::put();
        $this->storage()->put($this->getResourceId(), $this->getResourceData(true));
        $this->bodyData = $this->storage()->getOne($this->getResourceId(), $this->storage()->getFields());
    }

    public function patch()
    {
        parent::patch();
        $this->storage()->patch($this->getResourceId(), $this->getResourceData(true));
        $this->bodyData = $this->storage()->getOne($this->getResourceId(), $this->storage()->getFields());
    }

    public function delete()
    {
        parent::delete();
        $this->storage()->delete($this->getResourceId());
    }

    public function options()
    {
        $this->bodyData = parent::options();
    }

    /**
     * Get parsed request body (for put, patch, post requests)
     * for validation can be used $this->getResourceData
     *
     * @return mixed
     */
    abstract protected function getParsedBody();

    /**
     * Resource address
     *
     * @param null $id
     * @return mixed|null
     * @throws RestApiException
     */
    protected function getResourceUrl($id = null)
    {

        if ($id && $this->storage()->exists($id)) {
            // use id
        } elseif ($this->getResourceId() && $this->storage()->exists($this->getResourceId())) {
            $id = $this->getResourceId();
        } else {
            throw new RestApiException('Undefined resource identifier', 400);
        }

        return $id;
    }

    /**
     * Uri for the current RestApi context
     * @return mixed
     */
    abstract protected function getUri();

    /**
     * Get requested data with validation
     *
     * @param bool $required
     * @return mixed
     * @throws HttpRequestException
     */
    private function getResourceData($required = true)
    {
        $resourceData = $this->guessFields($this->getParsedBody());

        if ($required && !$this->storage()->isAllowedDefaultResources() && !$resourceData) {
            throw new HttpRequestException('Empty Request data.', 400);
        }

        return $resourceData;
    }

    /**
     * All key from uri change dots on _
     * example: "?my.var1=val" => ['my_var1' => 'val'], i.e we can't control all options
     * so try to guess
     *
     * @param array|null $parameters
     * @param bool $quiet - Don't write to log warnings (when looking for fields in query request (Filter))
     * @return array|null
     */
    private function guessFields(array $parameters = null, $quiet = false)
    {
        $resultParams = null;
        if (isset($parameters) && count($parameters)) {
            $resultParams = [];

            $guessFields = [];
            foreach ($this->storage()->getFields() as $key => $fieldName) {
                $guessFields[$key] = $this->convertFieldName($fieldName);
            }

            foreach ($parameters as $parameterName => $value) {
                if (in_array($parameterName, $this->storage()->getFields())) {
                    $resultParams[$parameterName] = $value;
                } else {
                    $guessField = $this->convertFieldName($parameterName);
                    if (in_array($guessField, $guessFields)) {
                        $pos = array_search($guessField, $guessFields);
                        $resultParams[$this->storage()->getFields()[$pos]] = $value;
                    } elseif (!$quiet) {
                        \common_Logger::w('RestApi can not find model property ' . $parameterName . ' in fields. Storage ' . get_class($this->storage()));
                    }
                }
            }
        }
        return $resultParams;
    }

    private function convertFieldName($name = '')
    {
        return str_replace('_', '.', $name);
    }
}
