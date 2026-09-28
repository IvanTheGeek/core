<?php

/*
 * Copyright (C) 2017-2018 EURO-LOG AG
 * Copyright (c) 2019 Deciso B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace OPNsense\Monit\Api;

use OPNsense\Base\ApiMutableServiceControllerBase;
use OPNsense\Core\Backend;

/**
 * Class ServiceController
 * @package OPNsense\Monit
 */
class ServiceController extends ApiMutableServiceControllerBase
{
    protected static $internalServiceClass = '\OPNsense\Monit\Monit';
    protected static $internalServiceEnabled = 'general.enabled';
    protected static $internalServiceTemplate = 'OPNsense/Monit';
    protected static $internalServiceName = 'monit';

    /**
     * reconfigure monit, report why it did not start or reload
     * @return array
     */
    public function reconfigureAction()
    {
        if (!$this->request->isPost()) {
            return parent::reconfigureAction();
        }
        $was_running = $this->statusAction()['status'] == 'running';
        $result = parent::reconfigureAction();
        if ($this->serviceEnabled()) {
            $backend = new Backend();
            if ($this->statusAction()['status'] != 'running') {
                /* monit refuses to start with an invalid control file */
                $failed = true;
            } else {
                /* rc refuses to reload an invalid control file, the running monit keeps the old one */
                $failed = $was_running && trim($backend->configdRun('monit reload')) != 'OK';
            }
            if ($failed) {
                $result = ['status' => 'failed', 'status_msg' => trim($backend->configdRun('monit check'))];
            }
        }
        return $result;
    }

     /**
      * avoid restarting Monit on reconfigure
      */
    protected function reconfigureForceRestart()
    {
        return 0;
    }
}
