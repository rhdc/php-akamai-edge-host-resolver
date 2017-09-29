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

abstract class ResolverAbstract implements ResolverInterface
{
    protected static $edgeHostRegex;

    protected static $edgeStagingHostRegex;

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

    public function isEdgeHost($host)
    {
        if (!isset(static::$edgeHostRegex)) {
            static::$edgeHostRegex = '/'.preg_quote(static::EDGE_DOMAIN).'\.?$/';
        }

        return (bool) preg_match(static::$edgeHostRegex, $this->normalizeHost($host));
    }

    public function isEdgeStagingHost($host)
    {
        if (!isset(static::$edgeStagingHostRegex)) {
            static::$edgeStagingHostRegex = '/'.preg_quote(static::EDGE_STAGING_DOMAIN).'\.?$/';
        }

        return (bool) preg_match(static::$edgeStagingHostRegex, $this->normalizeHost($host));
    }

    public function resolveStaging($host, $resolve = ResolverInterface::RESOLVE_HOST)
    {
        $stagingHost = str_replace(
            ResolverInterface::EDGE_DOMAIN,
            ResolverInterface::EDGE_STAGING_DOMAIN,
            $this->resolve($host, ResolverInterface::RESOLVE_HOST)
        );

        return ($resolve === ResolverInterface::RESOLVE_HOST)
            ? $stagingHost
            : $this->resolve($stagingHost, $resolve);
    }
}
