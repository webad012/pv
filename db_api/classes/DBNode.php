<?php

abstract class DBNode
{
    static public $TYPE_DBNODE = 'DBNODE';
    static public $TYPE_SCHEMA = 'SCHEMA';
    static public $TYPE_TABLE = 'TABLE';
    
    protected $_type;
    protected $_name;
    protected $_adjacencies = [];
    protected $_data = [];
    
    public function __construct($name) {
        $this->_name = $name;
        $this->_type = DBNode::$TYPE_DBNODE;
    }
    
    public function GetId()
    {
        return $this->_name;
    }
    public function GetName()
    {
        return $this->_name;
    }
    
    public function addAdjacencie(DBAdjacencie $adjacencie)
    {
        $this->_adjacencies[] = $adjacencie;
    }
    
    public function AdjacenciesOutput()
    {
        $result = [];
        
        foreach($this->_adjacencies as $adjacencie)
        {
            $result[] = [
                'nodeTo' => $adjacencie->GetTo(),
                'nodeFrom' => $adjacencie->GetFrom(),
                'labeltext' => $adjacencie->GetLabeltext(),
            ];
        }
        
        return $result;
    }
    
    // Force Extending class to define this method
    abstract protected function GetDisplayHtml();
    abstract protected function GetTooltipHtml();
}