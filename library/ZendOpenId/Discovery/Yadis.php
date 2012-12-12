<?php

namespace ZendOpenId\Discovery;

use Zend\Http\Request as Request;
use DOMDocument;

class Yadis extends AbstractDiscovery
{

    const METHOD_XRDS = 'METHOD_XRDS';
    const METHOD_HTTP_HEADER = 'METHOD_HTTP_HEADER';
    const METHOD_HTML_META = 'METHOD_HTML_META';
    const METHOD_HTML_BASED = 'METHOD_HTML_BASED';
    const XRDS_CONTENT_TYPE = 'application/xrds+xml';

    const HTML_PATTERN_OPENID2_PROVIDER_1 = '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.provider[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i';
    const HTML_PATTERN_OPENID2_PROVIDER_2 = '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.provider[ \t]*[^"\']*\\3[^>]*\/?>/i';
    
    const HTML_PATTERN_OPENID_SERVER_1 = '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.server[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i';
    const HTML_PATTERN_OPENID_SERVER_2 = '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.server[ \t]*[^"\']*\\3[^>]*\/?>/i';
    
    const HTML_PATTERN_OPENID2_LOCAL_ID_1 = '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.local_id[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i';
    const HTML_PATTERN_OPENID2_LOCAL_ID_2 = '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid2.local_id[ \t]*[^"\']*\\3[^>]*\/?>/i';
    
    const HTML_PATTERN_OPENID_DELEGATE_1 = '/<link[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.delegate[ \t]*[^"\']*\\1[^>]*href=(["\'])([^"\']+)\\2[^>]*\/?>/i';
    const HTML_PATTERN_OPENID_DELEGATE_2 = '/<link[^>]*href=(["\'])([^"\']+)\\1[^>]*rel=(["\'])[ \t]*(?:[^ \t"\']+[ \t]+)*?openid.delegate[ \t]*[^"\']*\\3[^>]*\/?>/i';
    
    public function legacyDiscover(&$id, &$server, &$version)
    {
        $r = $this->discover($id, array(Yadis::METHOD_HTML_BASED));
        
        if (is_a($r, 'ZendOpenId\Discovery\Result')) {
            /* @var $r \ZendOpenId\Discovery\Result */
            if (count($r) > 0) {
                $s = $r->current();
                /* @var $s \ZendOpenId\Discovery\Service */
                $id = $s->getAttribute(Service::OP_LOCAL_IDENTIFIER);
                $server = $s->getAttribute(Service::OP_ENDPOINT_URL);
                $version = $s->getVersion();
                
                //print_r($s);
                
                return true;
            }
        }
        return false;
    }
    
    /**
     * 
     * @param string $uri
     * @param array $methods
     * @return \ZendOpenId\Discovery\Result|boolean
     */
    public function discover($uri, array $methods = array()) {
        if (count($methods) == 0) {
            $methods = array(
                static::METHOD_XRDS,
                static::METHOD_HTML_META,
                static::METHOD_HTTP_HEADER,
                static::METHOD_HTML_BASED,
            );
        }

        $this->getHttpClient();
        $this->httpClient->resetParameters();

        $req = $this->httpClient->getRequest();


        /* @var $headers \Zend\Http\Headers */

        if (in_array(static::METHOD_XRDS, $methods)) {
            $headers = $req->getHeaders();
            $headers->addHeaderLine('Accept', 'application/xrds+xml;q=1.0,text/html;q=0.9,application/xhtml+xml;q=0.9,application/xml;q=0.8,*/*;q=0.7');
            $req->setHeaders($headers);
        }



        $req->setUri($uri);
        $req->setMethod(Request::METHOD_GET);
        $resp = $this->httpClient->send();
        /* @var $resp \Zend\Http\Response */

        
        // Try direct XRDS
        if (in_array(static::METHOD_XRDS, $methods)) {
            $responseHeaders = $resp->getHeaders();
            $contentType = $responseHeaders->get('Content-Type');
            if (stripos($contentType->getFieldValue(), static::XRDS_CONTENT_TYPE) !== false) {
                return $this->parseXrdsContent($uri, $resp->getBody());
            }
        }
        
        // Try XRDS location from HTTP header
        if (in_array(static::METHOD_HTTP_HEADER, $methods)) {
            $xrdsLocation = $responseHeaders->get('X-XRDS-Location');
            if ($xrdsLocation) {
                $this->httpClient->resetParameters();
                $this->httpClient->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/xrds+xml;q=1.0,text/html;q=0.9,application/xhtml+xml;q=0.9,application/xml;q=0.8,*/*;q=0.7');
                $this->httpClient->setMethod(Request::METHOD_GET);
                $this->httpClient->setUri($xrdsLocation->getFieldValue());
                $xrdsResponse = $this->httpClient->send();

                return $this->parseXrdsContent($uri, $xrdsResponse->getBody());
            }
        }
        
        // Try XRDS location from HTML meta
        if (in_array(static::METHOD_HTML_META, $methods)) {

            $metaMatches = array();
            if (preg_match_all('/<meta.*?X\-XRDS\-Location.*?>/ims', $resp->getBody(), $metaMatches)) {
                $m = array();
                if (preg_match('/content=[\"\'](.+?)[\"\']/i', $metaMatches[0][0], $m)) {
                    $this->httpClient->resetParameters();
                    $this->httpClient->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/xrds+xml;q=1.0,text/html;q=0.9,application/xhtml+xml;q=0.9,application/xml;q=0.8,*/*;q=0.7');
                    $this->httpClient->setMethod(Request::METHOD_GET);
                    $this->httpClient->setUri($m[1]);
                    $xrdsResponse = $this->httpClient->send();
                    return $this->parseXrdsContent($uri, $xrdsResponse->getBody());
                }
            }
        }
        
        // Fall back to HTML-based discovery
        if (in_array(static::METHOD_HTML_BASED, $methods)) {
            return $this->parseHtmlContent($uri, $resp->getBody());
        }

        return false;
    }

    /**
     * 
     * @param string $content
     * @return \ZendOpenId\Discovery\Result
     */
    protected function parseXrdsContent($uri, $content) {
        $dom = new \DOMDocument();
        //$dom->loadXML($content, LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT | LIBXML_XINCLUDE);
        $dom->loadXML($content);
        $services = $dom->getElementsByTagName('Service');
        $result = new Result();
        foreach ($services as $service) {
            /* @var $service \DOMNode */

            $priority = 0;

            $attributes = $service->attributes;
            if ($attributes) {
                /* @var $attributes \DOMNamedNodeMap */
                $p = $attributes->getNamedItem('priority');
                if ($p) {
                    /* @var $p \DOMAttr */
                    $priority = (int) $p->nodeValue;
                }
            }

            $types = array();
            $endpoints = array();
            $unrecognized = array();

            foreach ($service->childNodes as $child) {
                if ($child->nodeType === \XML_TEXT_NODE)
                    continue;


                switch ($child->nodeName) {
                    case 'Type':
                        $types[] = $child->nodeValue;
                        break;
                    case 'URI':
                        $endpoints[] = $child->nodeValue;
                        break;
                    default:
                        $unrecognized[] = array('name' => $child->nodeName, 'value' => $child->nodeValue);
                        break;
                }
            }
            $s = new Service($uri, $types, $endpoints, $unrecognized, $priority);
            $result->addService($s);
        }
        return $result;
    }

    /**
     * 
     * @param string $uri
     * @param string $content
     * @return \ZendOpenId\Discovery\Result
     */
    protected function parseHtmlContent($uri, $content) {
        // get all links
        $content = trim($content);
        if (empty($content)) return false;
        
        $result = new Result();
        libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML($content);
        
        $links = $document->getElementsByTagName('link');
        
        $discoveredTags = array();
        
        foreach($links as $link) {
            /* @var $link \DOMNode */
            $rel = null;
            $href = null;
            foreach($link->attributes as $attr) {
                /* @var $attr \DOMAttr */
                switch ($attr->name) {
                    case 'rel':
                        $rel = $attr->value;
                        break;
                    case 'href':
                        $href = $attr->value;
                        break;
                }
            }
            $m = array();
            if (!preg_match('/(openid2?\.[a-z_]+)/i', $rel, $m)) {
                continue;
            }
            
            $discoveredTags[$m[1]] = $href;
            
        }
        
        if (array_key_exists('openid2.provider', $discoveredTags)) {
            $u = array();
            if (array_key_exists('openid2.local_id', $discoveredTags)) {
                $u[] = array('name' => 'openid2.local_id', 'value' => $discoveredTags['openid2.local_id']);
            }
            $s = new Service($uri, array('http://specs.openid.net/auth/2.0/signon'), array($discoveredTags['openid2.provider']), $u, 0);
            $result->addService($s);
        }
        
        if (array_key_exists('openid.server', $discoveredTags)) {
            $u = array();
            if (array_key_exists('openid.delegate', $discoveredTags)) {
                $u[] = array('name' => 'openid.delegate', 'value' => $discoveredTags['openid.delegate']);
            }
            $s = new Service($uri, array('http://openid.net/signon/1.1'), array($discoveredTags['openid.server']), $u, 1);
            $result->addService($s);
        }
        
        return $result;
    }
}
