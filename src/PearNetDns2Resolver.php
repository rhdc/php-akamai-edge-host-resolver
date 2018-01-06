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
use Rhdc\Akamai\Edge\Resolver\Exception\NotFoundException;
use Rhdc\Akamai\Edge\Resolver\Exception\ResolveException;
use Rhdc\Akamai\Edge\Resolver\ResolverAbstract;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class PearNetDns2Resolver extends ResolverAbstract
{
    const RESULT_KEY_HOST = 'name';
    const RESULT_KEY_IP_V4 = 'address';
    const RESULT_KEY_IP_V6 = 'address';

    /** @var Net_DNS2_Resolver */
    protected $netDns2Resolver;

    public function __construct(Net_DNS2_Resolver $netDns2Resolver)
    {
        parent::__construct();
        $this->netDns2Resolver = $netDns2Resolver;
    }

    public function resolveKeys($resolve)
    {
        switch ($resolve) {
            case static::RESOLVE_HOST:
                return array('A', static::RESULT_KEY_HOST);
            case static::RESOLVE_IP_V4:
                return array('A', static::RESULT_KEY_IP_V4);
            case static::RESOLVE_IP_V6:
                return array('AAAA', static::RESULT_KEY_IP_V6);
            default:
                throw new ResolveException(sprintf(
                    'Invalid resolve type: "%s"',
                    $resolve
                ));
        }
    }

    protected function query($host, $type)
    {
        try {
            return $this->netDns2Resolver->query($host, $type);
        } catch (Exception $e) {
            throw new ResolveException($e->getMessage(), $e->getCode(), $e);
        }
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

        list($queryType, $queryAnswerKey) = $this->resolveKeys($resolve);

        $result = $this->query($host, $queryType);

        // For PHP 5.3 ("PHP Fatal error:  Cannot access static:: when no class scope is active")
        //
        // See:
        // * https://travis-ci.org/rhdc/php-akamai-edge-resolver/jobs/325702874
        // * https://travis-ci.org/rhdc/php-akamai-edge-resolver/jobs/325702875
        $resultKeyHost = static::RESULT_KEY_HOST;

        // For PHP 5.3 ("PHP Fatal error:  Using $this when not in object context")
        //
        // See:
        // * https://travis-ci.org/rhdc/php-akamai-edge-resolver/jobs/325704901
        // * https://travis-ci.org/rhdc/php-akamai-edge-resolver/jobs/325704902
        $isEdgeHostCallback = array($this, 'isEdgeHost');

        $resultNormalized = array_filter(array_map(function ($resultItem) use (
            $resultKeyHost,
            $isEdgeHostCallback,
            $staging,
            $queryAnswerKey
        ) {
            $host = isset($resultItem->$resultKeyHost)
                ? $resultItem->$resultKeyHost
                : null;

            if (!isset($host) || !call_user_func($isEdgeHostCallback, $host, $staging)) {
                return null;
            }

            return isset($resultItem->$queryAnswerKey) ? $resultItem->$queryAnswerKey : null;
        }, $result->answer));

        if (empty($resultNormalized)) {
            throw new NotFoundException(sprintf(
                'No Akamai edge host found for "%s"',
                $host
            ));
        }

        return $resolve === ResolverInterface::RESOLVE_HOST
            ? reset($resultNormalized)
            : array_values($resultNormalized);
    }
}
