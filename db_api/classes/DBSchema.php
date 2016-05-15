<?php

class DBSchema extends DBNode
{
    public function __construct($name) {
        parent::__construct($name);
        $this->_type = DBNode::$TYPE_SCHEMA;
    }
    
    public function GetDisplayHtml()
    {
        $result = "<a href='javascript:visualise_schema(\"$this->_name\")'>$this->_name</a>";
        
        return $result;
    }
    
    public function GetTooltipHtml()
    {
        $result = $this->_name;
        
        return $result;
    }
}
