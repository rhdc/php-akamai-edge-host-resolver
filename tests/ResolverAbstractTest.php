<?php

/**
 * This file is part of the RHDC Akamai edge host resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rhdc\Akamai\Edge\HostResolver\Test;

use PHPUnit\Framework\TestCase;
use Rhdc\Akamai\Edge\HostResolver\ResolverAbstract;
use Rhdc\Akamai\Edge\HostResolver\ResolverInterface;

class ResolverAbstractTest extends TestCase
{
    /** @var ResolverAbstract **/
    protected $stub;

    protected function setUp()
    {
        // @todo Change to `ResolverAbstract::class` when PHP 5.3 and 5.4
        //       support is dropped
        $this->stub = $this->getMockForAbstractClass(
            'Rhdc\\Akamai\\Edge\\HostResolver\\ResolverAbstract'
        );

        $this->stub
            ->method('resolve')
            ->will($this->returnArgument(0));

        $this->stub
            ->method('resolveIp')
            ->willReturn('0.0.0.0');
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
     * @dataProvider resolveStagingHostProvider
     */
    public function testResolveStagingHost($host, $expected)
    {
        $this->assertEquals($expected, $this->stub->resolveStaging($host));
    }

    public function resolveStagingHostProvider()
    {
        return array(
            array('test.'.ResolverInterface::DOMAIN, 'test.'.ResolverInterface::STAGING_DOMAIN),
            array('test.'.ResolverInterface::DOMAIN.'.', 'test.'.ResolverInterface::STAGING_DOMAIN.'.'),
            array('www.akamai.com', 'www.akamai.com'),
        );
    }

    public function testResolveStagingIp()
    {
        $this->assertEquals(
            '0.0.0.0',
            $this->stub->resolveStaging('www.akamai.com', ResolverInterface::RESOLVE_IP)
        );
    }
}
