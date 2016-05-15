<?php

function connectDb($params)
{
    $host = $params['host'];
    $port = $params['port'];
    $dbname = $params['dbname'];
    $credentials = $params['credentials'];
            
    $db = pg_connect( "$host $port $dbname $credentials"  );
    if(!$db)
    {
        throw new Exception('error in accessing database');
    }
    
    return $db;
}

function closeDb($db)
{
    pg_close($db);
}


function get_shemas_as_json($db)
{
    $sql =<<<EOF
        SELECT distinct table_schema, null::varchar as foreign_table_schema
        FROM information_schema.tables
        WHERE table_schema not in ('information_schema', 'pg_catalog')
        UNION
        SELECT distinct tc.table_schema, ccu.table_schema AS foreign_table_schema
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
        WHERE constraint_type = 'FOREIGN KEY' AND tc.table_schema != ccu.table_schema;
EOF;

    $ret = pg_query($db, $sql);
    if(!$ret){
        throw new Exception('error in getting data from database');
    }
    
    $tables = pg_fetch_all($ret);
    
    $dbnodes = [];
    
    foreach($tables as $table)
    {
        $schema_name = $table['table_schema'];
        $foreign_schema_name = $table['foreign_table_schema'];
        
        $schemaObj = null;
        
        if(isset($dbnodes[$schema_name]))
        {
            $schemaObj = $dbnodes[$schema_name];
        }
        else
        {
            $schemaObj = new DBSchema($schema_name);
            $dbnodes[$schema_name] = $schemaObj;
        }
        
        $adjacencie = new DBAdjacencie($schema_name, $foreign_schema_name);
        
        $schemaObj->addAdjacencie($adjacencie);
    }
    
    $result = parse_db_nodes($dbnodes);
        
    return $result;
}

function get_schema_tables_as_json($db, $selected_schema)
{
    $sql =<<<EOF
        SELECT null::varchar as constraint_name, table_schema, table_schema || '.' || table_name as table_name, null::varchar as column_name, 
                null::varchar as foreign_table_schema, null::text as foreign_table_name, null::varchar as foreign_column_name
        FROM information_schema.tables ist
        WHERE table_schema='$selected_schema'
        UNION
        SELECT
            tc.constraint_name, tc.table_schema, tc.table_schema || '.' || tc.table_name as table_name, kcu.column_name, 
            ccu.table_schema AS foreign_table_schema,
            ccu.table_schema || '.' || ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name 
        FROM 
            information_schema.table_constraints AS tc 
            JOIN information_schema.key_column_usage AS kcu
              ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
              ON ccu.constraint_name = tc.constraint_name
        WHERE constraint_type = 'FOREIGN KEY' 
            AND (tc.table_schema='$selected_schema' OR ccu.table_schema='$selected_schema');
EOF;
    
    $ret = pg_query($db, $sql);
    if(!$ret){
        throw new Exception('error in getting data from database');
    }
    
    $tables = pg_fetch_all($ret);
    
    $dbnodes = [];
    
    foreach($tables as $table)
    {
        $is_schema = false;
        $table_name = $table['table_name'];
        if($selected_schema !== $table['table_schema'])
        {
            $table_name = $table['table_schema'];
            $is_schema = true;
        }
        
        $is_foreign_schema = false;
        $foreign_name = $table['foreign_table_name'];
        if($selected_schema !== $table['foreign_table_schema'])
        {
            $foreign_name = $table['foreign_table_schema'];
            $is_foreign_schema = true;
        }
        
        $nodeObj = null;
        
        if(isset($dbnodes[$table_name]))
        {
            $nodeObj = $dbnodes[$table_name];
        }
        else
        {
            if($is_schema === true)
            {
                $nodeObj = new DBSchema($table_name);
            }
            else
            {
                $nodeObj = new DBTable($table_name);
            }
            
            $dbnodes[$table_name] = $nodeObj;
        }
        
        if(!empty($foreign_name))
        {
            if(!isset($dbnodes[$foreign_name]))
            {
                $foreignObj = null;
                if($is_foreign_schema === true)
                {
                    $foreignObj = new DBSchema($foreign_name);
                }
                else
                {
                    $foreignObj = new DBTable($foreign_name);
                }
                
                $dbnodes[$foreign_name] = $foreignObj;
            }
            
            $adjacencie = new DBAdjacencie($table_name, $foreign_name);
            $nodeObj->addAdjacencie($adjacencie);
        }
    }
    
    $result = parse_db_nodes($dbnodes);
    
    return $result;
}

function parse_db_nodes(array $dbnodes)
{
    $result = [];
    foreach($dbnodes as $dbnode)
    {
        $result[] = [
            'adjacencies' => $dbnode->AdjacenciesOutput(),
            'id' => $dbnode->GetId(),
            'name' => $dbnode->GetName(),
            'data' => [
                'display_html' => $dbnode->GetDisplayHtml(),
                'tooltip_html' => $dbnode->GetTooltipHtml()
            ]
        ];
    }
    
    return $result;
}
