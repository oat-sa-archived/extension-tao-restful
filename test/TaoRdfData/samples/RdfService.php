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

namespace oat\taoRestAPI\test\TaoRdfData\samples;


use tao_actions_form_Generis;
use tao_models_classes_ClassService;

class RdfService extends tao_models_classes_ClassService
{
    private $rdfClass;
    
    public function __construct(){
        parent::__construct();
        $this->rdfClass = new \core_kernel_classes_Class(tao_actions_form_Generis::DEFAULT_TOP_CLASS);
    }
    
    public function getRootClass()
    {
        return $this->rdfClass;
    }
}
