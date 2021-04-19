//FileSaver
(function(a, b) {
    if ("function" == typeof define && define.amd) define([], b);
    else if ("undefined" != typeof exports) b();
    else { b(), a.FileSaver = { exports: {} }.exports }
})(this, function() {
    "use strict";

    function b(a, b) { return "undefined" == typeof b ? b = { autoBom: !1 } : "object" != typeof b && (console.warn("Deprecated: Expected third argument to be a object"), b = { autoBom: !b }), b.autoBom && /^\s*(?:text\/\S*|application\/xml|\S*\/\S*\+xml)\s*;.*charset\s*=\s*utf-8/i.test(a.type) ? new Blob(["\uFEFF", a], { type: a.type }) : a }

    function c(a, b, c) {
        var d = new XMLHttpRequest;
        d.open("GET", a), d.responseType = "blob", d.onload = function() { g(d.response, b, c) }, d.onerror = function() { console.error("could not download file") }, d.send()
    }

    function d(a) {
        var b = new XMLHttpRequest;
        b.open("HEAD", a, !1);
        try { b.send() }
        catch (a) {}
        return 200 <= b.status && 299 >= b.status
    }

    function e(a) {
        try { a.dispatchEvent(new MouseEvent("click")) }
        catch (c) {
            var b = document.createEvent("MouseEvents");
            b.initMouseEvent("click", !0, !0, window, 0, 0, 0, 80, 20, !1, !1, !1, !1, 0, null), a.dispatchEvent(b)
        }
    }
    var f = "object" == typeof window && window.window === window ? window : "object" == typeof self && self.self === self ? self : "object" == typeof global && global.global === global ? global : void 0,
        a = /Macintosh/.test(navigator.userAgent) && /AppleWebKit/.test(navigator.userAgent) && !/Safari/.test(navigator.userAgent),
        g = f.saveAs || ("object" != typeof window || window !== f ? function() {} : "download" in HTMLAnchorElement.prototype && !a ? function(b, g, h) {
            var i = f.URL || f.webkitURL,
                j = document.createElement("a");
            g = g || b.name || "download", j.download = g, j.rel = "noopener", "string" == typeof b ? (j.href = b, j.origin === location.origin ? e(j) : d(j.href) ? c(b, g, h) : e(j, j.target = "_blank")) : (j.href = i.createObjectURL(b), setTimeout(function() { i.revokeObjectURL(j.href) }, 4E4), setTimeout(function() { e(j) }, 0))
        } : "msSaveOrOpenBlob" in navigator ? function(f, g, h) {
            if (g = g || f.name || "download", "string" != typeof f) navigator.msSaveOrOpenBlob(b(f, h), g);
            else if (d(f)) c(f, g, h);
            else {
                var i = document.createElement("a");
                i.href = f, i.target = "_blank", setTimeout(function() { e(i) })
            }
        } : function(b, d, e, g) {
            if (g = g || open("", "_blank"), g && (g.document.title = g.document.body.innerText = "downloading..."), "string" == typeof b) return c(b, d, e);
            var h = "application/octet-stream" === b.type,
                i = /constructor/i.test(f.HTMLElement) || f.safari,
                j = /CriOS\/[\d]+/.test(navigator.userAgent);
            if ((j || h && i || a) && "undefined" != typeof FileReader) {
                var k = new FileReader;
                k.onloadend = function() {
                    var a = k.result;
                    a = j ? a : a.replace(/^data:[^;]*;/, "data:attachment/file;"), g ? g.location.href = a : location = a, g = null
                }, k.readAsDataURL(b)
            }
            else {
                var l = f.URL || f.webkitURL,
                    m = l.createObjectURL(b);
                g ? g.location = m : location.href = m, g = null, setTimeout(function() { l.revokeObjectURL(m) }, 4E4)
            }
        });
    f.saveAs = g.saveAs = g, "undefined" != typeof module && (module.exports = g)
});

function b64toBlob(b64Data, contentType = '', sliceSize = 512) {
    const byteCharacters = atob(b64Data);
    const byteArrays = [];

    for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        const slice = byteCharacters.slice(offset, offset + sliceSize);

        const byteNumbers = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        const byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
    }

    const blob = new Blob(byteArrays, { type: contentType });
    return blob;
}

function isFormNameInvalid(formName) {
    const re = /[^a-z0-9_]/g;
    return re.test(formName);
}

function addTitle(element, title) {
    $(element).prop('data-toggle','tooltip');
    $(element).prop('title', title);
    $(element).tooltip();
}

function makeError(title, err, duplicates = false) {
    let options;
    if (duplicates) {
        options = {
            icon: 'warning',
            title: 'Warning: Duplicate Field Names',
            html: err,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: `Remove Duplicates`,
            cancelButtonText: `Cancel`,
            denyButtonText: `Keep Duplicates`,
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
                    addTitle(denyButton, 'This leaves all the duplicated fields as-is in the final result.');
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
            icon: 'error',
            title: title,
            html: err
        }
    }
    return Swal.fire(options);
}

let saveFunc;

function makeSuccess(fileData, fileName) {
    saveFunc = () => saveAs(fileData, fileName);
    Swal.fire({
        icon: 'success',
        title: 'Conversion successful!',
        html: `Click <button onclick='saveFunc();' class="btn btn-primary-yale btn-sm">here</button> ` +
            'to download your converted file(s).',
        showConfirmButton: false,
        allowEnterKey: false
    })
}

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

// callback for submit button
function convertFiles(duplicateAction) {
    makeLoading();
    try {
        const fileArray = grabFileData();
        const allInOneToggle = document.querySelector('#customSwitch1');
        const allInOne = !allInOneToggle || allInOneToggle.checked;
        const instrumentZipToggle = document.querySelector('#customSwitch2');
        const instrumentZip = instrumentZipToggle.checked;
        const payload = {
            fileArray: fileArray,
            allInOne: allInOne,
            instrumentZip: instrumentZip,
            duplicateAction: duplicateAction
        };
        console.log(payload)
        var postData = JSON.stringify(payload);
        var formData = new FormData();
        formData.append("payload", postData);
        $.ajax({
            type: "POST",
            url: "https://yaleredcapbot.net:3000?action=NDAConversion",
            data: formData,
            processData: false,
            contentType: false,
        }).then(function(result) {
            Swal.close();
            let res = JSON.parse(result);
            if (!res || !res[0]) makeError('Error creating file.');
            res = res[0];
            if (res.type === "text/csv") {
                let blob = new Blob([res.data], { type: "text/csv;charset=utf-8" });
                makeSuccess(blob, res.file);
            }
            else if (res.type === "application/zip") {
                let blob = b64toBlob(res.data, res.type);
                makeSuccess(blob, res.file);
            }
            else {
                makeError('Error: unknown mimetype in response.');
            }
        }).catch(function(err) {
            Swal.close();
            // 501: duplicate field names found
            // 500: other file conversion errors
            if (err.status == 501) { // duplicates found
                makeError('Error converting files', err.responseText, duplicates = true)
                .then(result => {
                    if (result.isDismissed) return;
                    let duplicateAction = result.isConfirmed ? "remove" : "ignore"; 
                    convertFiles(duplicateAction);
                    makeLoading();
                });
            }
            else {
                makeError('Error converting files', err.responseText);
            }
        });

    }
    catch (error) {
        Swal.close();
        if (error.message === "invalid form name") {
            Swal.fire({
                icon: 'error',
                title: 'Your proposed form name contains an invalid character',
                html: `Form names may only have lower case letters, numbers, and underscores: <b style="color:#f27474;">${error.proposedFormName}</b>`,
                footer: `<span>Try this: <strong><font style="color:#00356b;">${error.proposedFormName.replace(/ /g, '_').toLowerCase().replace(/[^a-z0-9_]/g, '')}</font></strong></span>`
            })
        }
        else {
            makeError('Error converting files');
        }
    }
}

// returns      - array of fileData objects
//                each fileData object has
//                  formName: string from formName input
//                  data: string read from file
function grabFileData() {
    return Array.from($('.fileInfoContainer')).map(el => {
        let proposedFormName = $(el).find('.formNameInput').val();
        if (isFormNameInvalid(proposedFormName)) {
            let error = new Error('invalid form name');
            error.proposedFormName = proposedFormName;
            throw error;
        }
        return {
            data: el.data,
            formName: proposedFormName
        };
    });
}

function removeFile(elem) {
    $(elem).closest("tr").remove();
    if ($('.delete').length === 1) {
        $('#allInOneToggle').remove();
    }
    else
    if ($('.delete').length === 0) {
        $('#fileupload').val("");
        $('#fileupload')[0].dispatchEvent(new Event("change"));
    }
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

function makeFileDisplay(fileData) {
    let formNameLabel = $(`<td style="vertical-align: middle;"><i class="fas fa-times delete" onclick="removeFile(this)"></i> ${fileData.filename}</td>`)
    let formName = filterFieldName(fileData.filename.replace('.csv', ''));
    let formNameInputId = `formNameInput${fileData.filename}`;
    let formNameInput = document.createElement('input');
    $(formNameInput).addClass("formNameInput");
    formNameInput.type = "text";
    formNameInput.id = formNameInputId;
    formNameInput.value = formName;
    formNameInput.addEventListener('blur', (evt) => $(evt.target).val(filterFieldName($(evt.target).val())));
    
    let formNameInputTD = document.createElement('td');
    formNameInputTD.appendChild(formNameInput);
    let fileInfoContainer = $('<tr class="fileInfoContainer">');
    fileInfoContainer.prop('data', fileData.data);
    fileInfoContainer.append(formNameLabel, formNameInputTD);
    
    return fileInfoContainer;
}

function success() {
    Swal.fire({
        title: '<strong>HTML <u>example</u></strong>',
        icon: 'success',
        html: 'You can use <b>bold text</b>, ' +
            '<a href="//sweetalert2.github.io">links</a> ' +
            'and other HTML tags',
        showCloseButton: true,
        showCancelButton: true,
        focusConfirm: false,
        confirmButtonText: '<i class="fa fa-thumbs-up"></i> Great!',
        confirmButtonAriaLabel: 'Thumbs up, great!',
        cancelButtonText: '<i class="fa fa-thumbs-down"></i>',
        cancelButtonAriaLabel: 'Thumbs down'
    })
}

function clearArea() {
    const fileContainer = $('#fileDisplay')[0];
    while (fileContainer.firstChild) {
        fileContainer.removeChild(fileContainer.firstChild);
    }
    $('#send').remove();
    $('#allInOneToggle').remove();
    $('#instrumentZipToggle').remove();
    $(fileContainer).hide();
}

$(document).ready(function() {
    // Make it look nice
    $('button[name="submit-btn-saverecord"]').remove();
    $('#changeFont').remove();
    $('#survey_logo').addClass('center');
    $('#pagecontainer').css('max-width', '60%');
    
    
        const input = $('#fileupload')[0];
        const fileContainer = $('#fileDisplay');
        input.addEventListener('change', () => {
            const curFiles = input.files;
            clearArea();
            if (curFiles.length > 0) {
                fileContainer.show();
                fileContainer.append($('<h4 style="text-align:center;">Files to Convert</h4>'));
                let fileContainerTooltip = "This controls what each instrument will be called in REDCap.\nThe names must only contain lower-case letters, numbers, and underscores.";
                fileContainer.append($(`<table id="fileTable" style="width:100%"><th></th><th data-toggle="tooltip" data-placement="top" title="${fileContainerTooltip}" style="text-align: center; margin-bottom:15px;" colspan="1">What should the form be called?</th></table>`));

                for (const file of curFiles) {

                    let reader = new FileReader();
                    reader.readAsText(file);
                    reader.onload = function() {
                        let fileData = {
                            filename: file.name,
                            data: reader.result
                        };
                        $('#fileTable').append(makeFileDisplay(fileData));
                    };
                    reader.onerror = function() {
                        console.log(reader.error);
                    };

                }
                
                fileContainer.after($(`<button id="send" onclick="convertFiles()" type="button" class="btn btn-large btn-primary-yale center">Convert File${curFiles.length>1?"s":""}</button>`));
                fileContainer.after($(`<div id="instrumentZipToggle" class="custom-control custom-switch center">
                    <input type="checkbox" class="custom-control-input" id="customSwitch2">
                    <label class="custom-control-label center" for="customSwitch2" data-toggle="tooltip" title="Toggle on to produce instrument zip files. Toggle off to create data dictionaries.">Produce Instrument Zip(s)?</label>
                    </div>`));
                if (curFiles.length > 1) {
                    fileContainer.after($(`<div id="allInOneToggle" class="custom-control custom-switch center">
                        <input type="checkbox" class="custom-control-input" id="customSwitch1">
                        <label class="custom-control-label center" for="customSwitch1" data-toggle="tooltip" title="Toggle on to put multiple instruments in the same data dictionary. Toggle off to create separate dictionaries or instrument zips.">Combine the files into one?</label>
                    </div>`));
                    $('#customSwitch1').on('change', (evt) => { if (evt.target.checked) $('#customSwitch2')[0].checked = false; });
                    $('#customSwitch2').on('change', (evt) => { if (evt.target.checked) $('#customSwitch1')[0].checked = false; });
                }
                $(function() {
                    $('[data-toggle="tooltip"]').tooltip();
                    
                });
            }
        });
        $('#fileupload_btn').on("click", () => { $('#fileupload').click() });
    

});
