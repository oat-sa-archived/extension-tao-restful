<?php
/**
 * Copyright (c) 2016 Open Assessment Technologies, S.A.
 *
 * @author Alexander Zagovorichev, <zagovorichev@1pt.com>
 */

namespace oat\taoRestAPI\model;


interface HttpRouterInterface
{

    /**
     * Get data
     * HTTP GET
     * @return array|resource 200 or 206 ("Partial Content" if many items and used pagination)
     *
     * @author Alexander Zagovorichev <zagovorichev@gmail.com>
     */
    public function get();

    /**
     * Create new record
     * Http POST
     *
     * @return mixed Resource identifier
     */
    public function post();

    /**
     * Update full record (all data in record)
     * Http PUT
     *
     * @return mixed Resource identifier
     */
    public function put();

    /**
     * Change only specified data (not all record)
     * Http PATCH
     *
     * @return mixed resource identifier
     */
    public function patch();

    /**
     * Delete record
     * Http DELETE
     * 
     * @return bool
     */
    public function delete();
    
    /**
     * Return options for record (List of the available rest operations)
     * HTTP OPTIONS
     *
     * # Can be:
     * # 200 Ok
     * # Allow: OPTIONS, GET, POST, DELETE
     *
     * # Or a complete description of the available rest operations
     * # {
     * #   "POST": {
     * #      "description": "Create an issue",
     * #      "parameters": {
     * #        "title": {
     * #          "type": "string",
     * #          "description": "Issue title",
     * #          "required": true
     * #        },
     * #        "body": {
     * #          "type": "string",
     * #           "description": "Issue body"
     * #        }
     * #    }
     * # }
     * #
     * @return mixed
     *
     * @author Alexander Zagovorichev <zagovorichev@gmail.com>
     */
    public function options();

    /**
     * Storage
     * 
     * @return DataStorageInterface
     */
    public function storage();
}
