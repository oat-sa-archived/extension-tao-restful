<?php
/**
 * Copyright (c) 2016 Open Assessment Technologies, S.A.
 *
 * @author Alexander Zagovorichev, <zagovorichev@1pt.com>
 */

namespace oat\taoRestAPI\test\v1\Mocks;


use oat\taoRestAPI\model\v1\StorageAdapter\ArrayStorageAdapter;

if (!defined('API_HOST')) {
    define('API_HOST', trim(preg_replace('|^.*://(.*)$|', "\\1", ROOT_URL), '/'));
}

/**
 *
 * @SWG\Swagger(
 *   swagger="2.0",
 *   schemes={"http","https"},
 *   host=API_HOST,
 *   basePath="/taoRestAPI/v1/",
 *   consumes={"application/json","application/xml"},
 *   produces={"application/json","application/xml"},
 *   @SWG\Info(
 *     title="TAO Example RestAPI",
 *     version="1.0.0",
 *     description="RestAPI control for TAO.
 *      [Learn about TAO](http://www.taotesting.com/).
 *      For this sample, you can use the api key `oAuth-token-for-test`",
 *     termsOfService="http://www.taotesting.com/resources/faq/",
 *     @SWG\Contact(
 *       name="Open Assessment Technologies S.A.",
 *       email="contact@taotesting.com",
 *       url="http://www.taotesting.com/contact/"
 *     ),
 *     @SWG\License(
 *       name="GNU General Public License",
 *       url="http://www.gnu.org/licenses/gpl.html"
 *     ),
 *   )
 * )
 *
 * ### Models
 * ---
 * @SWG\Definition(
 *     required={"title", "type"},
 *     @SWG\Xml(name="Resource"),
 *     @SWG\Property(title="id",format="int64"),
 *     definition="Resource for test"
 * )
 * ===
 * ###
 *
 *
 * ### Tags
 * ---
 * @SWG\Tag(
 *   name="Resources for example",
 *   description="TaoRestAPI examples"
 * )
 * ===
 * ###
 *
 *
 * ### List of the resources
 * ---
 *
 * @SWG\Get(
 *   path="resources",
 *   summary="All resources",
 *   description="Get list of the resources",
 *   tags={"Resources for example"},
 *   operationId="getList",
 *   consumes={"application/xml", "application/json"},
 *   produces={"application/xml", "application/json"},
 *   @SWG\Response(
 *     response=200,
 *     description="OK",
 *     @SWG\Schema(ref="#/definitions/Resource")
 *   ),
 *
 *   @SWG\Parameter(
 *     name="range",
 *     in="query",
 *     type="string",
 *     description="Paginate by the resources {0-1}",
 *     required=false,
 *     collectionFormat="multi"
 *   ),
 *   @SWG\Parameter(
 *     name="title",
 *     in="query",
 *     description="Filter by title (Potato,Orange)",
 *     required=false,
 *     type="string",
 *     @SWG\Items(type="array"),
 *     collectionFormat="multi"
 *   ),
 *   @SWG\Parameter(
 *     name="type",
 *     in="query",
 *     description="Filter by type (vegetable)",
 *     required=false,
 *     type="string",
 *     @SWG\Items(type="array"),
 *     collectionFormat="multi"
 *   ),
 *   @SWG\Parameter(
 *     name="fields",
 *     in="query",
 *     description="Return only specified fields (title,type)",
 *     required=false,
 *     type="string",
 *     @SWG\Items(type="array"),
 *     collectionFormat="multi"
 *   ),
 *   @SWG\Parameter(
 *     name="sort",
 *     in="query",
 *     description="Sorting by fields ASC (title,type)",
 *     required=false,
 *     type="string",
 *     @SWG\Items(type="array"),
 *     collectionFormat="multi"
 *   ),
 *   @SWG\Parameter(
 *     name="desc",
 *     in="query",
 *     description="Using with ?sort=field, and set DESC direction for field (title,type) ",
 *     required=false,
 *     type="string",
 *     @SWG\Items(type="array"),
 *     collectionFormat="multi"
 *   ),
 *
 *   @SWG\Response(
 *     response=206,
 *     description="Partial Content",
 *     @SWG\Schema(ref="#/definitions/Resource")
 *   ),
 *   @SWG\Response(
 *     response=400,
 *     description="Incorrect range parameter. Try to use: ?range=0-25"
 *   ),
 *   @SWG\Response(
 *     response=406,
 *     description="Not acceptable encoding format"
 *   ),
 *
 * )
 *
 * ====
 * ###
 *
 * ### Get one resource
 *
 * @SWG\Get(
 *   path="resources/{id}",
 *   summary="Find resource by ID",
 *   tags={"Resources for example"},
 *   operationId="getItem",
 *   @SWG\Parameter(
 *      name="id",
 *      in="query",
 *      type="string",
 *      description="Unique Id of the resource",
 *      required=true
 *   ),
 *   @SWG\Response(
 *     response=200,
 *     description="A list with resources|Resource was found"
 *   ),
 *   @SWG\Response(
 *     response="default",
 *     description="an unexpected error"
 *   )
 * )
 * 
 * 
 */


/**
 * Class DB
 * 
 * Local static DB for testing RestApi
 * 
 * @package oat\taoRestAPI\test\v1\Mocks
 */
class DB extends ArrayStorageAdapter
{

    /**
     * Data for testing and example of workflow
     * @var array
     */
    protected $resourcesData = [
        [
            'id' => 1,
            'title' => 'Potato',
            'type' => 'vegetable',
            'form' => 'circle',
            'color' => 'brown',
        ],
        [
            'id' => 2,
            'title' => 'Lemon',
            'type' => 'citrus',
            'form' => 'ellipse',
            'color' => 'yellow',
        ],
        [
            'id' => 3,
            'title' => 'Lime',
            'type' => 'citrus',
            'form' => 'ellipse',
            'color' => 'green',
        ],
        [
            'id' => 4,
            'title' => 'Carrot',
            'type' => 'vegetable',
            'form' => 'conical',
            'color' => 'orange',
        ],
        [
            'id' => 5,
            'title' => 'Orange',
            'type' => 'citrus',
            'form' => 'circle',
            'color' => 'orange',
        ],
    ];
}
