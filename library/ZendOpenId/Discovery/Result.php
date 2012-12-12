<?php
namespace ZendOpenId\Discovery;

use Iterator;
use Countable;

class Result implements Iterator, Countable
{
    
    protected $pointer = 0;
    protected $services = array();
    
    /**
     * 
     * @param \ZendOpenId\Discovery\Service $service
     * @return void
     */
    public function addService(Service $service)
    {
        $this->services[] = $service;
        usort($this->services, function($a, $b){
            if ($a->getPriority() == $b->getPriority()) return 0;
            return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
        });
    }
    
    /**
     * 
     * @return array
     */
    public function getServices()
    {
        return $this->services;
    }
    
    public function current() {
        return $this->services[$this->pointer];
    }
    
    public function key() {
        return $this->pointer;
    }
    
    public function next() {
        $this->pointer++;
    }
    public function rewind() {
        $this->pointer = 0;
    }
    public function valid() {
        return isset($this->services[$this->pointer]);
    }
    public function count() {
        return count($this->services);
    }
}
