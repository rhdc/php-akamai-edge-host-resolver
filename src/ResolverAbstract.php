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

use Rhdc\Akamai\Edge\Resolver\Exception\NotFoundException;

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

    abstract protected function resolveQuery($host, $resolve);

    abstract function resolveResultItemValue($resultItem, $resolve);

    public function resolve($host, $resolve = ResolverInterface::RESOLVE_HOST, $staging = false)
    {
        if ($staging) {
            // Get prod edge host, translate to staging edge host, and reset
            // $host to staging edge host
            $host = preg_replace(
                static::$edgeHostRegex,
                ResolverInterface::EDGE_STAGING_DOMAIN,
                $this->resolve($host, ResolverInterface::RESOLVE_HOST)
            );

            if ($resolve === ResolverInterface::RESOLVE_HOST) {
                return $host;
            }
        }

        $result = array();

        foreach ($this->resolveQuery($host, $resolve) as $index => $resultItem) {
            $host = $this->resolveResultItemValue($resultItem, ResolverInterface::RESOLVE_HOST);

            if (isset($host) && $this->isEdgeHost($host, $staging)) {
                if ($resolve === ResolverInterface::RESOLVE_HOST) {
                    $result[] = $host;
                } else {
                    $resultItemValue = $this->resolveResultItemValue($resultItem, $resolve);

                    if (isset($resultItemValue)) {
                        $result[] = $resultItemValue;
                    }
                }
            }
        }

        if (empty($result)) {
            throw new NotFoundException(sprintf(
                'Akamai edge not found for host "%s"',
                $host
            ));
        }

        return $resolve === ResolverInterface::RESOLVE_HOST
            ? $result[0]
            : $result;
    }
}
