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

namespace oat\taoRestAPI\model;


interface DataStorageInterface
{
    /**
     * List of the model fields
     * 
     * @return array
     */
    public function getFields();

    /**
     * One resource
     *
     * @param $id
     * @param array $partialFields
     * @return array
     */
    public function getOne($id, array $partialFields);
    
    /**
     * Search instances
     * 
     * @param array $params
     * @return array
     */
    public function searchInstances(array $params = null);

    /**
     * @param array $propertiesValues
     * @return mixed
     */
    public function post(array $propertiesValues=null);

    /**
     * Replace all resource data
     * @param $id
     * @param array $propertiesValues
     * @return mixed
     */
    public function put($id, array $propertiesValues);

    /**
     * Update only pointed fields
     * 
     * @param $id
     * @param array $propertiesValues
     * @return mixed
     */
    public function patch($id, array $propertiesValues);

    /**
     * @param $id
     * @return string resource identifier
     */
    public function delete($id);
    
    /**
     * Is resource with identifier exists
     * 
     * @param $id
     * @return mixed
     */
    public function exists($id);

    /**
     * Flag - if storage can create new resources with default values
     * (i.e. we can send post request without any post data, and as result we'll have resource with default fields)
     *
     * @return bool
     */
    public function isAllowedDefaultResources();
}
