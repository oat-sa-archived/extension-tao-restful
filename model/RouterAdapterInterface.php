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


interface RouterAdapterInterface
{

    /**
     * Defined with data storage
     * 
     * RouterAdapterInterface constructor.
     * @param DataStorageInterface $storage
     */
    public function __construct(DataStorageInterface $storage);

    /**
     * Get data storage
     * # income data
     * 
     * @return DataStorageInterface
     */
    public function storage();

    /**
     * Get headers for http response
     * #results
     * 
     * @return mixed
     */
    public function getHeaders();

    /**
     * Get Status code of the Route operation
     * #results
     * 
     * @return mixed
     */
    public function getStatusCode();

    /**
     * Data for answer
     * #results
     * 
     * @return mixed
     */
    public function getBodyData();

}
