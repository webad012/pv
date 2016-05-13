var fd, cur_json, have_depth_global;

var labelType, useGradients, nativeTextSupport, animate;

(function () {
    var ua = navigator.userAgent,
            iStuff = ua.match(/iPhone/i) || ua.match(/iPad/i),
            typeOfCanvas = typeof HTMLCanvasElement,
            nativeCanvasSupport = (typeOfCanvas === 'object' || typeOfCanvas === 'function'),
            textSupport = nativeCanvasSupport
            && (typeof document.createElement('canvas').getContext('2d').fillText === 'function');
    //I'm setting this based on the fact that ExCanvas provides text support for IE
    //and that as of today iPhone/iPad current text support is lame
    labelType = (!nativeCanvasSupport || (textSupport && !iStuff)) ? 'Native' : 'HTML';
    nativeTextSupport = labelType === 'Native';
    useGradients = nativeCanvasSupport;
    animate = !(iStuff || !nativeCanvasSupport);
})();

var Log = {
    elem: false,
    write: function (text) {
        if (!this.elem)
            this.elem = document.getElementById('log');
        this.elem.innerHTML = text;
        this.elem.style.left = (500 - this.elem.offsetWidth / 2) + 'px';
    }
};

$jit.ForceDirected.Plot.EdgeTypes.implement({
    'label-line': {
        'render': function (node, canvas) {
            var from = node.nodeFrom.pos.getc(true),
                    to = node.nodeTo.pos.getc(true);
            this.edgeHelper.line.render(from, to, canvas);
            var data = node.data;
            if (data.labeltext) {
                var ctx = canvas.getCtx();
                var posFr = node.nodeFrom.pos.getc(true);
                var posTo = node.nodeTo.pos.getc(true);
                ctx.fillStyle = "#00ff00";
                ctx.fillText(data.labeltext, (posFr.x + posTo.x) / 2, (posFr.y + posTo.y) / 2);
            }// if data.labeltext
        },
        //optional  
        'contains': function (node, pos) {
            var from = node.nodeFrom.pos.getc(true),
                    to = node.nodeTo.pos.getc(true);
            return this.edgeHelper.line.contains(from, to, pos, this.edge.epsilon);
        }
    }
});

$jit.ForceDirected.Plot.NodeTypes.implement({
    'db-table': {
        'render': function (node, canvas) {
            var pos = node.pos.getc(true),
                    dim = node.getData('dim');
            this.nodeHelper.square.render('fill', pos, dim, canvas);
        }
    }
});

function init_db_visualisation(canvas_div_id)
{
    fd = new $jit.ForceDirected({
        //id of the visualization container
        injectInto: canvas_div_id,
        //Enable zooming and panning
        //with scrolling and DnD
        Navigation: {
            enable: true,
            type: 'Native',
            //Enable panning events only if we're dragging the empty
            //canvas (and not a node).
            panning: 'avoid nodes',
            zooming: 10 //zoom speed. higher is more sensible
        },
        // Change node and edge styles such as
        // color and width.
        // These properties are also set per node
        // with dollar prefixed data-properties in the
        // JSON structure.
        Node: {
            overridable: true,
            dim: 10,
            type: "square"
        },
        Edge: {
            overridable: true,
            color: '#23A4FF',
            lineWidth: 0.4,
            type: 'label-line'
        },
        // Add node events
        Events: {
            enable: true,
            type: 'Native',
            //Change cursor style when hovering a node
            onMouseEnter: function () {
                fd.canvas.getElement().style.cursor = 'move';
            },
            onMouseLeave: function () {
                fd.canvas.getElement().style.cursor = '';
            },
            //Update node positions when dragged
            onDragMove: function (node, eventInfo, e) {
                var pos = eventInfo.getPos();
                node.pos.setc(pos.x, pos.y);
                fd.plot();
            },
            //Implement the same handler for touchscreens
            onTouchMove: function (node, eventInfo, e) {
                $jit.util.event.stop(e); //stop default touchmove event
                this.onDragMove(node, eventInfo, e);
            }
        },
        //Add Tips
        Tips: {
            enable: true,
            onShow: function (tip, node) {
                //count connections
                var count = 0;
                node.eachAdjacency(function () {
                    count++;
                });
                //display node info in tooltip
                innerhtml = "<div class=\"tip-title\">" + node.name + "</div>";

                if (typeof node.data.columns !== 'undefined')
                {
                    innerhtml += "<div class=\"columns-table-wrapper\"><table><tr><td>name</td><td>type</td></tr>";

                    for (var key in node.data.columns) {
                        innerhtml += "<tr><td>" + key + "</td><td>" + node.data.columns[key] + "</td></tr>";
                    }

                    innerhtml += "</table></div>";
                }

                tip.innerHTML = innerhtml;
            }
        },
        //Number of iterations for the FD algorithm
        iterations: 200,
        //Edge length
        levelDistance: 150,
        // This method is only triggered
        // on label creation and only for DOM labels (not native canvas ones).
        onCreateLabel: function (domElement, node) {
            // Create a 'name' and 'close' buttons and add them
            // to the main node label
            var nameContainer = document.createElement('span'),
                    style = nameContainer.style;
            nameContainer.className = 'name';
            nameContainer.innerHTML = node.name;
            domElement.appendChild(nameContainer);

            style.fontSize = "0.8em";
            style.color = "#ddd";
            //Toggle a node selection when clicking
            //its name. This is done by animating some
            //node styles like its dimension and the color
            //and lineWidth of its adjacencies.
            nameContainer.onclick = function () {
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
            };
        },
        // Change node styles when DOM labels are placed
        // or moved.
        onPlaceLabel: function (domElement, node) {
            var style = domElement.style;
            var left = parseInt(style.left);
            var top = parseInt(style.top);
            var w = domElement.offsetWidth;
            style.left = (left - w / 2) + 'px';
            style.top = (top + 10) + 'px';
            style.display = '';
        }
    });
    
    visualise_schema();
}

/**
 * schema_name - if empty, schema is seen as database, and elements are shemas
 *              if not empty, elements are tables
 * 
 * 
 * @param string schema_name
 * @returns {undefined}
 */
function visualise_schema(schema_name)
{
    $.ajax({
        method: "POST",
        url: "db_api/api.php",
        data: {
            action: 'GetJson', 
            selected_schema: schema_name
        }
    })
    .done(function( response ) {
        var parsed_reposnse = jQuery.parseJSON(response);
        if(parsed_reposnse.status !== 'success')
        {
            Log.write(response);
        }
        else
        {
            if(parsed_reposnse.have_back_button === true)
            {
                $('#backButton').show();
            }
            else
            {
                $('#backButton').hide();
            }
            
            cur_json = jQuery.parseJSON(parsed_reposnse.json);
            
            initGraph(cur_json);
        }
    });
}

function initGraph(json, positions) {
    $jit.id('inner-details').innerHTML = "";
    
    // load JSON data.
    fd.loadJSON(json);
        
    if(typeof positions === 'undefined')
    {
        // compute positions incrementally and animate.
        fd.computeIncremental({
            iter: 50,
            property: 'end',
            onStep: function (perc) {
                Log.write(perc + '% loaded...');
            },
            onComplete: function () {
                Log.write('done');
                fd.animate();
            }
        });
        // end
    }
    else
    {
        $.each(positions, function(key, value){
            node = fd.graph.getNode(value.id);
            if (node !== null) {
                node.setPos(new $jit.Complex(value.x,value.y), 'current');
                fd.plot();
            }
        });
    }
}

function downloadPositions()
{
    var data = {
        'cur_json': cur_json,
        'node_positions': []
    };

    fd.graph.eachNode(function (n) {
        var pos = n.getPos();
        data.node_positions.push({
            'id': n.id,
            'x': pos.x,
            'y': pos.y
        });
    });

    var string_array = JSON.stringify(data);

    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + string_array);
    element.setAttribute('download', 'download.txt');

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

function restorePositions(input)
{
//    var ext = input.files[0]['name'].substring(input.files[0]['name'].lastIndexOf('.') + 1).toLowerCase();
    if (input.files && input.files[0] 
//            && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")
    )
    {
        var reader = new FileReader();
        reader.onload = function (e) {            
            var result_array = JSON.parse(e.target.result);            
            initGraph(result_array.cur_json, result_array.node_positions);
        };
        reader.readAsText(input.files[0]);
    }
    else
    {
        alert("Failed to load file");
    }
}
