<?php
namespace ZendOpenId\Discovery;

class Service
{   
    const OP_ENDPOINT_URL = 'OP_ENDPOINT_URL';
    const OP_LOCAL_IDENTIFIER = 'OP_LOCAL_IDENTIFIER';
    const CLAIMED_IDENTIFIER = 'CLAIMED_IDENTIFIER';
    
    const AX_10 = 'http://openid.net/srv/ax/1.0';
    const UI_10_MODE_POPUP = 'http://specs.openid.net/extensions/ui/1.0/mode/popup';
    const UI_10_ICON = 'http://specs.openid.net/extensions/ui/1.0/icon';
    const PAPE_10 = 'http://specs.openid.net/extensions/pape/1.0';
    
    const SREG_10 = 'http://openid.net/sreg/1.0';
    const SREG_11 = 'http://openid.net/extensions/sreg/1.1';
    
    
    
    protected $version = 0;
    protected $priority = null;
    protected $endpoints = null;
    protected $types = array();
    protected $unrecognized = array();
    
    protected $attributes =  array();
    
    public function __construct($claimed, array $types, array $endpoints, $unrecognized = array(), $priority = 0) {
        $this->priority = $priority;
        $this->endpoints = $endpoints;
        $this->types = $types;
        $this->unrecognized = $unrecognized;
        
        $this->attributes[static::CLAIMED_IDENTIFIER] = $claimed;
        
        $this->process();
    }
    
    protected function process()
    {
         // first try OP Identifier
        if (in_array('http://specs.openid.net/auth/2.0/server', $this->types)) {
            $this->version = 2.0;
            $this->attributes[static::OP_ENDPOINT_URL] = $this->endpoints[0];
            $this->attributes[static::OP_LOCAL_IDENTIFIER] = 'http://specs.openid.net/auth/2.0/identifier_select';
            $this->attributes[static::CLAIMED_IDENTIFIER] = 'http://specs.openid.net/auth/2.0/identifier_select';
        } elseif (in_array('http://specs.openid.net/auth/2.0/signon', $this->types)) {
            $this->version = 2.0;
            $this->attributes[static::OP_ENDPOINT_URL] = $this->endpoints[0];
            
            foreach($this->unrecognized as $u) {
                if (in_array($u['name'], array('LocalID', 'openid2.local_id'))) {
                    $this->attributes[static::OP_LOCAL_IDENTIFIER] = $u['value'];
                    break;
                }
            }
        } elseif (in_array('http://openid.net/signon/1.1', $this->types)) {
            $this->version = 1.1;
            $this->attributes[static::OP_ENDPOINT_URL] = $this->endpoints[0];
            
            foreach ($this->unrecognized as $u) {
                if (in_array($u['name'], array('openid:Delegate', 'LocalID', 'openid.delegate'))) {
                    $this->attributes[static::OP_LOCAL_IDENTIFIER] = $u['value'];
                    break;
                }
                
            }
        } elseif (in_array('http://openid.net/signon/1.0', $this->types)) {
            $this->version = 1.0;
            $this->attributes[static::OP_ENDPOINT_URL] = $this->endpoints[0];
            foreach ($this->unrecognized as $u) {
                if (in_array($u['name'], array('openid:Delegate', 'LocalID', 'openid.delegate'))) {
                    $this->attributes[static::OP_LOCAL_IDENTIFIER] = $u['value'];
                    break;
                }
            }
        }
    }
    
    public function getPriority()
    {
        return $this->priority;
    }
    
    public function getVersion()
    {
        return $this->version;
    }
    
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    public function hasAttribute($attr) {
        return isset($this->attributes[$attr]);
    }
    
    public function getAttribute($attr) {
        if (isset($this->attributes[$attr])) {
            return $this->attributes[$attr];
        } else {
            return null;
        }
    }
    
}
