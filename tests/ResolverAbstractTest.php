<?php

/**
 * This file is part of the RHDC Akamai edge resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rhdc\Akamai\Edge\Resolver\Test;

use PHPUnit\Framework\TestCase;
use Rhdc\Akamai\Edge\Resolver\ResolverAbstract;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class ResolverAbstractTest extends TestCase
{
    /** @var ResolverAbstract **/
    protected $stub;

    protected function setUp()
    {
        // @todo Change to `ResolverAbstract::class` when PHP 5.3 and 5.4
        //       support is dropped
        $this->stub = $this->getMockForAbstractClass(
            'Rhdc\\Akamai\\Edge\\Resolver\\ResolverAbstract'
        );
    }

    /**
     * @dataProvider normalizeHostProvider
     */
    public function testNormalizeHost($rawHost, $expectedHost)
    {
        $this->assertEquals($expectedHost, $this->stub->normalizeHost($rawHost));
    }

    public function normalizeHostProvider()
    {
        return array(
            array('www.akamai.com', 'www.akamai.com'),
            array('WwW.AkAmAi.CoM', 'www.akamai.com'),
            array(' www.akamai.com ', 'www.akamai.com'),
            array(PHP_EOL.'www.akamai.com'.PHP_EOL, 'www.akamai.com'),
            array("\twww.akamai.com\t", 'www.akamai.com'),
            array(" \t".PHP_EOL, ''),
        );
    }

    public function testNormalizeHosts()
    {
        $rawHosts = array_map(function ($normalizeHostProviderItem) {
            return $normalizeHostProviderItem[0];
        }, $this->normalizeHostProvider());

        $expectedNormalizedHosts = array_filter(array_map(function ($normalizeHostProviderItem) {
            return $normalizeHostProviderItem[1];
        }, $this->normalizeHostProvider()));

        $actualNormalizedHosts = $this->stub->normalizeHosts($rawHosts);

        $this->assertEquals($expectedNormalizedHosts, $actualNormalizedHosts);
    }

    /**
     * @dataProvider resolvableHostsProvider
     */
    public function testGetSetResolvableHosts($resolvableHosts)
    {
        // Assert beginning state
        $beginningState = $this->stub->getResolvableHosts();
        $this->assertTrue(is_array($beginningState));
        $this->assertEmpty($beginningState);

        // Assert new state
        $this->stub->setResolvableHosts($resolvableHosts);
        $newStateExpected = $this->stub->normalizeHosts((array) $resolvableHosts);
        $newStateActual = $this->stub->getResolvableHosts();
        $this->assertTrue(is_array($newStateActual));
        $this->assertEquals($newStateExpected, $newStateActual);
    }

    public function resolvableHostsProvider()
    {
        $normalizeHostsArray = array_map(function ($normalizeHostProviderItem) {
            return $normalizeHostProviderItem[0];
        }, $this->normalizeHostProvider());

        return array(
            // Array with items requiring normalization
            array($normalizeHostsArray),
            // String
            array('www.akamai.com'),
        );
    }

    /**
     * @dataProvider isResolvableHostProvider
     */
    public function testIsResolvableHost($resolvableHosts, $host, $expectedResult)
    {
        $this->stub->setResolvableHosts($resolvableHosts);

        $this->assertSame($expectedResult, $this->stub->isResolvableHost($host));
    }

    public function isResolvableHostProvider()
    {
        $resolvableHosts = array(
            'akamai.com',
            'www.akamai.com',
        );

        return array(
            array($resolvableHosts, 'akamai.com', true),
            array($resolvableHosts, 'www.akamai.com', true),
            array($resolvableHosts, 'non-resolvable.akamai.com', false),
            array(array(), 'akamai.com', true),
            array(array(), 'www.akamai.com', true),
            array(array(), 'non-resolvable.akamai.com', true),
        );
    }

    /**
     * @dataProvider isEdgeHostProvider
     */
    public function testIsEdgeHost($host, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->stub->isEdgeHost($host));
    }

    public function isEdgeHostProvider()
    {
        return array(
            array(ResolverInterface::EDGE_DOMAIN, true),
            array(ResolverInterface::EDGE_DOMAIN.'.', true),
            array('test.'.ResolverInterface::EDGE_DOMAIN, true),
            array('test.'.ResolverInterface::EDGE_DOMAIN.'.', true),
            array(" \t".PHP_EOL.ResolverInterface::EDGE_DOMAIN." \t".PHP_EOL, true),
            array(" \t".PHP_EOL.ResolverInterface::EDGE_DOMAIN.". \t".PHP_EOL, true),
            array(ResolverInterface::EDGE_STAGING_DOMAIN, false),
            array(ResolverInterface::EDGE_STAGING_DOMAIN.'.', false),
            array('www.akamai.com', false),
        );
    }

    /**
     * @dataProvider isEdgeStagingHostProvider
     */
    public function testIsEdgeStagingHost($host, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->stub->isEdgeHost($host, true));
    }

    public function isEdgeStagingHostProvider()
    {
        return array(
            array(ResolverInterface::EDGE_STAGING_DOMAIN, true),
            array(ResolverInterface::EDGE_STAGING_DOMAIN.'.', true),
            array('test.'.ResolverInterface::EDGE_STAGING_DOMAIN, true),
            array('test.'.ResolverInterface::EDGE_STAGING_DOMAIN.'.', true),
            array(" \t".PHP_EOL.ResolverInterface::EDGE_STAGING_DOMAIN." \t".PHP_EOL, true),
            array(" \t".PHP_EOL.ResolverInterface::EDGE_STAGING_DOMAIN.". \t".PHP_EOL, true),
            array(ResolverInterface::EDGE_DOMAIN, false),
            array(ResolverInterface::EDGE_DOMAIN.'.', false),
            array('www.akamai.com', false),
        );
    }
}
