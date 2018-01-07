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

use Rhdc\Akamai\Edge\Resolver\Exception\ResolveException;
use Rhdc\Akamai\Edge\Resolver\ResolverAbstract;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class NativeResolver extends ResolverAbstract
{
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

    protected function resolveQuery($host, $resolve)
    {
        switch ($resolve) {
            case ResolverInterface::RESOLVE_HOST:
                $dnsGetRecordType = DNS_A;
                break;
            case ResolverInterface::RESOLVE_IP_V4:
                $dnsGetRecordType = DNS_A;
                break;
            case ResolverInterface::RESOLVE_IP_V6:
                $dnsGetRecordType = DNS_AAAA;
                break;
            default:
                throw new ResolveException(sprintf(
                    'Invalid resolve type: "%s"',
                    $resolve
                ));
        }

        return $this->dnsGetRecord($host, $dnsGetRecordType);
    }

    protected function resolveResultItemValue($resultItem, $resolve)
    {
        switch ($resolve) {
            case ResolverInterface::RESOLVE_HOST:
                $resultItemKey = 'host';
                break;
            case ResolverInterface::RESOLVE_IP_V4:
                $resultItemKey = 'ip';
                break;
            case ResolverInterface::RESOLVE_IP_V6:
                $resultItemKey = 'ipv6';
                break;
            default:
                throw new ResolveException(sprintf(
                    'Invalid resolve type: "%s"',
                    $resolve
                ));
        }

        return isset($resultItem[$resultItemKey]) ? $resultItem[$resultItemKey] : null;
    }
}
