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
use Rhdc\Akamai\Edge\Resolver\Exception\NotFoundException;
use Rhdc\Akamai\Edge\Resolver\NativeResolver;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class NativeResolverTest extends TestCase
{
    /**
     * @return NativeResolver
     */
    protected function createResolver()
    {
        $resolver = $this->getMockBuilder('Rhdc\\Akamai\\Edge\\Resolver\\NativeResolver')
            ->setMethods(array('dnsGetRecord'))
            ->getMock();

        $resolver->method('dnsGetRecord')
            ->will($this->returnCallback(array($this, 'createResolverDnsGetRecordCallback')));

        return $resolver;
    }

    public function createResolverDnsGetRecordCallback($hostname, $type)
    {
        switch ($hostname) {
            case 'not.found':
                return array(
                    array(
                        'host' => 'not.edge',
                    ),
                );

            case 'default.'.ResolverInterface::EDGE_STAGING_DOMAIN:
                return $type === DNS_AAAA
                    // IP v6
                    ? array(
                        array(
                            'host' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'ipv6' => '::2',
                        ),
                        array(
                            'host' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'ipv6' => '::3',
                        ),
                    )
                    // IP v4
                    : array(
                        array(
                            'host' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'ip' => '1.1.1.1',
                        ),
                    );

            default:
                return $type === DNS_AAAA
                    // IP v6
                    ? array(
                        array(
                            'host' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'ipv6' => '::0',
                        ),
                        array(
                            'host' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'ipv6' => '::1',
                        ),
                    )
                    // IP v4
                    : array(
                        array(
                            'host' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'ip' => '0.0.0.0',
                        ),
                    );
        }
    }

    /**
     * @dataProvider resolveKeysProvider
     */
    public function testResolveKeys($resolve, $expectedResult)
    {
        $resolver = $this->createResolver();
        $this->assertSame($expectedResult, $resolver->resolveKeys($resolve));
    }

    public function resolveKeysProvider()
    {
        return array(
            array(ResolverInterface::RESOLVE_HOST, array(DNS_A, NativeResolver::RESULT_KEY_HOST)),
            array(ResolverInterface::RESOLVE_IP_V4, array(DNS_A, NativeResolver::RESULT_KEY_IP_V4)),
            array(ResolverInterface::RESOLVE_IP_V6, array(DNS_AAAA, NativeResolver::RESULT_KEY_IP_V6)),
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testResolveKeysException()
    {
        $this->createResolver()->resolveKeys('_____invalid_____');
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve($resolve, $staging, $expected)
    {
        $resolver = $this->createResolver();
        $actual = $resolver->resolve('host.does.not.matter', $resolve, $staging);

        $this->assertEquals($expected, $actual);
    }

    public function resolveProvider()
    {
        return array(
            // Prod
            array(ResolverInterface::RESOLVE_HOST, false, 'default.'.ResolverInterface::EDGE_DOMAIN),
            array(ResolverInterface::RESOLVE_IP_V4, false, array('0.0.0.0')),
            array(ResolverInterface::RESOLVE_IP_V6, false, array('::0', '::1')),
            // Staging
            array(ResolverInterface::RESOLVE_HOST, true, 'default.'.ResolverInterface::EDGE_STAGING_DOMAIN),
            array(ResolverInterface::RESOLVE_IP_V4, true, array('1.1.1.1')),
            array(ResolverInterface::RESOLVE_IP_V6, true, array('::2', '::3')),
        );
    }

    /**
     * @dataProvider resolveExceptionProvider
     * @expectedException Rhdc\Akamai\Edge\Resolver\Exception\NotFoundException
     */
    public function testResolveException($staging)
    {
        $this->createResolver()->resolve('not.found', ResolverInterface::RESOLVE_HOST, $staging);
    }

    public function resolveExceptionProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }

    // public function testResolveWithNetwork()
    // {
    //     $resolver = $this->createResolver();
    //     $resolver->resolve('something');
    // }
}
