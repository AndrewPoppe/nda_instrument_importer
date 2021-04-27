<?php

?>

<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <script type="text/javascript" src="<?=$module->getUrl("lib/FileSaver.min.js")?>"></script>
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
            let fileArray = [];
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
                makeLoading();
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
                            url: `https://nda.nih.gov/api/datadictionary/datastructure/${instrument_name}`,
                        }).then(function(result) {
                            results.push({
                                data: result.dataElements,
                                formName: result.shortName
                            });
                            /*results.push({
                                data: result,
                                formName: instrument_name
                            });*/
                        }).catch(function(err) {
                            makeError(err);
                        }));
                    });
                    $.when(...ajaxPromises)
                    .then(function(data, textStatus, jqXHR) {
                        fileArray = results;
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
            function addTitle(element, title) {
                $(element).prop('data-toggle','tooltip');
                $(element).prop('title', title);
                $(element).tooltip();
            }

            function makeError(err, title = "Error", duplicates = false) {
                if (duplicates) {
                    options = {
                        icon: 'warning',
                        title: 'Duplicate Field Names Found',
                        html: err,
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: `Remove Duplicates`,
                        cancelButtonText: `Cancel`,
                        denyButtonText: `Rename Duplicates`,
                        confirmButtonColor: `#286dc0`,
                        cancelButtonColor: `#978d85`,
                        denyButtonColor: `#5f712d`,
                        allowEnterKey: false,
                        didRender: () => {
                            const content = Swal.getContent();
                            if (content) {
                                const confirmButton = document.querySelector('.swal2-confirm');
                                const denyButton    = document.querySelector('.swal2-deny');
                                addTitle(confirmButton, 'This removes all duplicated fields from the final result, leaving only the first occurence of the field.');
                                addTitle(denyButton, 'This changes the field names of duplicated fields in the final result.\nYou will be prompted to provide a suffix for the new field names.');
                            }
                        },
                        willClose: () => {
                            const confirmButton = document.querySelector('.swal2-confirm');
                            const denyButton    = document.querySelector('.swal2-deny');
                            $(confirmButton).tooltip('close');
                            $(denyButton).tooltip('close');
                        }
                    }
                } else {
                    options = {
                        icon: "error",
                        title: err?.responseJSON?.error || "Error",
                        html: err?.responseJSON?.message || err?.responseText || err
                    };
                }    
                return Swal.fire(options);
            }

            function makeSuccess(fileData, fileName) {
                saveFunc = () => saveAs(fileData, fileName);
                Swal.fire({
                    icon: 'success',
                    title: 'Conversion successful!',
                    html: `Click <button onclick='saveFunc();' class="btn btn-primaryrc btn-sm">here</button> ` +
                        'to download your converted file(s).',
                    showConfirmButton: false,
                    allowEnterKey: false
                })
            }

            function filterFieldName(temp) {
                temp = temp.trim();
                temp = temp.toLowerCase();
                temp = temp.replace(/[^a-z0-9]/ig,"_");
                temp = temp.replace(/[_]+/g,"_");
                while (temp.length > 0 && (temp.charAt(0) == "_" || temp.charAt(0)*1 == temp.charAt(0))) {
                    temp = temp.substr(1,temp.length);
                }
                while (temp.length > 0 && temp.charAt(temp.length-1) == "_") {
                    temp = temp.substr(0,temp.length-1);
                }
                return temp;
            }

            function handleDuplicateChoice(choice) {
                if (choice.isDismissed) return;
                let duplicateAction = choice.isConfirmed ? "remove" : "rename";
                if (duplicateAction === "rename") {
                    Swal.fire({
                        title: "Choose Suffix",
                        input: "text",
                        inputLabel: "Choose a suffix to be appended to all duplicate field names.\nLeave blank to append the name of the form (e.g., field_form1)",
                        showCancelButton: true,
                        preConfirm: function(suffix) {
                            return filterFieldName(suffix);
                        }
                    })
                    .then(function(result) {
                        if (result.isDismissed) return;
                        makeLoading();
                        convert(fileArray, duplicateAction, result.value);
                    })
                } else {
                    makeLoading();
                    convert(fileArray, duplicateAction);
                }
            }

            function convert(fileArray, duplicateAction, renameSuffix) {
                let payload = {
                    fileArray: fileArray,
                    allInOne: true,
                    instrumentZip: false,
                    duplicateAction: duplicateAction,
                    renameSuffix: renameSuffix,
                    json: true
                };
                let postData = JSON.stringify(payload);
                let formData = new FormData();
                formData.append("payload", postData);

                $.ajax({
                    type: "POST",
                    url: "<?= $module->getUrl('src/php/converter.php') ?>",
                    data: formData,
                    processData: false,
                    contentType: false,
                }).then(function(result) {
                    Swal.close();
                    console.log(result);
                    res = JSON.parse(result);
                    console.log(res);
                    if (res.type === "text/csv") {
                        let blob = new Blob([res.data], { type: "text/csv;charset=utf-8" });
                        makeSuccess(blob, res.file);
                    }
                }).catch(function(err) {
                    Swal.close();
                    if (err.status == 501) { // duplicates found
                        makeError(err.responseText, 'Error converting files', duplicates = true)
                        .then(handleDuplicateChoice);
                    }
                    else {
                        makeError(err);
                    }
                })
            }
        </script>
    </body>