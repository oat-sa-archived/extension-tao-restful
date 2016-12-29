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

namespace oat\taoRestAPI\proxy;


use common_http_Request;
use common_session_RestSession;
use oat\oatbox\user\LoginFailedException;
use oat\taoRestAPI\model\AuthenticationInterface;
use tao_models_classes_HttpBasicAuthAdapter;

class BasicAuthentication implements AuthenticationInterface
{
    private $adapter;

    public function __construct()
    {
        $this->adapter = new tao_models_classes_HttpBasicAuthAdapter(common_http_Request::currentRequest());
    }

    public function authenticate()
    {
        try {
            $user = $this->adapter->authenticate();
            // login
            $session = new common_session_RestSession($user);
            \common_session_SessionManager::startSession($session);
        } catch (LoginFailedException $e) {
            //throw new RestApiException($e->getUserMessage(), 401);
            $this->requireLogin();
        }
    }

    private function requireLogin()
    {
        header('HTTP/1.0 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="' . GENERIS_INSTANCE_NAME . '"');
        exit(0);
    }
}
