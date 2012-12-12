<?php
namespace ZendOpenIdTest\Discovery;

use PHPUnit_Framework_TestCase as TestCase;
use ZendOpenId\Discovery\Yadis;
use ZendOpenId\Discovery\Service;
use Zend\Http\Client\Adapter\Test as TestAdapter;
use Zend\Http\Response as HttpResponse;
use Zend\Http\Headers as HttpHeaders;

/**
 * @outputBuffering enabled
 *
 * @category   Zend
 * @package    Zend_OpenId
 * @subpackage UnitTests
 * @group      Zend_OpenId
 */
class YadisTest extends TestCase
{
    
    const ID       = "http://id.myopenid.com/";
    const REAL_ID  = "http://real_id.myopenid.com/";
    const SERVER   = "http://www.myopenid.com/";
    
    public function testGoogle()
    {
        $provided = 'https://www.google.com/accounts/o8/id';
        
        $discover = new Yadis();
        $mockAdapter = new TestAdapter();
        
        $response = new HttpResponse();
        $response->setStatusCode('200');
        $headers = new HttpHeaders();
        $headers->addHeaderLine('Content-Type', 'application/xrds+xml; charset=utf-8');
        $response->setHeaders($headers);
        $response->setContent(file_get_contents(__DIR__ . '/_files/google.xrds.xml'));
        
        $mockAdapter->setResponse($response);
        
        $discover->getHttpClient()->setAdapter($mockAdapter);
        
        $result = $discover->discover($provided);
        
        // we're expecting excatly one service
        $this->assertEquals(1, count($result));
        
        $expected = array(
            array(2.0, 'http://specs.openid.net/auth/2.0/identifier_select', 'https://www.google.com/accounts/o8/ud', 'http://specs.openid.net/auth/2.0/identifier_select'),
        );
        
        foreach ($result as $k => $service) {
            /* @var $service \ZendOpenId\Discovery\Service */
            $this->assertEquals($expected[$k][0], $service->getVersion());
            $this->assertEquals($expected[$k][1], $service->getAttribute(Service::CLAIMED_IDENTIFIER));
            $this->assertEquals($expected[$k][2], $service->getAttribute(Service::OP_ENDPOINT_URL));
            $this->assertEquals($expected[$k][3], $service->getAttribute(Service::OP_LOCAL_IDENTIFIER));
        }
        
        
    }
    public function testMyOpenIdXrds()
    {
        $provided = 'http://id.myopenid.com/';
        
        $discover = new Yadis();
        $mockAdapter = new TestAdapter();
        
        $response = HttpResponse::fromString(file_get_contents(__DIR__ . '/_files/myopenid.xrds.xml'));
        
        $mockAdapter->setResponse($response);
        
        $discover->getHttpClient()->setAdapter($mockAdapter);
        
        $result = $discover->discover($provided);
        
        $this->assertEquals(3, count($result));
        
        $expected = array(
            array(2.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.1, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
        );
        
        foreach ($result as $k => $service) {
            /* @var $service \ZendOpenId\Discovery\Service */
            $this->assertEquals($expected[$k][0], $service->getVersion());
            $this->assertEquals($expected[$k][1], $service->getAttribute(Service::CLAIMED_IDENTIFIER));
            $this->assertEquals($expected[$k][2], $service->getAttribute(Service::OP_ENDPOINT_URL));
            $this->assertEquals($expected[$k][3], $service->getAttribute(Service::OP_LOCAL_IDENTIFIER));
        }
    }
    
    public function testMyOpenIdHttpHeaderXrds()
    {
        $provided = 'http://id.myopenid.com/';
        
        $discover = new Yadis();
        
        $mockAdapter = new TestAdapter();
        
        
        $raw = file_get_contents(__DIR__ . '/_files/myopenid.html');
        $response = HttpResponse::fromString($raw);       
        $response2 = HttpResponse::fromString(file_get_contents(__DIR__ . '/_files/myopenid.xrds.xml'));
        $mockAdapter->setResponse($response);
        $mockAdapter->addResponse($response2);
        $discover->getHttpClient()->setAdapter($mockAdapter);
        
        $result = $discover->discover($provided);
        
        $this->assertEquals(3, count($result));
        
        $expected = array(
            array(2.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.1, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
        );
        
        foreach ($result as $k => $service) {
            /* @var $service \ZendOpenId\Discovery\Service */
            $this->assertEquals($expected[$k][0], $service->getVersion());
            $this->assertEquals($expected[$k][1], $service->getAttribute(Service::CLAIMED_IDENTIFIER));
            $this->assertEquals($expected[$k][2], $service->getAttribute(Service::OP_ENDPOINT_URL));
            $this->assertEquals($expected[$k][3], $service->getAttribute(Service::OP_LOCAL_IDENTIFIER));
        }
    }
    
    public function testMyOpenIdHtmlMetaXrds()
    {
        $provided = 'http://id.myopenid.com/';
        
        $discover = new Yadis();
        
        $mockAdapter = new TestAdapter();
        
        
        $raw = file_get_contents(__DIR__ . '/_files/xrds-html-meta.html');
        $response = HttpResponse::fromString($raw);       
        //$h = $response->getHeaders()->get('X-XRDS-Location');
        //$response->getHeaders()->removeHeader($h);
        $response2 = HttpResponse::fromString(file_get_contents(__DIR__ . '/_files/myopenid.xrds.xml'));
        $mockAdapter->setResponse($response);
        $mockAdapter->addResponse($response2);
        $discover->getHttpClient()->setAdapter($mockAdapter);
        
        $result = $discover->discover($provided, array(Yadis::METHOD_HTML_META));
        
        $this->assertTrue(is_a($result, '\ZendOpenId\Discovery\Result'), 'Expecting a Result object');
        $this->assertEquals(3, count($result));
        
        $expected = array(
            array(2.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.1, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
            array(1.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server', 'http://id.myopenid.com/'),
        );
        
        foreach ($result as $k => $service) {
            /* @var $service \ZendOpenId\Discovery\Service */
            $this->assertEquals($expected[$k][0], $service->getVersion());
            $this->assertEquals($expected[$k][1], $service->getAttribute(Service::CLAIMED_IDENTIFIER));
            $this->assertEquals($expected[$k][2], $service->getAttribute(Service::OP_ENDPOINT_URL));
            $this->assertEquals($expected[$k][3], $service->getAttribute(Service::OP_LOCAL_IDENTIFIER));
        }
    }
    public function testMyOpenIdHtmlBased()
    {
        $provided = 'http://id.myopenid.com/';
        
        $discover = new Yadis();
        
        $mockAdapter = new TestAdapter();
        
        
        $raw = file_get_contents(__DIR__ . '/_files/myopenid.html');
        $response = HttpResponse::fromString($raw);       
        $mockAdapter->setResponse($response);
        $discover->getHttpClient()->setAdapter($mockAdapter);
        
        $result = $discover->discover($provided, array(Yadis::METHOD_HTML_BASED));
        
        $this->assertTrue(is_a($result, '\ZendOpenId\Discovery\Result'), 'Expecting a Result object');
        $this->assertEquals(2, count($result));
        
        $expected = array(
            array(2.0, 'http://id.myopenid.com/', 'http://www.myopenid.com/server'),
            array(1.1, 'http://id.myopenid.com/', 'http://www.myopenid.com/server'),
        );
        
        foreach ($result as $k => $service) {
            /* @var $service \ZendOpenId\Discovery\Service */
            $this->assertEquals($expected[$k][0], $service->getVersion());
            $this->assertEquals($expected[$k][1], $service->getAttribute(Service::CLAIMED_IDENTIFIER), 'Testing claimed identifier');
            $this->assertEquals($expected[$k][2], $service->getAttribute(Service::OP_ENDPOINT_URL), 'Testing endpoint URL');
        }
    }
    public function testLivejournal()
    {
        $provided = 'http://exampleuser.livejournal.com/';
        
        $discover = new Yadis();
        
        $result = $discover->discover($provided, array(Yadis::METHOD_HTML_BASED));
        
        
        
    }
    
    public function testLegacy()
    {
        $discover = new Yadis();
        $test = new TestAdapter();
        $discover->getHttpClient()->setAdapter($test);

        // Bad response
        
        $id = self::ID;
        $this->assertFalse( $discover->legacyDiscover($id, $server, $version) );

        // Test HTML based discovery (OpenID 1.1)
        
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel=\"openid.server\" href=\"" . self::SERVER . "\">\n" .
                           "<link rel=\"openid.delegate\" href=\"" . self::REAL_ID . "\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id);
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 1.1, $version );

        // Test HTML based discovery (OpenID 1.1)
        
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link href=\"" . self::SERVER . "\" rel=\"openid.server\">\n" .
                           "<link href=\"" . self::REAL_ID . "\" rel=\"openid.delegate\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 1.1, $version );

        // Test HTML based discovery (OpenID 2.0)
        
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel=\"openid2.provider\" href=\"" . self::SERVER . "\">\n" .
                           "<link rel=\"openid2.local_id\" href=\"" . self::REAL_ID . "\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Test HTML based discovery (OpenID 2.0)
        
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link href=\"" . self::SERVER . "\" rel=\"openid2.provider\">\n" .
                           "<link href=\"" . self::REAL_ID . "\" rel=\"openid2.local_id\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Test HTML based discovery (OpenID 1.1 and 2.0)
        
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel=\"openid2.provider\" href=\"" . self::SERVER . "\">\n" .
                           "<link rel=\"openid2.local_id\" href=\"" . self::REAL_ID . "\">\n" .
                           "<link rel=\"openid.server\" href=\"" . self::SERVER . "\">\n" .
                           "<link rel=\"openid.delegate\" href=\"" . self::REAL_ID . "\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Test HTML based discovery (OpenID 1.1) (single quotes)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel='openid.server' href='" . self::SERVER . "'>\n" .
                           "<link rel='openid.delegate' href='" . self::REAL_ID . "'>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 1.1, $version );

        // Test HTML based discovery (OpenID 1.1) (single quotes)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link href='" . self::SERVER . "' rel='openid.server'>\n" .
                           "<link href='" . self::REAL_ID . "' rel='openid.delegate'>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 1.1, $version );

        // Test HTML based discovery (OpenID 2.0) (single quotes)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel='openid2.provider' href='" . self::SERVER . "'>\n" .
                           "<link rel='openid2.local_id' href='" . self::REAL_ID . "'>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Test HTML based discovery (OpenID 2.0) (single quotes)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link href='" . self::SERVER . "' rel='openid2.provider'>\n" .
                           "<link href='" . self::REAL_ID . "' rel='openid2.local_id'>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Test HTML based discovery (OpenID 1.1 and 2.0) (single quotes)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel='openid2.provider' href='" . self::SERVER . "'>\n" .
                           "<link rel='openid2.local_id' href='" . self::REAL_ID . "'>\n" .
                           "<link rel='openid.server' href='" . self::SERVER . "'>\n" .
                           "<link rel='openid.delegate' href='" . self::REAL_ID . "'>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 2.0, $version );

        // Wrong HTML
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertFalse( $discover->legacyDiscover($id, $server, $version) );

        // Test HTML based discovery with multivalue rel (OpenID 1.1)
        $test->setResponse("HTTP/1.1 200 OK\r\n\r\n" .
                           "<html><head>\n" .
                           "<link rel=\" aaa openid.server bbb \" href=\"" . self::SERVER . "\">\n" .
                           "<link rel=\"aaa openid.delegate\" href=\"" . self::REAL_ID . "\">\n" .
                           "</head><body</body></html>\n");
        $id = self::ID;
        $this->assertTrue( $discover->legacyDiscover($id, $server, $version) );
        $this->assertSame( self::REAL_ID, $id );
        $this->assertSame( self::SERVER, $server );
        $this->assertSame( 1.1, $version );
    }
}
