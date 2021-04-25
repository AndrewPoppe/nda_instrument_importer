<?php

?>

<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
    </head>
    <body>
        <div id="ndaSearch" class="dataTableParentHidden">
            <br/>
            <table id="ndaSearchTable" class="dataTable">
                <thead>
                    <tr>
                        <th>Add to Project?</th>
                        <th>Short Name</th>
                        <th>Title</th>
                        <th>Sources</th>
                        <th>Categories</th>
                        <th>Data Type</th>
                        <th>Status</th>
                        <th>Submission Status</th>
                        <th>Published Date</th>
                        <th>Modified Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        /*$ch = curl_init("https://nda.nih.gov/api/datadictionary/datastructure");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        $instruments = json_decode(curl_exec($ch));
                        curl_close($ch);
                        foreach($instruments as $instrument) { 
                    ?>
                        <tr>
                            <td><?= $instrument->shortName ?></td>
                            <td><?= $instrument->title ?></td>
                            <td><?= implode("; ", $instrument->sources) ?></td>
                            <td><?= implode("; ", $instrument->categories) ?></td>
                            <td><?= $instrument->dataType ?></td>
                            <td><?= $instrument->status ?></td>
                            <td><?= $instrument->publicStatus ?></td>
                            <td><?php 
                                $date = DateTime::createFromFormat('Y-m-d\TH:i:s.vO', $instrument->publishDate);
                                echo date_format($date, 'Y-m-d'); 
                            ?></td>
                            <td><?php 
                                $date = DateTime::createFromFormat('Y-m-d\TH:i:s.vO', $instrument->modifiedDate);
                                echo date_format($date, 'Y-m-d'); 
                            ?>
                        </tr>
                    <?php    } */?>
                    
                </tbody>
            </table>
            <button id="addFormsButton" class="btn btn-large btn-primaryrc" disabled>Add selected forms</button>
        </div>
        <style>
            table.dataTable tbody td.select-checkbox::before, table.dataTable tbody td.select-checkbox::after, table.dataTable tbody th.select-checkbox::before, table.dataTable tbody th.select-checkbox::after {
                top: 50% !important;
            }
            button#addFormsButton.disabled {
                
            }
            button#addFormsButton.disabled :hover {
                outline: none !important;
            }
        </style>
        <script>
            let searchTable = $('#ndaSearchTable').DataTable({
                "ajax": {
                    "url": "https://nda.nih.gov/api/datadictionary/datastructure",
                    "dataSrc": ""
                },
                "columns": [
                    {data: function (row, type, set) {return '';}},
                    {data: "shortName" },
                    {data: "title" },
                    {data: "sources" },
                    {data: "categories" },
                    {data: "dataType" },
                    {data: "status" },
                    {data: "publicStatus" },
                    {data: "publishDate" },
                    {data: "modifiedDate" }
                ],
                dom: 'lBfrtip',
                stateSave: true,
                buttons: [
                    {
                        text: "Select All",
                        action: function (e, dt, node, config) {
                            dt.rows({filter: 'applied'}).select();
                        }
                    },
                    "selectNone",
                    {
                        extend: 'searchPanes',
                        config: {
                            cascadePanes: true
                        }
                        
                    },
                    {
                        extend: 'searchBuilder'
                    },
                    'colvis',
                    {
                        text: 'Restore Default',
                        action: function (e, dt, node, config) {
                            dt.state.clear();
                            window.location.reload();
                        }
                    }
                ],
                columnDefs: [{
                    orderable: false,
                    className: 'select-checkbox',
                    targets: 0,
                    data: null,
                    defaultContent: ""
                }],
                select: {
                    style: 'os',
                    selector: 'td:first-child'
                },
                order: [
                    [1, 'asc']
                ]
            });
            searchTable.on('select deselect', function(e, dt, type, indexes) {
                if (dt.rows('.selected').any()) {
                    $('#addFormsButton').prop('disabled', false);
                } else {
                    $('#addFormsButton').prop('disabled', true);
                }
            })
        </script>
    </body>