<?php

class DBTable extends DBNode
{
    public function __construct($name) {
        parent::__construct($name);
        $this->_type = DBNode::$TYPE_TABLE;
    }
    
    public function GetDisplayHtml()
    {
        $result = $this->_name;
        
        return $result;
    }
    
    public function GetTooltipHtml()
    {
        $result = $this->_name;
        
        return $result;
    }
}
