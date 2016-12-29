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

class ArrayStorageAdapter extends AbstractStorageAdapter
{
    protected $resourcesData = [];
    
    public function getFields()
    {
        return array_keys($this->searchInstances()[0]);
    }
    
    public function getOne($id, array $partialFields)
    {
        $resource = [];
        foreach ($this->searchInstances() as $resource) {
            if ($resource['id'] == $id) {
                break;
            }
        }

        $this->selectFields($resource, $partialFields);

        return $resource;
    }

    /**
     *
     * // use filter by values
     * 'filters' => $filter->getFilters(),
     *
     * // columns
     * 'fields' => $partial->getFields(),
     *
     * // sort
     * 'sortBy' => $sort->getSorting(),
     *
     * // pagination
     * 'offset' => $paginate->offset(),
     * 'limit' => $paginate->length(),
     *
     *
     * @param array $params
     * @return array|mixed
     */
    public function searchInstances(array $params = null)
    {
        $data = $this->resourcesData;

        // filters
        // fields with and, values with or
        $filteredData = [];
        if (isset($params['filters']) && count($params['filters'])) {
            foreach ($data as $key => $row) {
                foreach ($params['filters'] as $field => $filters) {
                    if (isset($row[$field]) && in_array($row[$field], $filters)) {
                        $filteredData[$key] = $row;
                    } else {
                        if (isset($filteredData[$key])) {
                            unset($filteredData[$key]);
                        }
                        continue;
                    }
                }
            }
            $data = $filteredData;
        }

        // sort
        if (isset($params['sortBy']) && count($params['sortBy'])) {
            $sorting = [];

            if (!isset($params['sortBy']['desc'])) {
                $params['sortBy']['desc'] = [];
            }

            if (!isset($params['sortBy']['sort'])) {
                $params['sortBy']['sort'] = [];
            }

            foreach ($params['sortBy']['sort'] as $field) {
                $column = [];
                foreach ($data as $key => $row) {
                    $column[$key] = $row[$field];
                }

                $sorting[] = $column;

                if (in_array($field, $params['sortBy']['desc'])) {
                    $sorting[] = SORT_DESC;
                } else {
                    $sorting[] = SORT_ASC;
                }
            }

            $sorting[] = &$data;
            call_user_func_array('array_multisort', $sorting);
            $data = array_pop($sorting);
        }

        // pagination
        if (isset($params['offset']) && isset($params['limit'])) {
            $data = array_slice($data, $params['offset'], $params['limit']);
        }

        // fields
        if (isset($params['fields']) && count($params['fields'])) {

            foreach ($data as $k => &$row) {
                $this->selectFields($row, $params['fields']);
            }
        }

        return $data;
    }

    public function isAllowedDefaultResources()
    {
        return false;
    }
    
    protected function create()
    {
        $propertiesValues = $this->getPropertiesValues();
        
        if (!isset($propertiesValues['id'])) {
            throw new RestApiException(__('Resource parameter "id" is required'), 400);
        }
        
        if ($this->exists($propertiesValues['id'])) {
            throw new RestApiException(__('Resource with id=%s already exists', $propertiesValues['id']), 400);
        }
        
        //creating
        $this->resourcesData[] = $propertiesValues;
        
        return current(array_slice($this->resourcesData, -1, 1))['id'];
    }

    // replace all data
    protected function replace($id)
    {
        $propertiesValues = $this->getPropertiesValues();

        if (!isset($propertiesValues['id']) || $id != $propertiesValues['id']) {
            throw new RestApiException(__('You can not change id of the resource'), 400);
        }
        
        $this->resourcesData[$this->findIdKey($id)] = $propertiesValues;
    }

    // change only properties
    protected function edit($id)
    {
        $propertiesValues = $this->getPropertiesValues();

        if (isset($propertiesValues['id']) && $id != $propertiesValues['id']) {
            throw new RestApiException(__('You can not change id of the resource'), 400);
        }
        
        $key = $this->findIdKey($id);
        foreach ($propertiesValues as $pKey => $propertyValue) {
            $this->resourcesData[$key][$pKey] = $propertyValue;
        }
    }
    
    public function delete($id)
    {
        if ($key = $this->findIdKey($id) !== false) {
            unset($this->resourcesData[$key]);
        }
    }
    
    public function exists($id)
    {
        $ids = [];
        foreach ($this->searchInstances() as $row) {
            $ids[] = $row['id'];
        }
        if (in_array($id, $ids)) {
            return true;
        }
        
        return false;
    }

    /**
     * @param $resource
     * @param array $partialFields
     */
    private function selectFields(&$resource, array $partialFields)
    {
        foreach ($resource as $key => $value) {
            if (!in_array($key, $partialFields)) {
                unset($resource[$key]);
            }
        }
    }
    
    private function findIdKey($id)
    {
        foreach ($this->searchInstances() as $key => $resource) {
            if ($resource['id'] == $id) {
                return $key;
            }
        }
        
        return false;
    }
}
