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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

return array(
    'name' => 'taoRestAPI',
    'label' => 'Rest API Tool',
    'description' => 'RestAPI extension for tao project',
    'license' => 'GPL-2.0',
    'version' => '0.0.1',
    'author' => 'Open Assessment Technologies SA',
    'requires' => array(
        'tao' => '>=2.17.0',
    ),
    'acl' => array(
     //   array('grant', 'http://www.tao.lu/Ontologies/TAO.rdf#taoRestApiRole', array('ext'=>'taoRestApi')),
    ),
    'install' => array(
    ),
    'uninstall' => array(),
    'routes' => array(
        '/taoRestAPI' => 'oat\\taoRestAPI\\controller',
        'rest-route' => array(
            'class' => 'oat\\taoRestAPI\\model\\route\\ResourceRoute',
        ),
    ),
    'constants' => array(
        "DIR_VIEWS" => dirname(__FILE__) . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR,
        'BASE_URL' => ROOT_URL . 'taoRestAPI/',
        'BASE_WWW' => ROOT_URL . 'taoRestAPI/views/'
    ),
);
