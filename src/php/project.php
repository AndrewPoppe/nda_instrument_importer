<?php

?>

<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
    </head>
    <body>
        <div class="pagecontainer">
            <div>
                <h4>Use the table below to search for data collection instruments in the NIMH Data Archive</h3>
                <br/>
                <hr>

            </div>
            <div id="ndaSearch" class="dataTableParent">
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
        </div>
        <style>
            table.dataTable tbody td.select-checkbox::before, table.dataTable tbody td.select-checkbox::after, table.dataTable tbody th.select-checkbox::before, table.dataTable tbody th.select-checkbox::after {
                top: 50% !important;
            }
            table.dataTable tr.selected td.select-checkbox::after, table.dataTable tr.selected th.select-checkbox::after {    
                font-size: 16px !important;
                margin-top: -14px !important;
                margin-left: -4px !important;
                text-shadow: none !important;
            }
            button#addFormsButton.disabled {

            }
            button#addFormsButton.disabled :hover {
                outline: none !important;
            }
            tr.selected {
                background-color: #00356b !important;
                color: #ddd !important;
                font-weight: bold;
            }
            div#ndaSearch {
                padding-right: 5%;
            }
        </style>
        <script>
            let searchTable = $('#ndaSearchTable').DataTable({
                "ajax": {
                    "url": "https://nda.nih.gov/api/datadictionary/datastructure",
                    "dataSrc": ""
                },
                "columns": [
                    {data: null, "defaultContent": ""},
                    {data: "shortName", "defaultContent": "" },
                    {data: "title", "defaultContent": "" },
                    {data: "sources[; ]" , "defaultContent": ""},
                    {data: "categories" , render: "[; ]", "defaultContent": ""},
                    {data: "dataType" , "defaultContent": ""},
                    {data: "status" , "defaultContent": ""},
                    {data: "publicStatus" , "defaultContent": ""},
                    {data: "publishDate",
                     type: "date", "defaultContent": "",
                     render: function(data, type, row, meta) {
                        if (!data) return "";
                        if (type === "display") {                            
                            return new Date(data).toLocaleString();
                        }
                        return data;
                    }},
                    {data: "modifiedDate",
                     type: "date", "defaultContent": "",
                     render: function(data, type, row, meta) {
                        if (!data) return "";
                        if (type === "display") {                            
                            return new Date(data).toLocaleString();
                        }
                        return data;
                    }}
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
                    searchable: false,
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
            });
            $('#addFormsButton').on('click', function(e) {
                let rows = searchTable.rows('.selected');
                let nRows = rows.count();
                if (nRows > 20) {
                    Swal.fire({
                        icon: 'error',
                        title:'Error: Too many instruments selected',
                        html: `${nRows} instruments is a lot! Choose fewer.`
                    });
                } else {
                    let ajaxPromises = [];
                    let results = [];
                    searchTable.rows('.selected').every(function(rowIdx, tableLoop, rowLoop) {
                        let data = this.data();
                        let instrument_name = data.shortName;
                        
                        ajaxPromises.push($.ajax({
                            type: "GET",
                            url: `https://nda.nih.gov/api/datadictionary/datastructure/${instrument_name}/csv`,
                        }).then(function(result) {
                            results.push({
                                data: result,
                                formName: instrument_name
                            });
                        }).catch(function(err) {
                            makeError(err);
                        }));
                    });
                    $.when(...ajaxPromises)
                    .then(function(data, textStatus, jqXHR) {
                        convert(results);
                    })
                    .catch(function(err) {
                        makeError(err);
                    });
                }
            });
            searchTable.on( 'buttons-action', 
                function ( e, buttonApi, dataTable, node, config ) {
                    const text = buttonApi.text();
                    if (text.search(/Panes|Builder/)) {
                        $('.dt-button-collection').draggable();
                    }
            });

            function makeLoading() {
                Swal.fire({
                    title: 'Working...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading()
                    },
                });
            }

            function makeError(err) {
                let title = err?.responseJSON?.error || "Error";
                let message = err?.responseJSON?.message || err;
                Swal.fire({
                    icon: 'error',
                    title: title,
                    html: message
                });
            }

            function convert(fileArray) {
                console.log(fileArray);
                makeLoading();
                let payload = {
                    fileArray: fileArray,
                    allInOne: true,
                    instrumentZip: false,
                    duplicateAction: "remove"
                };
                console.log(payload);
                $.ajax({
                    type: "POST",
                    url: "<?= $module->getUrl('src/js/testing.php') ?>",
                    data: payload
                }).then(function(result) {
                    Swal.close();
                    console.log(result);
                }).catch(function(err) {
                    Swal.close();
                    if (err.status == 501) { // duplicates found
                        makeError(err);
                    }
                    else {
                        makeError(err);
                    }
                })
            }
        </script>
    </body>