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
 * Class Partial
 * Allow client to retrieve only the information they need
 *  options['query'] = 'type,title'
 *
 * @package oat\taoRestAPI\model\http\Response
 */
class Partial extends AbstractFilter
{

    protected $options = [
        'query' => '',
        'fields' => [],
    ];

    private $fields = [];

    public function getFields()
    {
        return $this->fields;
    }

    protected function prepare()
    {
        if (!empty($this->options['query'])) {
            foreach (explode(',', $this->options['query']) as $field) {
                if (in_array($field, $this->options['fields'])) {
                    $this->fields[] = $field;
                }
            }
        }
        
        if (empty($this->fields)) {
            $this->fields = $this->options['fields'];
        }
    }
}
