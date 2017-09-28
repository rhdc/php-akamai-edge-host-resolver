<?php

/**
 * This file is part of the RHDC Akamai edge resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rhdc\Akamai\Edge\Resolver;

interface ResolverInterface
{
    const DOMAIN = 'akamaiedge.net';

    const STAGING_DOMAIN = 'akamaiedge-staging.net';

    const RESOLVE_HOST = 'host';

    const RESOLVE_IP = 'ip';

    public function normalizeHost($host);

    public function normalizeHosts(array $hosts);

    public function setResolvableHosts($resolvableHosts);

    public function getResolvableHosts();

    public function isResolvableHost($host);

    public function resolve($host, $resolve = self::RESOLVE_HOST);

    public function resolveStaging($host, $resolve = self::RESOLVE_HOST);
}
