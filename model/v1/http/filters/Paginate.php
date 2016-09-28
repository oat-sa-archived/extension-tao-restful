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


use oat\taoRestAPI\exception\HttpRequestException;
use oat\taoRestAPI\exception\HttpRequestExceptionWithHeaders;
use oat\taoRestAPI\model\v1\http\AbstractFilter;

/**
 * Class Paginate
 * Paging in Http request
 *
 * Request
 * GET param like ?range=0-3
 *
 * Response
 * Allow partial content, with possible HTTP responses:
 * # 200 Ok - All resources data fit in a response
 * # 206 Partial Content - Response only part of the resources data
 * # 400 Bad Request - Invalid requested range
 *
 * Headers
 * "Content-Range" - 0-24/48 - [offset - limit / count]
 * "Accept-Range" - resource 50 - [resource] - type of the resources, [50] - maximum number of resources that allowed
 *                  to get for the single request
 *
 * Also provide pagination
 * "Link" - navigation links such as next page, previous page and last page
 *          &lt;https://api.example.com/v1/items?range=0-7&gt;; rel="first",
 *          &lt;https://api.example.com/v1/items?range=40-47&gt;; rel="prev",
 *          &lt;https://api.example.com/v1/itemss?range=56-64&gt;; rel="next",
 *          &lt;https://api.example.com/v1/items?range=56-64&gt;; rel="last"
 *
 *
 * @package oat\taoRestAPI\model\http\Response
 */
class Paginate extends AbstractFilter
{

    /**
     * @var array
     */
    protected $options = [
        'query' => '',
        'limit' => false,
        'offset' => false,
        'acceptRange' => 50,
        'total' => 0,
        'paginationUrl' => '', // optional
    ];

    /**
     * Offset (like sql offset)
     * @return int
     */
    public function offset()
    {
        return $this->options['offset'];
    }

    /**
     * Limit of the records (like sql limit)
     * @return int
     */
    public function length()
    {
        return $this->options['limit'] - $this->options['offset'] + 1;
    }

    /**
     * After query to DB with all filters, paste in Content-Range real values
     * @param int $loaded - the count of uploaded data
     * @param $totalFiltered - count of uploaded data without pagination
     */
    public function correctPaginationHeader($loaded = 0, $totalFiltered = 0)
    {
        if ($loaded) {
            $this->options['limit'] = $this->options['offset'] + $loaded;
            $this->options['total'] = $totalFiltered;
            $this->setSuccessResponse();
        }
    }

    private function setSuccessResponse()
    {
        if ($this->options['offset'] == 0 && $this->options['limit'] >= $this->options['total']
            && $this->options['limit'] < $this->options['acceptRange']
        ) {
            // all data in response
            $this->setStatusCode(200);
            $this->addHeader('Content-Range', $this->options['offset'] . '-' . ($this->options['limit']-1) . '/' . $this->options['total']);
            $this->addHeader('Accept-Range', 'resource ' . $this->options['acceptRange']);
        }
        else {
            // range is smaller than count records
            $this->setStatusCode(206);

            $limit = $this->options['limit'];
            if ($limit >= $this->options['total']) {
                $limit = $this->options['total'] - 1;
            } else {
                --$limit;
            }

            $this->addHeader('Content-Range', $this->options['offset'] . '-' . $limit . '/' . $this->options['total']);
            $this->addHeader('Accept-Range', 'resource ' . $this->options['acceptRange']);

            if ($this->options['paginationUrl']) {
                $this->paginationLinks();
            }
        }
    }

    /** From router get link and generate new pagination Http header
     * Example:
     *  $link = http://api.taotesting.com/v1/items?range=
     *  $link = http://api.taotesting.com/v1/items?fields=field1,field2&sort=field1,field2&range=
     */
    private function paginationLinks()
    {
        $length = $this->options['limit'] - $this->options['offset'];

        $pages['first'] = '0-' . ($length - 1);
        $pages['last'] = ($this->options['total'] - $length) . '-' . ($this->options['total'] - 1);

        $pages['prev'] = $this->options['offset'] - $length < 0
            ? $pages['last']
            : ($this->options['offset'] - $length) . '-' . ($this->options['offset'] - 1);

        if ($this->options['offset'] + $length >= $this->options['total']) {
            $pages['next'] = $pages['first'];
        } elseif ($this->options['limit'] + $length >= $this->options['total'] && $this->options['offset'] + 1 < $this->options['total']) {
            $pages['next'] = $this->options['limit'] . '-' . ($this->options['total']-1);
        } else {
            $pages['next'] = ($this->options['offset'] + $length) . '-' . ($this->options['limit'] + $length - 1);
        }
        
        $links = [];
        foreach ($pages as $rel => $range) {
            $links[] = '&lt;' . $this->options['paginationUrl'] . $range . '&gt;; rel="' . $rel . '"';
        }

        $this->addHeader('Link', $links);
    }

    protected function prepare()
    {
        $this->setRange($this->options['query']);
        $this->validate();

        if ($this->options['limit'] < 0 || $this->options['limit'] === false) {
            $this->options['limit'] = $this->options['total'] < $this->options['acceptRange']
                ? $this->options['total']
                : $this->options['acceptRange'];

            --$this->options['limit'];
        }
    }

    private function setRange($query = '')
    {
        $this->options['limit'] = $this->options['total'] - 1;
        $this->options['offset'] = 0;

        if (!empty($query)) {

            if (!preg_match("/^\d{1,4}-\d{1,4}$/", $query)) {
                throw new HttpRequestException('Incorrect range parameter. Try to use: ?range=0-25', 400);
            } else {
                list($this->options['offset'], $this->options['limit']) = explode('-', $query);
            }
        }
    }

    /**
     * @throws HttpRequestException
     */
    private function validate()
    {
        if ($this->options['acceptRange'] <= 0) {
            throw new HttpRequestExceptionWithHeaders('Invalid acceptRange', 400, ['Accept-Range' => 'resource ' . $this->options['acceptRange']]);
        } elseif ($this->options['total'] <= 0) {
            throw new HttpRequestExceptionWithHeaders('Empty response data', 400, ['Accept-Range' => 'resource ' . $this->options['acceptRange']]);
        } elseif (
            // enormous range
            ($this->options['limit'] - $this->options['offset']) >= $this->options['total']
            // bad range
            || $this->options['offset'] > $this->options['limit']
            // invalid range
            || $this->options['offset'] >= $this->options['total']
            || $this->options['offset'] < 0

        ) {
            throw new HttpRequestExceptionWithHeaders('Invalid range', 400, ['Accept-Range' => 'resource ' . $this->options['acceptRange']]);
        }

    }
}
