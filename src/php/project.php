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
        </div>
        <script>
            $('#ndaSearchTable').DataTable({
                "ajax": {
                    "url": "https://nda.nih.gov/api/datadictionary/datastructure",
                    "dataSrc": ""
                },
                "columns": [
                    {data: "shortName" },
                    {data: "title" },
                    {data: "sources" },
                    {data: "categories" },
                    {data: "dataType" },
                    {data: "status" },
                    {data: "publicStatus" },
                    {data: "publishDate" },
                    {data: "modifiedDate" }
                ]
            });
        </script>
    </body>