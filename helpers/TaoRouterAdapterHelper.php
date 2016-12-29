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

namespace oat\taoRestAPI\helpers;


use common_Exception;
use common_exception_Error;
use common_Logger;
use tao_helpers_Http;

class TaoRouterAdapterHelper
{
    /**
     * Init stream variable
     * For multiple stream usages (because input we can read only once)
     *
     * @var null
     */
    private static $stream = null;

    /**
     * Get json from stream
     */
    public static function getJsonDataFromStream()
    {
        if (($contents = stream_get_contents(self::getStream())) === false) {
            throw new common_exception_Error('Could not get contents of stream', 400);
        }

        self::streamValidation(md5($contents), strlen($contents));

        return json_decode(urldecode($contents), true);
    }

    public static function getStream()
    {
        if (!isset(self::$stream)) {

            $headers = tao_helpers_Http::getHeaders();
            if (!isset($headers['Content-Length'])) {
                //unset($putData);
                throw new common_exception_Error("Incorrect Http headers, must have Content-Length",
                    411); // 411 Length Required
            }

            if (!isset($headers['Content-Md5'])) {
                //unset($putData);
                throw new common_exception_Error("Incorrect Http headers, must have Content-Md5",
                    449); // 449 Retry With
            }

            if (($putData = fopen("php://input", "r")) === false) {
                throw new common_exception_Error("Can't get php://input data", 400);
            }

            self::$stream = fopen('php://temp', 'w+');
            stream_copy_to_stream($putData, self::$stream);
            rewind(self::$stream);
        }

        return self::$stream;
    }

    private static function streamValidation($md5, $length)
    {
        $headers = tao_helpers_Http::getHeaders();

        // Check file length and MD5
        if ($length != $headers['Content-Length']) {
            throw new common_exception_Error("Wrong file size", 412); // Precondition Failed
        }
        if ($md5 != $headers['Content-Md5']) {
            throw new common_exception_Error("Wrong md5", 412);
        }
    }

    /**
     * Stream file loading (Http PUT)
     *
     * @return string
     * @throws common_Exception
     */
    public static function getFileFromInputStream()
    {

        if (!($putData = fopen("php://input", "r"))) {
            throw new common_Exception("Can't get php://input data", 1); // not error - can continue app without file
        }
        $headers = tao_helpers_Http::getHeaders();
        if (!isset($headers['Content-Length'])) {
            unset($putData);
            throw new common_Exception("Incorrect Http headers, must have Content-Length", 411); // 411 Length Required
        }

        if (!isset($headers['Content-Md5'])) {
            unset($putData);
            throw new common_Exception("Incorrect Http headers, must have Content-Md5", 449); // 449 Retry With
        }

        $tmpFileName = tempnam(sys_get_temp_dir(), 'stream_loader');
        if (!($fp = fopen($tmpFileName, "w"))) {
            unset($putData);
            throw new common_Exception("Can't write to tmp file", 500);
        }

        $totalWriteSize = 0;
        // Read the data a chunk at a time and write to the file
        while ($data = fread($putData, 262144)) {
            $chunkRead = strlen($data);
            if (($blockWriteSize = fwrite($fp, $data)) != $chunkRead) {
                unset($putData);
                throw new common_Exception("Can't write more to tmp file", 507);
            }

            $totalWriteSize += $blockWriteSize;
        }

        if (!fclose($fp)) {
            common_Logger::w('Could not close file fclose(' . $tmpFileName . ')', 'RestAPI');
        }
        unset($putData);

        // Check file length and MD5
        if ($totalWriteSize != $headers['Content-Length']) {
            throw new common_Exception("Wrong file size", 412); // Precondition Failed
        }
        if (md5_file($tmpFileName) != $headers['Content-Md5']) {
            throw new common_Exception("Wrong md5", 412);
        }

        return $tmpFileName;
    }
}
