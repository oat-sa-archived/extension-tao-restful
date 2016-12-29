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

class Sort extends AbstractFilter
{

    /**
     * Name in query string
     * @var array
     */
    private $allowSort = ['sort', 'desc'];

    /**
     * Fields for sorting
     *
     * # sort[ 'sort' => ['field1', 'field2'], 'desc' => ['field3']]
     * #
     * @var array
     */
    private $sort = [];

    public function getSorting()
    {
        return $this->sort;
    }

    protected function prepare()
    {
        if (is_array($this->options['query'])) {
            foreach ($this->options['query'] as $field => $value) {
                if (in_array($field, $this->allowSort)) {
                    $this->sort[$field] = explode(',', $value);
                }
            }
        }
        
        $this->cleanSorting();
    }
    
    private function cleanSorting()
    {
        if (count($this->sort)) {
            
            // unique in sort
            foreach ($this->sort as $key => $val) {
                $this->sort[$key] = array_unique($val);
            }
            
            // allow to DESC sort
            if (count($this->sort) == 2) {
                $this->sort['desc'] = array_intersect($this->sort['desc'], $this->sort['sort']); 
            }
        }
    }
}
