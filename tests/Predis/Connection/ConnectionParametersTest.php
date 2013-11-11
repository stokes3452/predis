<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use \PHPUnit_Framework_TestCase as StandardTestCase;

/**
 * @todo ConnectionParameters::define();
 * @todo ConnectionParameters::undefine();
 */
class ParametersTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultValues()
    {
        $defaults = $this->getDefaultParametersArray();
        $parameters = new ConnectionParameters();

        $this->assertEquals($defaults['scheme'], $parameters->scheme);
        $this->assertEquals($defaults['host'], $parameters->host);
        $this->assertEquals($defaults['port'], $parameters->port);
        $this->assertEquals($defaults['timeout'], $parameters->timeout);
    }

    /**
     * @group disconnected
     */
    public function testIsSet()
    {
        $parameters = new ConnectionParameters();

        $this->assertTrue(isset($parameters->scheme));
        $this->assertFalse(isset($parameters->unknown));
    }

    /**
     * @group disconnected
     */
    public function testUserDefinedParameters()
    {
        $parameters = new ConnectionParameters(array('port' => 7000, 'custom' => 'foobar'));

        $this->assertTrue(isset($parameters->scheme));
        $this->assertSame('tcp', $parameters->scheme);

        $this->assertTrue(isset($parameters->port));
        $this->assertSame(7000, $parameters->port);

        $this->assertTrue(isset($parameters->custom));
        $this->assertSame('foobar', $parameters->custom);

        $this->assertFalse(isset($parameters->unknown));
        $this->assertNull($parameters->unknown);
    }

    /**
     * @group disconnected
     */
    public function testConstructWithUriString()
    {
        $defaults = $this->getDefaultParametersArray();

        $overrides = array(
            'port' => 7000,
            'database' => 5,
            'custom' => 'foobar',
        );

        $parameters = new ConnectionParameters($this->getParametersString($overrides));

        $this->assertEquals($defaults['scheme'], $parameters->scheme);
        $this->assertEquals($defaults['host'], $parameters->host);
        $this->assertEquals($overrides['port'], $parameters->port);

        $this->assertEquals($overrides['database'], $parameters->database);

        $this->assertTrue(isset($parameters->custom));
        $this->assertEquals($overrides['custom'], $parameters->custom);

        $this->assertFalse(isset($parameters->unknown));
        $this->assertNull($parameters->unknown);
    }

    /**
     * @group disconnected
     */
    public function testToArray()
    {
        $additional = array('port' => 7000, 'custom' => 'foobar');
        $parameters = new ConnectionParameters($additional);

        $this->assertEquals($this->getParametersArray($additional), $parameters->toArray());
    }

    /**
     * @group disconnected
     */
    public function testSerialization()
    {
        $parameters = new ConnectionParameters(array('port' => 7000, 'custom' => 'foobar'));
        $unserialized = unserialize(serialize($parameters));

        $this->assertEquals($parameters->scheme, $unserialized->scheme);
        $this->assertEquals($parameters->port, $unserialized->port);

        $this->assertTrue(isset($unserialized->custom));
        $this->assertEquals($parameters->custom, $unserialized->custom);

        $this->assertFalse(isset($unserialized->unknown));
        $this->assertNull($unserialized->unknown);
    }

    /**
     * @group disconnected
     */
    public function testParsingURI()
    {
        $uri = 'tcp://10.10.10.10:6400?timeout=0.5&persistent=1';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'port' => 6400,
            'timeout' => '0.5',
            'persistent' => '1',
        );

        $this->assertSame($expected, ConnectionParameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingUnixDomainURI()
    {
        $uri = 'unix:///tmp/redis.sock?timeout=0.5&persistent=1';

        $expected = array(
            'scheme' => 'unix',
            'host' => 'localhost',
            'path' => '/tmp/redis.sock',
            'timeout' => '0.5',
            'persistent' => '1',
        );

        $this->assertSame($expected, ConnectionParameters::parse($uri));
    }

    /**
     * @group disconnected
     */
    public function testParsingURIWithIncompletePairInQueryString()
    {
        $uri = 'tcp://10.10.10.10?persistent=1&foo=&bar';

        $expected = array(
            'scheme' => 'tcp',
            'host' => '10.10.10.10',
            'persistent' => '1',
            'foo' => '',
        );

        $this->assertSame($expected, ConnectionParameters::parse($uri));
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid parameters URI: tcp://invalid:uri
     */
    public function testParsingURIThrowOnInvalidURI()
    {
        ConnectionParameters::parse('tcp://invalid:uri');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return Array Default connection parameters.
     */
    protected function getDefaultParametersArray()
    {
        return array(
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 5.0,
        );
    }

    /**
     * Returns a named array with the default connection parameters merged with
     * the specified additional parameters.
     *
     * @param Array $additional Additional connection parameters.
     * @return Array Connection parameters.
     */
    protected function getParametersArray(Array $additional)
    {
        return array_merge($this->getDefaultParametersArray(), $additional);
    }

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param Array $parameters Array of connection parameters.
     * @return String URI string.
     */
    protected function getParametersString(Array $parameters)
    {
        $defaults = $this->getDefaultParametersArray();

        $scheme = isset($parameters['scheme']) ? $parameters['scheme'] : $defaults['scheme'];
        $host = isset($parameters['host']) ? $parameters['host'] : $defaults['host'];
        $port = isset($parameters['port']) ? $parameters['port'] : $defaults['port'];

        unset($parameters['scheme'], $parameters['host'], $parameters['port']);
        $uriString = "$scheme://$host:$port/?";

        foreach ($parameters as $k => $v) {
            $uriString .= "$k=$v&";
        }

        return $uriString;
    }
}
