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

namespace oat\taoRestAPI\model\v1\StorageAdapter;


use oat\taoRestAPI\exception\RestApiException;
use oat\taoRestAPI\model\DataStorageInterface;

abstract class AbstractStorageAdapter implements DataStorageInterface
{

    /**
     * Flag - if storage can create|update resources with default values
     * (i.e. we can send post request without any post data, and as result we'll have resource with default fields)
     * 
     * @var bool
     */
    protected $allowedDefaultResources = false;

    /**
     * Properties of the RestApi resource model
     * 
     * @var array
     */
    private $propertiesValues=[];

    public function isAllowedDefaultResources() {
        return $this->allowedDefaultResources;
    }

    /**
     * @param array|null $propertiesValues
     */
    protected function appendPropertiesValues(array $propertiesValues = null)
    {
        if (is_array($propertiesValues)) {
            $this->propertiesValues = array_merge($this->getPropertiesValues(), $propertiesValues);
        }
    }

    /**
     * @return array
     */
    protected function getPropertiesValues()
    {
        return $this->propertiesValues;
    }

    /**
     * @param string $key
     */
    protected function unsetPropertiesValue($key = '')
    {
        if (key_exists($key, $this->propertiesValues)) {
            unset($this->propertiesValues[$key]);
        }
    }
    
    public function post(array $propertiesValues = null)
    {
        $this->appendPropertiesValues($propertiesValues);
        return $this->create();
    }

    public function put($id, array $propertiesValues)
    {
        $this->appendPropertiesValues($propertiesValues);
        $this->throwIfResourceNotExists($id);
        return $this->replace($id);
    }
    
    public function patch($id, array $propertiesValues)
    {
        // for patch don't use default properties, only updating for requested
        $this->propertiesValues = [];
        
        $this->appendPropertiesValues($propertiesValues);
        $this->throwIfResourceNotExists($id);
        return $this->edit($id);
    }

    /**
     * Create new resource
     * 
     * @return mixed
     */
    abstract protected function create();

    /**
     * Replace resource
     *
     * @param $id
     * @return mixed
     */
    abstract protected function replace($id);

    /**
     * Edit resource properties
     * 
     * @param $id
     * @return mixed
     */
    abstract protected function edit($id);

    /**
     * Check if resource exists
     * 
     * @param $id
     * @return mixed
     */
    abstract public function exists($id);
    
    /**
     * @param $id
     * @throws RestApiException
     */
    private function throwIfResourceNotExists($id)
    {
        if (!$this->exists($id)) {
            throw new RestApiException(__('Resource not found'), 404);
        }
    }
}
