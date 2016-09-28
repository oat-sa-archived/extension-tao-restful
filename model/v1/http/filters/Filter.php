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

namespace oat\taoRestAPI\model\v1\http\filters;


use oat\taoRestAPI\model\v1\http\AbstractFilter;

/**
 * Class Filter
 * Filtering by field values
 * 
 * Response
 * GET ?field1=value1&amp;field2=value1,value3
 * 
 * @package oat\taoRestAPI\model\http\Response
 */
class Filter extends AbstractFilter
{

    /**
     * @var array
     */
    protected $options = [
        'query' => [],
        'fields' => [],
    ];

    private $filters = [];
    
    public function getFilters()
    {
        return $this->filters;
    }
    
    protected function prepare()
    {
        if (is_array($this->options['query'])) {
            foreach ($this->options['query'] as $field => $filter) {
                if (in_array($field, $this->options['fields'])) {
                    $this->filters[$field] = explode(',', $filter);
                }
            }
        }
    }
}
