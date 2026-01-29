<?php

/*
 * This file is part of the "Remote Work" plugin for Kimai.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\RemoteWorkBundle;

use App\Configuration\SystemConfiguration;

final class RemoteWorkConfiguration
{
    public function __construct(private readonly SystemConfiguration $systemConfiguration)
    {
    }

    public function isApprovalRequired(): bool
    {
        return $this->systemConfiguration->find('remote_work.approval_required') === true;
    }
}
