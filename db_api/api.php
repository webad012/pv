<?php

//include('mysqldb.php');
include('pgsqldb.php');
include('classes/DBNode.php');
include('classes/DBSchema.php');
include('classes/DBTable.php');
include('classes/DBAdjacencie.php');

try 
{
    if(!file_exists('config.php'))
    {
        throw new Exception('no config file');
    }

    include('config.php');

    $action = filter_input(INPUT_POST, 'action');

    $response = [
        'status' => 'failure',
        'message' => 'bad action'
    ];

    if($action === 'GetJson')
    {
        $selected_schema = filter_input(INPUT_POST, 'selected_schema');

        $db = connectDb($db_params);

        $json = [];
        
        $have_depth = false;
        $have_back_button = false;
//        $back_button_action = "";

        if(empty($selected_schema))
        {
            $json = get_shemas_as_json($db);
        }
        else
        {
            $have_back_button = true;
//            $back_button_action = "javascript:initGraph();";
            $json = get_schema_tables_as_json($db, $selected_schema);
        }

        closeDb($db);
        
        $response = [
            'status' => 'success',
            'json' => $json,
            'have_back_button' => $have_back_button,
//            'back_button_action' => $back_button_action
        ];
    }

    $encoded_response = json_encode($response);
    echo $encoded_response;
} 
catch (Exception $ex) 
{
    $response = [
        'status' => 'failure',
        'message' => 'Exception: '.$ex->getMessage()
    ];
    
    $encoded_response = json_encode($response);
    echo $encoded_response;
}
