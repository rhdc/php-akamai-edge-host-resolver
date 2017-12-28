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
use Rhdc\Akamai\Edge\Resolver\Exception\ResolveException;
use Rhdc\Akamai\Edge\Resolver\ResolverAbstract;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class NativeResolver extends ResolverAbstract
{
    const RESULT_KEY_HOST = 'host';
    const RESULT_KEY_IP_V4 = 'ip';
    const RESULT_KEY_IP_V6 = 'ipv6';

    public function resolveKeys($resolve)
    {
        switch($resolve) {
            case static::RESOLVE_HOST:
                return array(DNS_A, static::RESULT_KEY_HOST);
            case static::RESOLVE_IP_V4:
                return array(DNS_A, static::RESULT_KEY_IP_V4);
            case static::RESOLVE_IP_V6:
                return array(DNS_AAAA, static::RESULT_KEY_IP_V6);
            default:
                throw new \RuntimeException(sprintf(
                    'Invalid resolve type: "%s"',
                    $resolve
                ));
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function dnsGetRecord($hostname, $type)
    {
        $result = dns_get_record($hostname, $type);

        if ($result === false) {
            throw new ResolveException(sprintf(
                'dns_get_record("%s", %d) failed',
                $hostname,
                $type
            ));
        }

        return $result;
    }

    public function resolve($host, $resolve = ResolverInterface::RESOLVE_HOST, $staging = false)
    {
        if ($staging) {
            // Get prod edge host, translate to staging edge host, and reset
            // $host to staging edge host
            $host = str_replace(
                ResolverInterface::EDGE_DOMAIN,
                ResolverInterface::EDGE_STAGING_DOMAIN,
                $this->resolve($host, ResolverInterface::RESOLVE_HOST)
            );

            if ($resolve === ResolverInterface::RESOLVE_HOST) {
                return $host;
            }
        }

        list($dnsGetRecordType, $dnsGetRecordResultKey) = $this->resolveKeys($resolve);

        $result = $this->dnsGetRecord($host, $dnsGetRecordType);

        $resultNormalized = array_filter(array_map(function ($resultItem) use ($dnsGetRecordResultKey, $staging) {
            $host = isset($resultItem[static::RESULT_KEY_HOST])
                ? $resultItem[static::RESULT_KEY_HOST]
                : null;

            if (!isset($host) || !$this->isEdgeHost($host, $staging)) {
                return null;
            }

            return isset($resultItem[$dnsGetRecordResultKey]) ? $resultItem[$dnsGetRecordResultKey] : null;
        }, $result));

        if (empty($resultNormalized)) {
            throw new NotFoundException(sprintf(
                'No Akamai edge host found for "%s"',
                $host
            ));
        }

        return $resolve === ResolverInterface::RESOLVE_HOST
            ? $resultNormalized[0]
            : $resultNormalized;
    }
}
