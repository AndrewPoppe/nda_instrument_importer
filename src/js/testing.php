<?php
$payload = json_decode($_POST['payload']);
#exit(json_encode($payload));
$file = $payload->fileArray[0];
#http_response_code(400);
#exit(json_encode("$payload->allInOne"));
//exit(json_encode([array('ok'=>'yes', 'data'=>$file->data, 'type'=>"text/csv", 'file'=>$file->formName."_2.csv")]));

$array = [['c1','c2','c3'],[1,2,3],[2,3,4]];
//array_to_csv_download($array);


exit(json_encode(array(
    "type" => "text/csv",
    "data" => convert_all_in_one($payload),
    "file" => "test.csv"
)));

/**
 * Throw an error with the 
 * 
 */

/**
 * Convert an Array into a CSV string
 * 
 * This function works with a single row of data.
 * By default it will not include a header row. If you want a header, it will 
 * grab the values from the array keys of the $row parameter. 
 *
 * @param array $row The Array to formatted as CSV.
 * @param array $header The array of explicitly defined header values.
 * @param bool $doHeader Whether to write a header row.
 * 
 * @return string The formatted CSV string.
 */
function array_to_csv(array $row, array $header = NULL, bool $doHeader = FALSE) {
    $outstream = fopen("php://temp", 'r+');
    $header = $header ?? array_keys($arr[0]);
    $doHeader && fputcsv($outstream, $header, ',', '"');
    fputcsv($outstream, $row, ',', '"');
    rewind($outstream);
    $csv = fgets($outstream);
    fclose($outstream);
    return $csv;
}
 
/**
 * Convert a CSV String into an associative array with headers as keys.
 *
 * @param string $str The CSV formatted string.
 * @return array The CSV string as Array.
 */
function csv_to_array(string $str) {
    $data = str_getcsv($str, "\n");
    $result = array();
    $nrows = count($data);
    $header = str_getcsv($data[0], ",");
    for ($i = 1; $i < $nrows; $i++) {
        $row = str_getcsv($data[$i], ",");
        $assoc_row = array();
        for ($j = 0; $j < count($header); $j++) {
            $assoc_row[$header[$j]] = $row[$j];
        }
        array_push($result, $assoc_row);
    }
    return $result;
}

function get_header(string $str) {
    $data = str_getcsv($str, "\n");
    return str_getcsv($data[0], ",");
}

function convert_all_in_one($fileObject) {
    $fieldArray = [];
    $header = get_header($fileObject->fileArray[0]->data);
    $csvString = array_to_csv($header);

    foreach($fileObject->fileArray as $fileData) {
        $csvDat = csv_to_array($fileData->data);

        foreach ($csvDat as $row) {
            $csvString .= array_to_csv($row);
        }
    }
    return $csvString;
}

?>