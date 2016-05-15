<?php

class DBAdjacencie
{
    private $_from;
    private $_to;
    private $_labelText;
    
    public function __construct($from, $to, $labelText='') {
        $this->_from = $from;
        $this->_to = $to;
        $this->_labelText = $labelText;
    }
    
    public function GetFrom()
    {
        return $this->_from;
    }
    public function GetTo()
    {
        return $this->_to;
    }
    public function GetLabelText()
    {
        return $this->_labelText;
    }
}
