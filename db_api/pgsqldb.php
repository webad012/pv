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
    
    $schemas = [];
    
    foreach($tables as $table)
    {
        if(!isset($schemas[$table['table_schema']]))
        {
            $schemas[$table['table_schema']] = [];
        }
        
        $schemas[$table['table_schema']][$table['foreign_table_schema']] = false;
    }
    
    $json = parse_array_to_json($schemas, true);
    
    return $json;
}

function get_schema_tables_as_json($db, $selected_schema)
{
    $sql =<<<EOF
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
        WHERE constraint_type = 'FOREIGN KEY' AND (tc.table_schema='$selected_schema' OR ccu.table_schema='$selected_schema');
EOF;
    
    $ret = pg_query($db, $sql);
    if(!$ret){
        throw new Exception('error in getting data from database');
    }
    
    $tables = pg_fetch_all($ret);
    
    $parsed_tables = [];
    
    foreach($tables as $table)
    {        
        $table_name = $table['table_name'];
        if($selected_schema !== $table['table_schema'])
        {
            $table_name = $table['table_schema'];
        }
                
        $foreign_name = $table['foreign_table_name'];
        if($selected_schema !== $table['foreign_table_schema'])
        {
            $foreign_name = $table['foreign_table_schema'];
        }
                
        if(!isset($parsed_tables[$table_name]))
        {
            $parsed_tables[$table_name] = [];
        }
        
        if(!isset($parsed_tables[$foreign_name]))
        {
            $parsed_tables[$foreign_name] = [];
        }

        $parsed_tables[$table_name][$foreign_name] = $table['constraint_name'];
        
    }
    
    $json = parse_array_to_json($parsed_tables);
    
    return $json;
}

function parse_array_to_json(array $array, $is_schema=false)
{
    $json = '[';
    
    $is_first = true;
    foreach($array as $key => $value)
    {
        if($is_first === false)
        {
            $json .= ',';
        }
        
        $json .= '{"adjacencies":[';
        
        $is_first_foreign = true;
        foreach($value as $foreign_table_key => $foreign_table_value)
        {
            if($is_first_foreign === false)
            {
                $json .= ',';
            }
            
            $json .= '{"nodeTo":"'.$foreign_table_key.'","nodeFrom":"'.$key.'"';
            
            if(is_string($foreign_table_value))
            {
                $json .= ',"data":{"labeltext": "'.$foreign_table_value.'"}';
            }
            
            $json .= '}';
            
            $is_first_foreign = false;
        }
        
        $display_html = '';
        if($is_schema === true)
        {
            $display_html = "<a href='javascript:visualise_schema(\\\"$key\\\")'>$key</a>";
        }
        else
        {
            $display_html = $key;
        }
        
        $json .= '],"id":"'.$key.'","name":"'.$key.'","data":{'
                .'"display_html":"'.$display_html.'"'
            .'}}';
        
        $is_first = false;
    }
    
    $json .= ']';
    
    return $json;
}