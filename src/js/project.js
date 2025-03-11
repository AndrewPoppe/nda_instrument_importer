(function () {

    let fileArray = [];
    let searchTable = $('#ndaSearchTable').DataTable({
        ajax: {
            url: "https://nda.nih.gov/api/datadictionary/datastructure",
            dataSrc: ""
        },
        deferRender: true,
        showLoading: true,
        columns: [
            { data: null, "defaultContent": "" },
            { data: "shortName", "defaultContent": "" },
            { data: "title", "defaultContent": "" },
            { data: "sources[; ]", "defaultContent": "" },
            { data: "categories", render: "[; ]", "defaultContent": "" },
            { data: "dataType", "defaultContent": "" },
            { data: "status", "defaultContent": "" },
            { data: "publicStatus", "defaultContent": "" },
            {
                data: "publishDate",
                type: "date", "defaultContent": "",
                render: function (data, type, row, meta) {
                    if (!data) return "";
                    if (type === "display") {
                        return new Date(data).toLocaleString();
                    }
                    return data;
                }
            },
            {
                data: "modifiedDate",
                type: "date", "defaultContent": "",
                render: function (data, type, row, meta) {
                    if (!data) return "";
                    if (type === "display") {
                        return new Date(data).toLocaleString();
                    }
                    return data;
                }
            },
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
                        dt.rows({ filter: 'applied' }).select();
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
            style: 'multi',
            selector: 'td:first-child'
        },
        order: [
            [1, 'asc']
        ]
    });

    document.querySelectorAll('.dt-buttons button').forEach(function (elt) {
        addTitle(elt, elt.title);
    });

    searchTable.on('select deselect', function (e, dt, type, indexes) {
        if (dt.rows({ selected: true }).any()) {
            $('#addFormsButton').prop('disabled', false);
        } else {
            $('#addFormsButton').prop('disabled', true);
        }
    });
    $('#addFormsButton').on('click', function (e) {
        makeLoading();
        let rows = searchTable.rows({ selected: true });
        let nRows = rows.count();
        if (nRows > 20) {
            Swal.fire({
                icon: 'error',
                title: 'Error: Too many instruments selected',
                html: `${nRows} instruments is a lot! Choose fewer.`
            });
        } else {
            let ajaxPromises = [];
            let results = [];
            searchTable.rows({ selected: true }).every(function (rowIdx, tableLoop, rowLoop) {
                let data = this.data();
                let instrument_name = data.shortName;

                ajaxPromises.push(
                    $.ajax({
                        type: "GET",
                        url: `https://nda.nih.gov/api/datadictionary/datastructure/${instrument_name}`,
                    }).then(function (result) {
                        results.push({
                            data: result.dataElements,
                            formName: result.shortName
                        });
                    }).catch(function (err) {
                        makeError(err);
                    })
                );
            });
            $.when(...ajaxPromises)
                .then(function (data, textStatus, jqXHR) {
                    fileArray = results;
                    convert(results);
                })
                .catch(function (err) {
                    makeError(err);
                });
        }
    });
    searchTable.on('buttons-action',
        function (e, buttonApi, dataTable, node, config) {
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
        $(element).prop('data-toggle', 'tooltip');
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
                        const denyButton = document.querySelector('.swal2-deny');
                        addTitle(confirmButton, 'This removes all duplicated fields from the final result, leaving only the first occurence of the field.');
                        addTitle(denyButton, 'This changes the field names of duplicated fields in the final result.\nYou will be prompted to provide a suffix for the new field names.');
                    }
                },
                willClose: () => {
                    const confirmButton = document.querySelector('.swal2-confirm');
                    const denyButton = document.querySelector('.swal2-deny');
                    $(confirmButton).tooltip('hide');
                    $(denyButton).tooltip('hide');
                }
            }
        } else {
            options = {
                icon: "error",
                title: err?.responseJSON?.error || "Error",
                html: err?.responseJSON?.message || err?.responseText || html_entity_decode(err?.error) || err
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
        temp = temp.replace(/[^a-z0-9]/ig, "_");
        temp = temp.replace(/[_]+/g, "_");
        while (temp.length > 0 && (temp.charAt(0) == "_" || temp.charAt(0) * 1 == temp.charAt(0))) {
            temp = temp.substr(1, temp.length);
        }
        while (temp.length > 0 && temp.charAt(temp.length - 1) == "_") {
            temp = temp.substr(0, temp.length - 1);
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
                preConfirm: function (suffix = "") {
                    return suffix.toLowerCase().replace(/[^a-z0-9_]/g, '');
                }
            })
                .then(function (result) {
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

        nda_importer.ajax('import', dictionary)
            .then(function (result) {
                Swal.close();
                console.log(result);
                if (result.success) {
                    makeSuccess();
                } else {
                    console.log(result);
                    makeError(result);
                }
            })
            .catch(function (err) {
                Swal.close();
                makeError(err);
            });
    }


    /**
     * @param mixed messageTxt
     * 
     * @return [type]
     */
    function makeDuplicateFormDialog(messageTxt) {

    }

    function handleDuplicateFormChoice(choice) {

    }

    /**
     * Converts the given file array.
     * 
     * @param {Array} fileArray - The array of files to be converted.
     * @param {string} duplicateAction - The action to be taken in case of duplicate files.
     * @param {string} renameSuffix - The suffix to be added to the renamed files.
     */
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

        nda_importer.ajax('convert', payload)
            .then(function (res) {
                Swal.close();
                if (res.success) {
                    Swal.fire({
                        //icon: "warning",
                        iconHtml: "<i class=\"fas fa-clipboard-check\"></i>",
                        iconColor: "#aed130",
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
                        .then(function (response) {
                            if (response.isConfirmed) {
                                makeLoading();
                                importDictionary(res.dictionary);
                            }
                        })
                        .catch(function (err) {
                            Swal.close();
                            makeError(err);
                        });
                } else {
                    if (501 == res.code) {
                        makeError(res.error, 'Error converting files', duplicates = true)
                            .then(handleDuplicateChoice);
                    }
                    else if (502 == res.code) {
                        makeDuplicateFormDialog(res.error)
                            .then(handleDuplicateFormChoice);
                    }
                    else {
                        makeError(res.error);
                    }
                }
            })
            .catch(function (err) {
                Swal.close();
                makeError(err);
            });
    }
})();
