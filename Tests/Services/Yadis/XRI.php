<?php

/**
 * XRI resolution / handling tests.
 *
 * @package Yadis
 * @author JanRain, Inc. <openid@janrain.com>
 * @copyright 2005 Janrain, Inc.
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */

require_once "PHPUnit.php";
require_once "Services/Yadis/XRI.php";

class Tests_Services_Yadis_XriDiscoveryTestCase extends PHPUnit_TestCase {
    function runTest()
    {
        $this->assertEquals(
               Services_Yadis_identifierScheme('=john.smith'), 'XRI');

        $this->assertEquals(
               Services_Yadis_identifierScheme('@smiths/john'), 'XRI');

        $this->assertEquals(
               Services_Yadis_identifierScheme('smoker.myopenid.com'), 'URI');

        $this->assertEquals(
               Services_Yadis_identifierScheme('xri://=john'), 'XRI');
    }
}

class Tests_Services_Yadis_XriEscapingTestCase extends PHPUnit_TestCase {
    function test_escaping_percents()
    {
        $this->assertEquals(Services_Yadis_escapeForIRI('@example/abc%2Fd/ef'),
                            '@example/abc%252Fd/ef');
    }

    function runTest()
    {
        // no escapes
        $this->assertEquals('@example/foo/(@bar)',
               Services_Yadis_escapeForIRI('@example/foo/(@bar)'));

        // escape slashes
        $this->assertEquals('@example/foo/(@bar%2Fbaz)',
               Services_Yadis_escapeForIRI('@example/foo/(@bar/baz)'));

        $this->assertEquals('@example/foo/(@bar%2Fbaz)/(+a%2Fb)',
               Services_Yadis_escapeForIRI('@example/foo/(@bar/baz)/(+a/b)'));

        // escape query ? and fragment
        $this->assertEquals('@example/foo/(@baz%3Fp=q%23r)?i=j#k',
               Services_Yadis_escapeForIRI('@example/foo/(@baz?p=q#r)?i=j#k'));
    }
}

class Tests_Services_Yadis_ProxyQueryTestCase extends PHPUnit_TestCase {
    function setUp()
    {
        $this->proxy_url = 'http://xri.example.com/';
        $this->fetcher = Services_Yadis_Yadis::getHTTPFetcher();
        $this->proxy = new Services_Yadis_ProxyResolver($fetcher,
                                                        $this->proxy_url);
        $this->servicetype = 'xri://+i-service*(+forwarding)*($v*1.0)';
        $this->servicetype_enc = 'xri%3A%2F%2F%2Bi-service%2A%28%2Bforwarding%29%2A%28%24v%2A1.0%29';
    }

    function runTest()
    {
        $st = $this->servicetype;
        $ste = $this->servicetype_enc;
        $args_esc = "_xrd_r=application%2Fxrds%2Bxml&_xrd_t=" . $ste;
        $h = $this->proxy_url;
        $this->assertEquals($h . '=foo?' . $args_esc,
                            $this->proxy->queryURL('=foo', $st));
        $this->assertEquals($h . '=foo/bar?baz&' . $args_esc,
                            $this->proxy->queryURL('=foo/bar?baz', $st));
        $this->assertEquals($h . '=foo/bar?baz=quux&' . $args_esc,
                            $this->proxy->queryURL('=foo/bar?baz=quux', $st));
        $this->assertEquals($h . '=foo/bar?mi=fa&so=la&' . $args_esc,
                            $this->proxy->queryURL('=foo/bar?mi=fa&so=la', $st));

        $args_esc = "_xrd_r=application%2Fxrds%2Bxml&_xrd_t=" . $ste;
        $h = $this->proxy_url;
        $this->assertEquals($h . '=foo/bar??' . $args_esc,
                            $this->proxy->queryURL('=foo/bar?', $st));
        $this->assertEquals($h . '=foo/bar????' . $args_esc,
                            $this->proxy->queryURL('=foo/bar???', $st));
    }
}

class Tests_Services_Yadis_XRI extends PHPUnit_TestSuite {
    function getName()
    {
        return "Tests_Services_Yadis_XRI";
    }

    function Tests_Services_Yadis_XRI()
    {
        $this->addTest(new Tests_Services_Yadis_ProxyQueryTestCase());
        $this->addTest(new Tests_Services_Yadis_XriEscapingTestCase());
        $this->addTest(new Tests_Services_Yadis_XriDiscoveryTestCase());
    }
}

?>