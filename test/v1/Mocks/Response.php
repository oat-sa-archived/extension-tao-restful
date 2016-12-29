<?php
/**
 * Copyright (c) 2016 Open Assessment Technologies, S.A.
 *
 * @author Alexander Zagovorichev, <zagovorichev@1pt.com>
 */

namespace oat\taoRestAPI\test\v1\Mocks;


use Slim\Http\Response as HttpResponse;

class Response extends HttpResponse
{
    /**
     * Source data for response, if needed for data handling
     * @var mixed
     */
    private $resourceData;
    
    public function setResourceData( $data )
    {
        $this->resourceData = $data;
    }
    
    public function getResourceData()
    {
        return $this->resourceData;
    }
}
