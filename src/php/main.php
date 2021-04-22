<?php

   

?>
<!doctype html>
<html lang="en">
    <head>
        <link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("lib/jquery-ui-1.12.1/jquery-ui.min.css") ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("lib/jquery-ui-1.12.1/jquery-ui.structure.css") ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("lib/jquery-ui-1.12.1/jquery-ui.theme.min.css") ?>">
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.css"/>
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.3/css/all.css" integrity="sha384-SZXxX4whJ79/gErwcOYf+zWLeJdY/qpuqC4cAa9rOGUstPomtqpuNWT9wdPEn2fk" crossorigin="anonymous"><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-eOJMYsd53ii+scO/bJGFsiCZc+5NDVN2yr8+0RDqr0Ql0h+rP48ckxlpbzKgwra6" crossorigin="anonymous">
        <link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("src/css/main.css") ?>">
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <script src="<?php echo $module->getUrl("lib/jquery-ui-1.12.1/jquery-ui.min.js") ?>"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
        <script src="<?php echo $module->getUrl("src/js/main.js") ?>" defer></script>
    </head>
    <body style="background:#00356b;">
        <div id="pagecontainer" class="center">
            <div class="center big">
            <div id="surveytitlelogo">
	<table style="width:100%;max-width:100%;" cellspacing="0">
		<tbody><tr>
			<td valign="top">
				<div style="padding:10px 0 0;">
                    <img id="logo" src="<?=$module->getUrl("img/logo.png");?>" alt="image" title="image" style="max-width:600px;width:600px;max-width:600px;height:105px;max-height:105px;" class="center">
                </div>
            </td>
        </tr></tbody>
    </table>
</div>
            <div id="surveyinstructions">
	<div class="info-container">
	<p><strong>Use this tool to convert data dictionaries from the <a title="NIMH Data Archive" href="https://nda.nih.gov/data_dictionary.html?source=NDA&amp;submission=ALL" target="_blank" rel="noopener noreferrer">NIMH Data Archive</a> into REDCap-compatible data dictionaries and/or instrument zip files. </strong></p>
	<p>Select one or more NDA data dictionary .csv files below. You will be prompted to provide a name for each file you upload. This will serve as the instrument name in the REDCap data dictionary/instrument zip file.</p>
	<p>If you select more than one file, you will have the option to combine all the files into one REDCap data dictionary or to one data dictionary/instrument zip file for each input file.</p>
	</div>
</div>
                <p class="center" style="text-align:center;">Upload your NDAR csv file(s)</p>
                <input id="fileupload" type="file" class="center" style="text-align:center;display:none;" accept="text/csv" multiple />
                <input id="fileupload_btn" type="button" class="center" value="Select Files..." style="text-align: center;"/>
                <div id="fileDisplay"></div>
            </div>
        </div>
    </body>
</html>