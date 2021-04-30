<?php

$config = $module->getConfig();
$moduleName = $config["name"];
$modulePath = $module->getModulePath();
$moduleVersion = preg_replace("/.*_v|\//", "", $modulePath);

?>

<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-colvis-1.7.0/date-1.0.3/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/b-colvis-1.7.0/date-1.0.3/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>

        <script src="//cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <script type="text/javascript" src="<?=$module->getUrl("lib/FileSaver.min.js")?>"></script>
    </head>
    <body>
        <div class="pagecontainer">
            <div class="infocontainer">
                <h4><?=$moduleName?></h3>
                    <p>Use this tool to import data dictionaries from the <strong><a title="NIMH Data Archive" href="https://nda.nih.gov/data_dictionary.html" target="_blank" rel="noopener noreferrer">NIMH Data Archive</a></strong> directly into this REDCap project.</p>
                <p id="infotext" onclick="(function() {
                    Swal.fire({
                        icon: 'info',
                        iconColor: '#17a2b8',
                        title: '<?=$moduleName.' v'.$moduleVersion?>',
                        confirmButtonText: 'Got it!',
                        confirmButtonColor: '#17a2b8',
                        html: `Find data collection instruments in the table below using the provided search/filter tools.
                               <br>Select the instruments you would like to add to this REDCap project using the checkboxes.
                               <br>Multiple instruments can be added by holding <code>CTRL</code> while clicking (<code>CMD</code> on Apple machines).
                               <br><br>Click the <strong>Add selected forms</strong> button once you have made your selections.`
                    })})();">Click here for more information</p>           
                <br/>
                <hr>
                <style>
                    .infocontainer p {
                        font-size: 110%;
                    }
                    #infotext {
                        cursor: pointer;
                        text-decoration: underline;
                        font-weight: bold;
                        color: #17a2b8;
                    }
                    #infotext:hover {
                        text-shadow: 0px 0px 5px #17a2b8;
                    }
                </style>
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
                    </tbody>
                </table>
                <button id="addFormsButton" class="btn btn-large btn-primaryrc" disabled>Add selected forms</button>
            </div>
        </div>
        <style>
            table.dataTable tbody td.select-checkbox::before, table.dataTable tbody td.select-checkbox::after, table.dataTable tbody th.select-checkbox::before, table.dataTable tbody th.select-checkbox::after {
                top: 50% !important;
                cursor: pointer;
            }
            td.select-checkbox:hover {
                cursor: pointer;
                background-color: inherit;
                filter: brightness(80%);
            }
            table.dataTable tr.selected td.select-checkbox::after, table.dataTable tr.selected th.select-checkbox::after {    
                font-size: 16px !important;
                margin-top: -14px !important;
                margin-left: -4px !important;
                text-shadow: none !important;
            }
            div.dtsp-panesContainer tr:not(.selected):hover {
                filter: brightness(80%);
            }
            div.dtsp-panesContainer tr {
                cursor: pointer;
            }
            button#addFormsButton.disabled {

            }
            
            tr.selected {
                background-color: #00356b !important;
                color: #ddd !important;
                font-weight: bold;
            }
            div#ndaSearch {
                padding-right: 5%;
            }
            .swal2-popup {
                min-width: 32em;
                width: auto;
            }
            button:hover {
                outline: none !important;
            }
            div.dataTableParentHidden { overflow:hidden; height:0px; width:100%; }
            div.ui-draggable { cursor: move; cursor: grab; cursor: -moz-grab; cursor: -webkit-grab; }
            div.ui-draggable-dragging { cursor: grabbing; cursor: -moz-grabbing; cursor: -webkit-grabbing; }
            div.dtsb-searchBuilder { cursor: inherit; }
            div.dtsb-searchBuilder select { cursor: pointer; }
            div.dt-buttons {
                margin-left: 10px;
            }
            div.dt-button-collection div[role=menu]:not(.dtsb-searchBuilder) button.active {
                width: 100%;
                font-weight: normal;    
            }
            div.dt-button-collection div[role=menu]:not(.dtsb-searchBuilder) button.active:hover {
                width: 100%;
                filter: brightness(110%);    
            }
            
            div.dt-button-collection div[role=menu]:not(.dtsb-searchBuilder) button:not(.active) {
                width: 100%;
                filter: brightness(60%);
                font-weight: lighter;     
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
                    }},
                ],
                dom: 'lBfrtip',
                stateSave: true,
                responsive: true,
                buttons: {
                    dom: {
                        button: {
                            tag: 'button',
                            className: ''
                        }
                    },
                    buttons: [
                        {
                            text: "Select All",
                            action: function (e, dt, node, config) {
                                dt.rows({filter: 'applied'}).select();
                            },
                            className: "btn btn-sm btn-primaryrc",
                            titleAttr: "Select all rows that have not been filtered"
                        },
                        {
                            text: "Select None",
                            extend: "selectNone",
                            className: "btn btn-sm btn-primaryrc",
                            titleAttr: "Remove all selections"
                        },
                        {
                            extend: 'searchPanes',
                            config: {
                                cascadePanes: true
                            },
                            className: "btn btn-sm btn-primaryrc",
                            titleAttr: "Simple filter interface"
                            
                        },
                        {
                            extend: 'searchBuilder',
                            className: "btn btn-sm btn-primaryrc",
                            titleAttr: "Build complex filters"
                        },
                        {
                            extend: 'colvis',
                            className: "btn btn-sm btn-primaryrc",
                            titleAttr: "Show or hide columns in this table"
                        },
                        {
                            text: 'Restore Default',
                            action: function (e, dt, node, config) {
                                dt.state.clear();
                                window.location.reload();
                            },
                            className: "btn btn-sm btn-danger",
                            titleAttr: "Remove all filter, sort, and column visibility settings. Restores whole table to default"
                        }
                    ]
                },
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

            function makeSuccess(message) {
                return Swal.fire({
                    icon: 'success',
                    title: 'Import successful!',
                    html: message,
                    showConfirmButton: false,
                    allowEnterKey: false
                });
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
                            return suffix.replace(/[^a-zA-Z0-9_]/g, '');
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

            function makeFieldHtml(fieldArray) {
                let div;
                let outerdiv;
                let nFields = fieldArray.length;
                if (nFields > 10) {
                    div = `<div class="fieldArray" style="display:none;">${fieldArray.join('<br>')}</div>`
                    message = `Display ${nFields} Fields`;
                    outerdiv = `<div><button onclick="(function(elt) {
                        $(elt).siblings('.fieldArray').toggle({duration:500});
                        $(elt).text(function(i, text){
                            return text === 'Hide Fields' ? '${message}' : 'Hide Fields';
                        });
                    })(this)">${message}</button>${div}</div>`;
                } else {
                    div = `<div class="fieldArray">${fieldArray.join('<br>')}</div>`
                    outerdiv = `<div>${div}</div>`
                }
                return outerdiv; 
            }

            function importDictionary(dictionary) {
                let postData = JSON.stringify(dictionary);
                let formData = new FormData();
                formData.append("dictionary", postData);

                $.ajax({
                    type: "POST",
                    url: "<?= $module->getUrl('src/php/importDictionary.php') ?>",
                    data: formData,
                    processData: false,
                    contentType: false,
                }).then(function(result) {
                    Swal.close();
                    console.log(result);
                    result = JSON.parse(result);
                    if (result.success) {
                        makeSuccess();
                    } else {
                        console.log(result);
                        makeError(result);
                    }
                }).catch(function(err) {
                    Swal.close();
                    makeError(err);
                })
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
                    if (res.success) {
                        Swal.fire({
                            //icon: "warning",
                            iconHtml:"<i class=\"fas fa-clipboard-check\"></i>", 
                            iconColor:"#aed130",
                            title: "Confirm Changes",
                            html: "Below is a table with the forms and fields to be added.<br>" + 
                                  "Clicking the <strong>Confirm</strong> button below will add these to the current project.<br><br>" +
                                  `<table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>Forms</th>
                                            <th>Fields</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Existing</td>
                                            <td>${res.result.orig_forms.join('<br>')}</td>
                                            <td>${makeFieldHtml(res.result.orig_fields)}</td>
                                        </tr>
                                        <tr>
                                            <td>To be added</td>
                                            <td>${res.result.new_forms.join('<br>')}</td>
                                            <td>${makeFieldHtml(res.result.new_fields)}</td>
                                        </tr>
                                    </tbody>
                                   </table>`,
                            showCancelButton: true,
                            confirmButtonText: "Confirm"
                        })
                        .then(function(response) {
                            if (response.isConfirmed) {
                                makeLoading();
                                importDictionary(res.dictionary);
                            }
                        })
                        .catch(function(err) {
                            Swal.close();
                            makeError(err);
                        });
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