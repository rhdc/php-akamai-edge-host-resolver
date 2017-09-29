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
    const EDGE_DOMAIN = 'akamaiedge.net';

    const EDGE_STAGING_DOMAIN = 'akamaiedge-staging.net';

    const RESOLVE_HOST = 'host';

    const RESOLVE_IP_V4 = 'ip-v4';

    const RESOLVE_IP_V6 = 'ip-v6';

    public function normalizeHost($host);

    public function normalizeHosts(array $hosts);

    public function setResolvableHosts($resolvableHosts);

    public function getResolvableHosts();

    public function isResolvableHost($host);

    public function isEdgeHost($host);

    public function isEdgeStagingHost($host);

    public function resolve($host, $resolve = self::RESOLVE_HOST);

    public function resolveStaging($host, $resolve = self::RESOLVE_HOST);
}
