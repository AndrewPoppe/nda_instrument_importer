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
        <link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl("src/css/fonts.css") ?>">
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
		<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jszip-2.5.0/dt-1.10.24/af-2.3.5/b-1.7.0/b-colvis-1.7.0/b-html5-1.7.0/b-print-1.7.0/rg-1.1.2/sb-1.0.1/sp-1.2.2/sl-1.3.3/datatables.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <script src="<?php echo $module->getUrl("lib/jquery-ui-1.12.1/jquery-ui.min.js") ?>"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta3/dist/js/bootstrap.bundle.min.js" integrity="sha384-JEW9xMcG8R+pH31jmWH6WWP0WintQrMb4s7ZOdauHnUtxwoG2vI5DkLtS3qm9Ekf" crossorigin="anonymous"></script>
        <script src="<?php echo $module->getUrl("src/js/main.js") ?>" defer></script>
    </head>
    <body>
    <div id="ndaSearch" class="dataTableParentHidden">
				<br/>
				<table id="ndaSearchTable" class="dataTable">
				<thead>
					<tr>
						<th></th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
				</table>
			</div>
    </body>