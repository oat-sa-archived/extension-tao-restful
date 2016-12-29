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
 */

namespace oat\taoRestAPI\model\v1\dataEncoder;


use oat\taoRestAPI\exception\HttpRequestException;
use oat\taoRestAPI\model\DataEncoderInterface;

class ZipEncoder implements DataEncoderInterface
{
    public function encode( $path )
    {
        if (!is_string($path) || !file_exists($path) || !is_readable($path)) {
            throw new HttpRequestException(__('Invalid data for Zip encoder. Wrong HTTP Accept header.'), 400);
        }

        \tao_helpers_Http::returnFile($path, false);
    }
    
    public function getContentType()
    {
        return 'application/zip';
    }
}
