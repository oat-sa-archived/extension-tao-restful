Represent state transfer [version 1.0]
======================================

- [REST](#rest)
    - [Restful usage](#restful-usage)
    - [CRUD](#crud)
        - [GET](#get)
        - [POST](#post)
        - [PUT](#put)
        - [PATCH](#patch)
        - [DELETE](#delete)
        - [OPTIONS](#options)
    - [Filters](#filters)
        - [Filter](#filter)
        - [Paginate](#paginate)
        - [Partial](#partial)
        - [Sort](#sort)
    - [Status codes](#status-codes)
        - [Success](#success)
        - [Client error](#client-error)
        - [Server error](#server-error)
    - [Lifecycle](#lifecycle)
    - [Versioning](#versioning)
- [Authentication](#authentication)
- [Encoding](#encoding)
    - [JSON](#json-encoder)
    - [RDF](#rdf-encoder)
    - [XML](#xml-encoder)
- [Router adapter](#router-adapter)
    - [Slim framework](#slim)
    - [Clearfw](#clearfw)
- [Storage adapter](#storage-adapter)
    - [Array storage](#array-storage)
    - [RDF storage](#rdf-storage)
    - [QTI storage](#qti-storage)

## REST

REST-compliant web services allows to quickly access data.

### Restful usage

For the implementation of Restful protocol for the data access uses extensions-middleware.
In that way will be excluded cases of the strong dependencies between different extensions.

All requests which uri contains `RestApi` will be intercepted and treated by RestApi extension.

As an example take taoItemRestApi extension. This extension uses for access 
to the item rdf data.

First of all create configuration file for the REST implementation: 
```php
return [

    /**
     * Auth method for access to RestApi protocol
     * @see \oat\taoRestAPI\model\AuthenticationInterface
     */
    'authenticator' => '\oat\taoRestAPI\proxy\BasicAuthentication',
    
    /**
     * default definer for the format of the data
     * @see \oat\taoRestAPI\model\HttpDataFormatInterface
     */
    'encoder' => '\oat\taoRestAPI\model\v1\http\Request\DataFormat',
    
    /**
     * Adapter for requested data from different frameworks (Slim, ClearFw)
     * @see \oat\taoRestAPI\model\v1\http\Request\RouterAdapter
     */
    'routerAdapter' => '\oat\taoRestAPI\model\v1\http\Request\RouterAdapter\TaoRouterAdapter',
    
    /**
     * Adapter for data access (Array, Qti, Rdf ... types of the data storage)
     * @see \oat\taoRestAPI\model\DataStorageInterface
     */
    'storageAdapter' => '\oat\taoItemRestApi\model\v1\ItemRdfStorageAdapter',
    
    /**
     * Configurable identifier getter
     * 
     * #examples:
     * url $_GET param: ?uri={id} => ['type' => 'get', 'key' => 'uri'] // used by tao for Rdf models with clearfw
     * in url params: /url/{id} => ['type' => 'param', 'key' => 'id'] // used by phpunit test with Slim framework
     * 
     * @param string
     */
    'idRule' => [
        'type' => 'get',
        'key' => 'uri'
    ],
];
```

And second that implementation of the storage adapter for current type of data.

### CRUD

Create, read, update, delete - the things which we have do with data.
The REST approach is using HTTP as a protocol.

HTTP | CRUD action | Collection : /orders| Instance : /orders/{id}
-----|-------------|---------------------|------------------------
GET | READ | Read a list of orders. 200 OK. | Read the detail of a single order. 200 OK.
POST | CREATE | Create a new order. 201 Created. | –
PUT | UPDATE | – | Full Update. 200 OK.
PATCH | UPDATE | – | Partial Update. 200 OK.
DELETE | DELETE | – | Delete order. 200 OK.

###### GET

Reading the collections of the data. Returns  
_Identifier of the instance is_ **not** _required._    
For more information see [Filters](#filters).

Or read one instance of the data. Can be used with filter [Partial](#partial).

###### POST

Create new instance in the collection.
_Identifier of the instance is_ **not** _required._  
The resource URI and id are sent back in the header “_Location_” of the response.

###### PUT

**Full** update of the instance. That mean that all instance will be replaced with new data.
All fields which not provided as attributes will be deleted.

###### PATCH

**Partial** update of the instance. That mean that will be updated only data that provided.
All fields which not provided as attributes are left untouched. 

###### DELETE

Delete instance.

###### OPTIONS

It can be any additional information for Restful protocol for that instance or collection.


### Filters

Any data which can be selected under the Restful protocol should have opportunity to be filtered by some criteria and returned to the receiver in the ordered slices.

***

#### Filter

Filtering data by the specified values.

##### For example

###### Data

id | title | type | form | color
---|-------|------|------|------
 1 | Potato | vegetable | circle | brown
 2 | Lemon | citrus | ellipse | yellow
 3 | Lime | citrus | ellipse | green
 4 | Carrot | vegetable | conical | orange
 5 | Orange | citrus | circle | orange

###### Request

`?type=citrus,vegetable&form=circle`

###### Result

id | title | type | form | color
---|-------|------|------|------
 5 | Orange | citrus | circle | orange

###### Response

`200` `"OK"`
 
###### Headers

`"Content-Range"` `0-1/2`

`"Accept-Range"` - `resource 50`


***

#### Paginate

Provides pagination in the REST protocol.

###### Expected responses

`200` `'Ok'` - All resources data fit in a response

`206` `'Partial Content'` - Response only part of the resources data

`400` `'Bad Request'` - Invalid requested range

###### Expected headers

- [offset - limit / count]

    `"Content-Range"` - `0-24/48`

- [resource] - type of the resources,

    `"Accept-Range"` - `resource 50`
    
    - [50] - maximum number of resources that allowed to get for the single request

- navigation links such as next page, previous page and last page

    `"Link"` - 
    
         &lt;https://api.example.com/v1/items?range=0-7&gt;; rel="first",
         &lt;https://api.example.com/v1/items?range=40-47&gt;; rel="prev",
         &lt;https://api.example.com/v1/itemss?range=56-64&gt;; rel="next",
         &lt;https://api.example.com/v1/items?range=56-64&gt;; rel="last"


##### For example

###### Data

id | title | type | form | color
---|-------|------|------|------
 1 | Potato | vegetable | circle | brown
 2 | Lemon | citrus | ellipse | yellow
 3 | Lime | citrus | ellipse | green
 4 | Carrot | vegetable | conical | orange
 5 | Orange | citrus | circle | orange

###### Request

`?range=3-4`

###### Result

id | title | type | form | color
---|-------|------|------|------
 4 | Carrot | vegetable | conical | orange
 5 | Orange | citrus | circle | orange

###### Response

`206` `"Partial Content"`
 
###### Headers

`"Content-Range"` `3-4/5`

`"Accept-Range"` - `resource 50`

`"Link"` - 

    &lt;http://localhost/resources?range=0-1&gt;; rel="first"
    &lt;http://localhost/resources?range=3-4&gt;; rel="last"
    &lt;http://localhost/resources?range=1-2&gt;; rel="prev"
    &lt;http://localhost/resources?range=0-1&gt;; rel="next"

***

#### Partial

Partial answers allow clients to retrieve only the information they need.

##### For example

###### Data

id | title | type | form | color
---|-------|------|------|------
 1 | Potato | vegetable | circle | brown
 2 | Lemon | citrus | ellipse | yellow
 3 | Lime | citrus | ellipse | green
 4 | Carrot | vegetable | conical | orange
 5 | Orange | citrus | circle | orange

###### Request

`?fields=title,type`

###### Result

id | title | type 
---|-------|------
 1 | Potato | vegetable
 2 | Lemon | citrus
 3 | Lime | citrus
 4 | Carrot | vegetable
 5 | Orange | citrus
 
###### Response

`200` `"OK"`
 
###### Headers

`"Content-Range"` `0-4/5`

`"Accept-Range"` - `resource 50`

***


#### Sort

Sorting the result of a query on a collection of resources requires two main parameters:
- **sort**: Contains the names of the attributes on which the sorting is performed.
- **desc**: By default, the sorting is done in ascending order. 
If one wishes to sort in descending order, they need to add this parameter (without any value).
In some specific cases, one may want to specify which attributes should be used as ascending
sort keys and which as descending sort keys. Then, the desc parameter should contain the
attributes that will be descending sort keys, the others will be ascending sort keys.

##### For example

###### Data

id | title | type | form | color
---|-------|------|------|------
 1 | Potato | vegetable | circle | brown
 2 | Lemon | citrus | ellipse | yellow
 3 | Lime | citrus | ellipse | green
 4 | Carrot | vegetable | conical | orange
 5 | Orange | citrus | circle | orange

###### Request

`?sort=title&desc=title`

###### Result

id | title | type 
---|-------|------
 1 | Potato | vegetable
 5 | Orange | citrus
 3 | Lime | citrus
 2 | Lemon | citrus
 4 | Carrot | vegetable
 
###### Response

`200` `"OK"`
 
###### Headers

`"Content-Range"` `0-4/5`

`"Accept-Range"` - `resource 50`

### Status codes

For every common case are used HTTP codes.

#### Success

HTTP Status | Description
------------|------------
200 OK | Common success code
201 Created | Resource created
206 Partial Content | The returned content is not completed. (Used with [filters](#filters))

#### Client error

HTTP Status | Description
------------|------------
400 Bad Request | Common error code for incorrect data or actions from users
401 Unauthorized | Error code from [authentication](#authentication)
404 Not Found | Source not found

#### Server error

HTTP Status | Description
------------|------------
500 Internal Server Error | Request is correct but something happens on the server. The reason often in the Body
501 Unsupported HTTP request method | Incorrect HTTP method (GET, POST ...)

### Lifecycle

Rest API lifecycle:

`authenticate` &rarr; `route` &rarr; `encode`

### Versioning

For each resources should be pointed version of the Rest API which used.
Version can be placed at beginning or at the end of the URI.

_Example:_

`http://www.taotesting.com/taoItemsRestApi/v1/`

## Authentication

For using authentication in the restful, we need to determine `authenticator` in the API 
configuration file.

`'authenticator' => '\oat\taoRestAPI\proxy\BasicAuthentication'`

## Encoding

By default responses will be in JSON format. But it can be changed if in HTTP response header
_HTTP_ACCEPT_ send `application/xml`

### JSON encoding

`application/json` (_default_)

### XML encoding

`application/xml`

### RDF encoding

Does not implemented yet

## Router adapter

Restful protocol should know where from he can get needed data (Headers, Http Status Code, BodyData).

Example of usages:
```
'routerAdapter' => '\oat\taoRestAPI\model\v1\http\Request\RouterAdapter\TaoRouterAdapter',
'routerAdapter' => '\oat\taoRestAPI\model\v1\http\Request\RouterAdapter\SlimRouterAdapter',
```

Method | Description
-------|------------
`get()` | Action on HTTP GET request. Get data.
`post()` | Action on HTTP POST request. Create resource.
`put()` | Action on HTTP PUT request. Replace all resource data.
`patch()` | Action on HTTP PATCH request. Update/Edit resource fields.
`options()` | Action on HTTP OPTION request. Specified resource options.
`getHeaders()` | Headers from HTTP request
`getStatusCode()` | HTTP Status Code for the response
`getBodyData()` | Data for HTTP Body for the response
`storage()` | Current [Storage adapter](#storage-adapter)

### Slim framework

Implemented router adapter for working through [Slim micro framework](http://www.slimframework.com/).

For usage check your configuration file:

`'routerAdapter' => '\oat\taoRestAPI\model\v1\http\Request\RouterAdapter\SlimRouterAdapter'`

### Clearfw

Router adapter for working through [clearfw](https://github.com/oat-sa/clearfw)

For usage check your configuration file:

`'routerAdapter' => '\oat\taoRestAPI\model\v1\http\Request\RouterAdapter\TaoRouterAdapter'`


## Storage adapter

Storage adapters are used for data access from different data sources.

Interface of the storage adapter include:

Method | Parameter | Description
-------|-----------|-------------
`getOne()` | id, partialFields | Load full resource or only part of the resource
`getFields()` | Get the names of the resource properties
`searchInstances()` | params | Search instances by parameters
`post()` | properties | Create new resource
`put()` | id, properties | Replace resource properties
`patch()` | id, properties | Edit resource properties
`delete()` | id | Delete resource
`exists()` | identifier | Check if resource exists
`isAllowedDefaultResources()` | | Check if storage can create new resources with default values


For usages place in the configuration file:

`'storageAdapter' => '\oat\taoItemRestApi\model\v1\ItemRdfStorageAdapter'`

### Array storage

Storage adapter for data stored in the array. Usually used in the unit tests.

### RDF storage

RDF implementation for the TAO RDF data storage.

Method | Parameter | Description
-------|-----------|------------
`getService()` | | Service, extended from `tao_models_classes_ClassService`

### QTI storage

TODO, Not implemented yet
 