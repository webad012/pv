<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>ForceDirected - Force Directed Static Graph</title>
        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>

        <!-- CSS Files -->
        <link type="text/css" href="css/db_visualisation.css" rel="stylesheet" />

        <!-- JIT Library File -->
        <script language="javascript" type="text/javascript" src="js/jit.js"></script>

        <script language="javascript" type="text/javascript" src="js/db_visualisation.js"></script>
    </head>

    <body onload="init_db_visualisation({
        'canvas_div_id': 'infovis',
        'onDragEnd': function(node, eventInfo, e){
        },
        'onLabelClick': function(node){
            //set final styles
            fd.graph.eachNode(function (n) {
                if (n.id !== node.id)
                {
                    delete n.selected;
                }
                n.setData('dim', 7, 'end');
                n.eachAdjacency(function (adj) {
                    adj.setDataset('end', {
                        lineWidth: 0.4,
                        color: '#23a4ff'
                    });
                });
            });
            if (!node.selected) {
                node.selected = true;
                node.setData('dim', 17, 'end');
                node.eachAdjacency(function (adj) {
                    adj.setDataset('end', {
                        lineWidth: 3,
                        color: '#36acfb'
                    });
                });
            } else {
                delete node.selected;
            }
            //trigger animation to final styles
            fd.fx.animate({
                modes: ['node-property:dim',
                    'edge-property:lineWidth:color'],
                duration: 500
            });
            // Build the right column relations list.
            // This is done by traversing the clicked node connections.

            //append connections information
            $jit.id('inner-details').innerHTML = node.data.display_html;
        }
    });">
        <div id="container">
            <div id="center-container">
                <div id="infovis"></div>    
            </div>

            <div id="right-container">
                <a id="backButton" href="javascript:visualise_schema();">Back</a>
                <a href="javascript:downloadPositions()">Download positions</a>
                <input type="file" id="fileuplaod" onchange="restorePositions(this);"/>
                <div id="inner-details"></div>
            </div>

            <div id="log"></div>
        </div>
    </body>
</html>