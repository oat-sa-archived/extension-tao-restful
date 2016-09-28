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

namespace oat\taoRestAPI\test\TaoCurlRequest;


use core_kernel_classes_Class;
use oat\tao\test\TaoPhpUnitTestRunner;

abstract class RestTestCase extends TaoPhpUnitTestRunner
{

    protected $host = ROOT_URL;

    protected $userUri = "";

    protected $login = "";

    protected $password = "";


    public abstract function serviceProvider();

    public function setUp()
    {
        TaoPhpUnitTestRunner::initTest();
        $this->disableCache();

        // creates a user using remote script from joel

        $testUserData = array(
            PROPERTY_USER_LOGIN => 'tjdoe',
            PROPERTY_USER_PASSWORD => 'test123',
            PROPERTY_USER_LASTNAME => 'Doe',
            PROPERTY_USER_FIRSTNAME => 'John',
            PROPERTY_USER_MAIL => 'jdoe@tao.lu',
            PROPERTY_USER_DEFLG => \tao_models_classes_LanguageService::singleton()->getLanguageByCode(DEFAULT_LANG)->getUri(),
            PROPERTY_USER_UILG => \tao_models_classes_LanguageService::singleton()->getLanguageByCode(DEFAULT_LANG)->getUri(),
            PROPERTY_USER_ROLES => array(
                INSTANCE_ROLE_GLOBALMANAGER
            )
        );

        $testUserData[PROPERTY_USER_PASSWORD] = 'test' . rand();

        $data = $testUserData;
        $data[PROPERTY_USER_PASSWORD] = \core_kernel_users_Service::getPasswordHash()->encrypt($data[PROPERTY_USER_PASSWORD]);
        $tmclass = new \core_kernel_classes_Class(CLASS_TAO_USER);
        $user = $tmclass->createInstanceWithProperties($data);
        \common_Logger::i('Created user ' . $user->getUri());

        // prepare a lookup table of languages and values
        $usage = new \core_kernel_classes_Resource(INSTANCE_LANGUAGE_USAGE_GUI);
        $propValue = new \core_kernel_classes_Property(RDF_VALUE);
        $langService = \tao_models_classes_LanguageService::singleton();

        $lookup = array();
        foreach ($langService->getAvailableLanguagesByUsage($usage) as $lang) {
            $lookup[$lang->getUri()] = (string) $lang->getUniquePropertyValue($propValue);
        }

        $data = array(
            'rootUrl' => ROOT_URL,
            'userUri' => $user->getUri(),
            'userData' => $testUserData,
            'lang' => $lookup
        );

        $this->login = $data['userData'][PROPERTY_USER_LOGIN];
        $this->password = $data['userData'][PROPERTY_USER_PASSWORD];
        $this->userUri = $data['userUri'];
    }

    public function tearDown()
    {
        // removes the created user
        $user = new \core_kernel_classes_Resource($this->userUri);
        $success = $user->delete();
        $this->restoreCache();
    }

    /**
     * shall be used beyond high level http connections unit tests (default parameters)
     *
     * @param
     *            returnType CURLINFO_HTTP_CODE, etc... (default returns rhe http response data
     *
     * @param $url
     * @param int $method
     * @param string $returnType
     * @param array $curlopt_httpheaders
     * @param string $postfields
     * @return mixed
     */
    protected function curl($url, $method = CURLOPT_HTTPGET, $returnType = "data", $curlopt_httpheaders = array(), $postfields = '')
    {
        $process = curl_init($url);

        /**
         * For sending json string I had to use different PUT request instead of build-in
         * For streams file sending, must to use build-in PUT 
         */
        if ($method == CURLOPT_PUT && is_string($postfields)) {
            curl_setopt($process, CURLOPT_CUSTOMREQUEST, "PUT");
        } elseif (in_array($method, ['DELETE', 'PATCH'])) {
            curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            curl_setopt($process, $method, 1);
        }

        if($method == CURLOPT_PUT && is_array($postfields) && count($postfields)==2) {
            curl_setopt($process, CURLOPT_INFILE, $postfields[0]);
            curl_setopt($process, CURLOPT_INFILESIZE, $postfields[1]);
            curl_setopt($process, CURLOPT_UPLOAD, true);
        }

        curl_setopt($process, CURLOPT_USERPWD, $this->login . ":" . $this->password);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        // debug
        // curl_setopt($process, CURLOPT_VERBOSE, true);

        $headers = array_merge(array(
            "Accept: application/json"
        ), $curlopt_httpheaders);
        
        curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
        
        if ($method == CURLOPT_POST || (in_array($method, [CURLOPT_PUT, 'PATCH']) && is_string($postfields))) {
            curl_setopt($process, CURLOPT_POSTFIELDS, $postfields);
        }
        
        $data = curl_exec($process);
        if ($returnType != "data") {
            $data = curl_getinfo($process, $returnType);
        }
        
        curl_close($process);
        return $data;
    }

    /**
     * @dataProvider serviceProvider
     */
    public function testHttp($service)
    {
        $url = $this->host . $service;
        // HTTP Basic
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Accept: application/json"
        ));

        // should return a 401
        curl_setopt($process, CURLOPT_USERPWD, "dummy:dummy");
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($process);
        $http_status = curl_getinfo($process, CURLINFO_HTTP_CODE);

        $this->assertEquals("401", $http_status, 'bad response on url ' . $url . ' return ' . $http_status);
        curl_close($process);

        // should return a 401
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Accept: application/json"
        ));
        curl_setopt($process, CURLOPT_USERPWD, $this->login . ":dummy");
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($process);
        $http_status = curl_getinfo($process, CURLINFO_HTTP_CODE);
        $this->assertEquals($http_status, "401");
        curl_close($process);

        // should return a 406
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Accept: dummy/dummy"
        ));
        curl_setopt($process, CURLOPT_USERPWD, $this->login . ":" . $this->password);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($process);
        $http_status = curl_getinfo($process, CURLINFO_HTTP_CODE);
        $this->assertEquals($http_status, "406");
        curl_close($process);

        // should return a 200
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Accept: application/xml"
        ));
        curl_setopt($process, CURLOPT_USERPWD, $this->login . ":" . $this->password);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($process);
        $http_status = curl_getinfo($process, CURLINFO_HTTP_CODE);
        $this->assertEquals($http_status, "200");

        // should return a 200, should return content encoding application/xml
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array(
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
        ));
        curl_setopt($process, CURLOPT_USERPWD, $this->login . ":" . $this->password);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($process);
        $http_status = curl_getinfo($process, CURLINFO_HTTP_CODE);
        $this->assertEquals($http_status, "200");
        $contentType = curl_getinfo($process, CURLINFO_CONTENT_TYPE);
        $this->assertEquals($contentType, "application/xml; charset=UTF-8");
        curl_close($process);

        // should return a 200
        $http_status = $this->curl($url, CURLOPT_HTTPGET, CURLINFO_HTTP_CODE);
                        $this->assertEquals($http_status, "200");

    }

    /**
     * Test HTTP GET list of data
     * @dataProvider serviceProvider
     * @author Lionel Lecaque, lionel@taotesting.com
     */
    public function testGetAll($service, $topclass = null){

        $instances = $this->getInstance($topclass);
        $data = $this->HttpGet($service);
        foreach ($data as $results)
            $this->checkResourceStructure($results, $instances);
    }

    /**
     * Check HTTP GET for only one item (get by identifier)
     * @param $service
     * @param null $topClass
     */
    protected function checkGet($service, $topClass=null){
        $this->checkResourceStructure(
            $this->HttpGet($service)[0],
            $this->getInstance($topClass)
        );
    }

    /**
     * Check the data structure of the response data set
     * @param $results
     * @param $instances
     */
    private function checkResourceStructure($results, $instances)
    {

        $this->assertInternalType('array', $results);
        $this->assertArrayHasKey('uri', $results);
        $this->assertArrayHasKey('properties', $results);
        $this->assertInternalType('array', $instances);

        $this->assertArrayHasKey($results['uri'], $instances);
        $resource = $instances[$results['uri']];

        foreach ($results['properties'] as $propArray){
            $this->assertInternalType('array', $propArray);

            $this->assertArrayHasKey('predicateUri',$propArray);
            $prop = new \core_kernel_classes_Property($propArray['predicateUri']);
            $values = $resource->getPropertyValues($prop);
            $this->assertArrayHasKey('values',$propArray);
            $current = current($propArray['values']);
            $this->assertInternalType('array',$current);

            $this->assertArrayHasKey('valueType',$current);
            if (\common_Utils::isUri(current($values))){
                $this->assertEquals('resource', $current['valueType']);

            } else {
                $this->assertEquals('literal', $current['valueType']);
            }
            $this->assertArrayHasKey('value',$current);
            $this->assertEquals(current($values), $current['value']);

        }

    }

    /**
     * CRUD Http GET request
     * @param $service
     * @return array
     */
    private function HttpGet($service)
    {
        $returnedData = $this->curl($this->host . $service);
        return $this->getData($returnedData);

    }

    /**
     * get instance of topClass
     * @param $topClass
     * @return array
     */
    private function getInstance($topClass=null)
    {
        if ($topClass == null) {
            $this->markTestSkipped('This test do not apply to topclass', $topClass);
        }

        $ItemClass = new core_kernel_classes_Class($topClass);
        $instances = $ItemClass->getInstances(true);
        return $instances;
    }

    /**
     * CRUD HTTP PUT request
     * 
     * @param $uri
     * @param array $headers
     * @param string $postFields
     * @return mixed
     */
    protected function checkPut($uri, array $headers=[], $postFields = '')
    {
        $returnedData = $this->curl($this->host . $uri, CURLOPT_PUT, 'data', $headers, $postFields);
        return $this->getData($returnedData);
    }

    /**
     * CRUD HTTP PATCH request
     *
     * @param $uri
     * @param array $headers
     * @param string $postFields
     * @return mixed
     */
    protected function checkPatch($uri, array $headers=[], $postFields = '')
    {
        $returnedData = $this->curl($this->host . $uri, 'PATCH', 'data', $headers, $postFields);
        return $this->getData($returnedData);
    }

    /**
     * CRUD HTTP POST request
     * @param $uri
     * @param array $headers
     * @param string $postfields
     * @return mixed
     */
    protected function checkPost($uri, $headers=[], $postfields='')
    {
        $returnedData = $this->curl($this->host . $uri, CURLOPT_POST, 'data', $headers, $postfields);
        return $this->getData($returnedData);
    }

    /**
     * Http DELETE
     * @param $service
     * @return mixed
     */
    protected function checkDelete($service){

        $returnedData = $this->curl($this->host . $service, "DELETE");
        return $this->getData($returnedData);
    }

    /**
     * All Rest response have to have similar structure
     *
     * @param $data
     * @return mixed
     */
    private function getData($data)
    {
        $data = json_decode($data, true);
        return $data;
    }
}
