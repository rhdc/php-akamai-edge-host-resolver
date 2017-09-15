<?php

/**
 * This file is part of the RHDC Akamai edge host resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rhdc\Akamai\Edge\HostResolver;

abstract class ResolverAbstract implements ResolverInterface
{
    /** @var string[] */
    protected $resolvableHosts = array();

    public function normalizeHost($host)
    {
        return strtolower(trim($host));
    }

    public function normalizeHosts(array $hosts)
    {
        return array_filter(array_map(array($this, 'normalizeHost'), $hosts));
    }

    public function setResolvableHosts($resolvableHosts)
    {
        $this->resolvableHosts = $this->normalizeHosts((array) $resolvableHosts);
    }

    public function getResolvableHosts()
    {
        return $this->resolvableHosts;
    }

    public function isResolvableHost($host)
    {
        return empty($this->resolvableHosts)
            || in_array($this->normalizeHost($host), $this->resolvableHosts);
    }

    public function resolveStaging($host, $resolve = ResolverInterface::RESOLVE_HOST)
    {
        $stagingHost = str_replace(
            ResolverInterface::DOMAIN,
            ResolverInterface::STAGING_DOMAIN,
            $this->resolve($host)
        );

        return (ResolverInterface::RESOLVE_IP === $resolve)
            ? $this->resolveIp($stagingHost)
            : $stagingHost;
    }

    abstract protected function resolveIp($host);
}
