<?php

namespace App\Server;

class StreamedResponse extends \Symfony\Component\HttpFoundation\StreamedResponse
{
    public function getCallback()
    {
        return $this->callback;
    }
    
    public function sendContent()
    {
        return $this;
    }
    
    public function sendHeaders()
    {
        return $this;
    }
}
