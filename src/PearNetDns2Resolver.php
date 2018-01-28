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

use Net_DNS2_Resolver;
use Rhdc\Akamai\Edge\Resolver\Exception\ResolveException;
use Rhdc\Akamai\Edge\Resolver\ResolverAbstract;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class PearNetDns2Resolver extends ResolverAbstract
{
    /** @var Net_DNS2_Resolver */
    protected $netDns2Resolver;

    public function __construct(Net_DNS2_Resolver $netDns2Resolver)
    {
        parent::__construct();
        $this->netDns2Resolver = $netDns2Resolver;
    }

    protected function resolveQuery($host, $resolve)
    {
        switch ($resolve) {
            case ResolverInterface::RESOLVE_HOST:
                $netDns2ResolverQueryType = 'A';
                break;
            case ResolverInterface::RESOLVE_IP_V4:
                $netDns2ResolverQueryType = 'A';
                break;
            case ResolverInterface::RESOLVE_IP_V6:
                $netDns2ResolverQueryType = 'AAAA';
                break;
            default:
                throw new ResolveException(sprintf(
                    'Invalid resolve type: "%s"',
                    $resolve
                ));
        }

        try {
            $result = $this->netDns2Resolver->query($host, $netDns2ResolverQueryType);
            return $result->answer;
        } catch (Exception $e) {
            throw new ResolveException($e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function resolveResultItemValue($resultItem, $resultItemKey)
    {
        switch ($resultItemKey) {
            case static::RESULT_ITEM_KEY_HOST:
                $resultItemKey = 'name';
                break;
            case static::RESULT_ITEM_KEY_IP_V4:
                $resultItemKey = 'address';
                break;
            case static::RESULT_ITEM_KEY_IP_V6:
                $resultItemKey = 'address';
                break;
            case static::RESULT_ITEM_KEY_TTL:
                $resultItemKey = 'ttl';
                break;
            default:
                throw new ResolveException(sprintf(
                    'Invalid result item key: "%s"',
                    $resolve
                ));
        }

        return isset($resultItem->$resultItemKey) ? $resultItem->$resultItemKey : null;
    }
}
