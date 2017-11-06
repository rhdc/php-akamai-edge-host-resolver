<?php

/**
 * This file is part of the RHDC Akamai edge resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Rhdc\Akamai\Edge\Resolver;

abstract class ResolverAbstract implements ResolverInterface
{
    protected static $edgeHostRegex;

    protected static $edgeStagingHostRegex;

    /** @var string[] */
    protected $resolvableHosts = array();

    public function __construct()
    {
        if (!isset(static::$edgeHostRegex)) {
            static::$edgeHostRegex = '/'.preg_quote(static::EDGE_DOMAIN).'\.?$/';
        }

        if (!isset(static::$edgeStagingHostRegex)) {
            static::$edgeStagingHostRegex = '/'.preg_quote(static::EDGE_STAGING_DOMAIN).'\.?$/';
        }
    }

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

    public function isEdgeHost($host, $staging = false)
    {
        return (bool) preg_match(
            $staging ? static::$edgeStagingHostRegex : static::$edgeHostRegex,
            $this->normalizeHost($host)
        );
    }
}
