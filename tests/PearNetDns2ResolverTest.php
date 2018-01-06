<?php
/**
 * This file is part of the RHDC Akamai edge resolver package.
 *
 * (c) Shawn Iwinski <siwinski@redhat.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
namespace Rhdc\Akamai\Edge\Resolver\Test;

use Net_DNS2_Resolver;
use PHPUnit\Framework\TestCase;
use Rhdc\Akamai\Edge\Resolver\Exception\NotFoundException;
use Rhdc\Akamai\Edge\Resolver\Exception\ResolveException;
use Rhdc\Akamai\Edge\Resolver\PearNetDns2Resolver;
use Rhdc\Akamai\Edge\Resolver\ResolverInterface;

class PearNetDns2ResolverTest extends TestCase
{
    /** @var PearNetDns2Resolver */
    protected $resolver;

    protected function setUp()
    {
        $netDns2Resolver = $this->getMockBuilder('Net_DNS2_Resolver')
            ->getMock();

        $netDns2Resolver->method('query')
            ->will($this->returnCallback(array($this, 'createResolverQueryCallback')));

        $this->resolver = new PearNetDns2Resolver($netDns2Resolver);
    }

    public function createResolverQueryCallback($host, $queryType)
    {
        switch ($host) {
            case 'not.found':
                $answer = array(
                    (object) array(
                        'name' => 'not.edge',
                    ),
                );
                break;

            case 'default.'.ResolverInterface::EDGE_STAGING_DOMAIN:
                $answer = ($queryType === 'AAAA')
                    // IP v6
                    ? array(
                        (object) array(
                            'name' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'address' => '::2',
                        ),
                        (object) array(
                            'name' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'address' => '::3',
                        ),
                    )
                    // IP v4
                    : array(
                        (object) array(
                            'name' => 'staging.'.ResolverInterface::EDGE_STAGING_DOMAIN,
                            'address' => '1.1.1.1',
                        ),
                    );
                break;

            default:
                $answer = ($queryType === 'AAAA')
                    // IP v6
                    ? array(
                        (object) array(
                            'name' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'address' => '::0',
                        ),
                        (object) array(
                            'name' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'address' => '::1',
                        ),
                    )
                    // IP v4
                    : array(
                        (object) array(
                            'name' => 'default.'.ResolverInterface::EDGE_DOMAIN,
                            'address' => '0.0.0.0',
                        ),
                    );
        }

        return (object) array('answer' => $answer);
    }

    /**
     * @dataProvider resolveKeysProvider
     */
    public function testResolveKeys($resolve, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->resolver->resolveKeys($resolve));
    }

    public function resolveKeysProvider()
    {
        return array(
            array(ResolverInterface::RESOLVE_HOST, array('A', PearNetDns2Resolver::RESULT_KEY_HOST)),
            array(ResolverInterface::RESOLVE_IP_V4, array('A', PearNetDns2Resolver::RESULT_KEY_IP_V4)),
            array(ResolverInterface::RESOLVE_IP_V6, array('AAAA', PearNetDns2Resolver::RESULT_KEY_IP_V6)),
        );
    }

    /**
     * @expectedException Rhdc\Akamai\Edge\Resolver\Exception\ResolveException
     */
    public function testResolveKeysException()
    {
        $this->resolver->resolveKeys('_____invalid_____');
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve($resolve, $staging, $expected)
    {
        $actual = $this->resolver->resolve('host.does.not.matter.because.responses.are.mocked', $resolve, $staging);
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
    public function testResolveNotFoundException($staging)
    {
        $this->resolver->resolve('not.found', ResolverInterface::RESOLVE_HOST, $staging);
    }

    public function resolveExceptionProvider()
    {
        return array(
            array(false),
            array(true),
        );
    }
}
