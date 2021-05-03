<?php
$payload = json_decode($_POST['payload'], true);

$project = $module->getProject();
$fieldArray = [];

/**
 * 
 * payload has these fields:
 *   - fileArray        : array of file arrays, each has data and formName
 *   - duplicateAction  : string what action to take given duplicate field names
 *                        Can be undefined, "rename", "delete"
 *   - renameSuffix     : string what suffix to append to duplicate field names
 *                        provided duplicateAction is "rename"
 */

// Fields to test csv data against
// Keys are the JSON keys,
// Values are the column headers for CSV
$matchFields = [
	"name"          => "ElementName", 
	"type"          => "DataType", 
	"required"      => "Required", 
	"description"   => "ElementDescription", 
	"valueRange"    => "ValueRange", 
	"notes"         => "Notes"
];

/**
 * Throw an error with the provided array.
 * 
 * @param string $message Describe the error
 * @param int $code Error code for the browser
 * 
 * @return void
 */
function throw_error(string $message, int $code = 500) {
    http_response_code($code);
    exit($message);
}

function reformat_array(array $data, array $header) {
    return array_map(function($row) use ($header) {
        $result = [];
        foreach ($header as $field) {
           $result[$field] = $row[$field];
        }
        return $result;
    }, $data);
}

function combine_dicts_to_file(array $newData, array $oldDD, string $filename) {
    $header = array_keys($newData[0]);
    $dd = reformat_array($oldDD, $header);
    $outstream = fopen($filename, 'w');
    $success = TRUE;
    if ($outstream === FALSE) {
        return FALSE;
    }
    try {
        fputcsv($outstream, $header, ',', '"');
        foreach($oldDD as $ddrow) {
            fputcsv($outstream, $ddrow, ',', '"');
        }
        foreach($newData as $dataRow) {
            fputcsv($outstream, $dataRow, ',', '"');
        }
    }
    catch (exception $e) {
        $success = FALSE;
    }
    finally {
        fclose($outstream);
        return $success;
    }
}
 

function array_duplicates(array $arr) {
    return array_unique(array_diff_assoc($arr,array_unique($arr)));
}


/**
 * Verify input format/fields
 * 
 * @param array $csvArr Parsed array of csv values
 * @param array $fields Array of field names to test against
 * 
 * @return bool Whether or not input is valid
 */
function inputIsValid(array $csvArr, array $fields) {
    foreach ($fields as $field=>$label) {
        foreach ($csvArr as $row) {
            if (!in_array($field, array_keys($row), true)) {
                return false;
            }
        }
    }
    return true;
}


/**
 * Given array of 
 * 
 * @param array $arr
 * 
 * @return [type]
 */
function getRanges(array $arr) {
    return array_map(function($element1) {
        $bounds = explode('::', $element1);
        $bounds = array_map(function($bound) {
            return trim(preg_replace("/NDAR.*/", "", $bound));
        }, $bounds);
        if (count($bounds) === 2 && is_numeric($bounds[0]) && is_numeric($bounds[1])){
            $val1 = intval($bounds[0]);
            $val2 = intval($bounds[1]);
            return range(min($val1, $val2), max($val1, $val2));
        } else {
            return $bounds;
        }
    }, $arr);
}


function reduceRanges(array $arr) {
    return array_reduce($arr, function($res, $item) {
        $item = array_map('strval', $item);
        return array_merge($res, $item);
    }, []);
}


function parseValueRange(string $vr) {
    $arr = explode(';', $vr);
    $ranges = getRanges($arr);
    $reducedRanges = reduceRanges($ranges);
    return array_unique($reducedRanges);
}


function clean($val) {
    return preg_replace('/^[,;\'"\s]*|[,;\'"\s]*$/', '', $val);
}

function reMatch(string $note, string $parsedVrString, string $re) {
    preg_match_all($re, $note, $matches, PREG_SET_ORDER);
    return array_map(function($match) {
        return array(
            "key" => clean($match["key"]),
            "value" => clean($match["val"])
        );
    }, $matches);
}

function delimMatch(string $note, array $parsedVr, string $delimiter) {
    $split = preg_split("/[${delimiter}]/", $note);
    $result = array_filter($split, function($el){
        return strpos($el, '=') !== FALSE;
    });
    $result = array_map(function($whole) use ($parsedVr) {
        $parts = explode('=', $whole);
        $parts = array_map('trim', $parts);
        if (in_array($parts[1], $parsedVr, true)) {
            $parts = array_reverse($parts);
        }
        if (in_array($parts[0], $parsedVr, true)) {
            return array(
                "key" => clean($parts[0]),
                "value" => clean($parts[1])
            );
        }
        return NULL;
    }, $result);

    return array_filter($result, function($piece) {
        return !is_null($piece);
    });
}


function chooseMatches(array $matches, array $parsedVr, string $note) {
    $pvrl = count($parsedVr);
    $eqs  = substr_count($note, '=');
    if ($eqs === $pvrl) {
        $matches = array_filter($matches, function($match) use ($eqs) {
            return (count($match) === intval($eqs));
        });
        $matches = reset($matches);
        
    } else {
        // TODO: Do we need to sort? If so, can we re-order them at the end to match input data?
        usort($matches, function ($a, $b) {
            return count($b) - count($a);
        });
        
        $matches = reset($matches);
        
        if (count($matches) > 0) {

            // If we have matches with duplicate keys, merge them
            $matches = array_reduce($matches, function ($acc, $el) {
                $duplicate = FALSE;
                foreach ($acc as $i=>$acc_el) {
                    if ($acc_el["key"] === $el["key"]) {
                        $acc[$i]["value"] .= "; " . $el["value"];
                        $duplicate = TRUE;
                    }
                }
                if (!$duplicate) array_push($acc, $el);
                return $acc;
            }, []);

            // If we have more values without matches, create them
            foreach ($parsedVr as $val) {
                if (in_array($val, array_column($matches, "key"), true)) {
                    continue;
                }
                $key = preg_replace('/[^0-9A-Za-z._\-]/', '', $val);
                if ($key !== "") {
                    array_push($matches, array(
                        "key" => $key,
                        "value" => $val
                    ));
                }
            }
        } else if ($pvrl < 20) {
            foreach ($parsedVr as $i=>$val) {
                $key = (string) (is_numeric($val) ? (int)$val : intval($i)+1);
                array_push($matches, array(
                    "key" => $key,
                    "value" => $val
                ));
            }
        } else {
            // This should catch situations where there is just a range of values with no labels (greater than 20 of them, see else if above)
            $matches = NULL;
        }

    }
    $finalMatches = "";
    $fieldNote = $note;
    if ($matches) {
        $finalMatches = array_map(function($match) {
            return $match["key"] . ", " . $match["value"];
        }, $matches);
        natcasesort($finalMatches);
        $finalMatches = implode(' | ', $finalMatches);
        $fieldNote = array_map(function($match) {
            return $match["key"] . " = " . $match["value"];
        }, $matches);
        natcasesort($fieldNote);
        $fieldNote = implode('; ', $fieldNote);
    }

    return array (
        "matches" => $finalMatches,
        "fieldNote" => $fieldNote
    );
}


function parseNote(string $note, array $parsedVr = []) {
    $parsedVr = array_map(function($el) {
        if ($el === "") return "";
        $result = preg_replace('/[^0-9A-Za-z._\-]/', '', trim($el));
        if ($result === "") $result = "custom".ord(trim($el));
        return $result;
    }, $parsedVr);
    $parsedVrString = array_map(function($el) {
        return addcslashes($el, '\\');
    }, $parsedVr);
    $parsedVrString = implode('|', $parsedVrString);
    
    if ($parsedVrString === "") return "";

    $matchesFore    = reMatch($note, $parsedVrString, "/(?<key>${parsedVrString})\s*=(?<val>(?:(?!(${parsedVrString})\s*=).)*)/");
    $matchesBack    = reMatch($note, $parsedVrString, "/(?<val>(?:(?!\s*=\s*(${parsedVrString})).)*)\s*=\s*(?<key>${parsedVrString})/");
    $matches_sc     = delimMatch($note, $parsedVr, ';');
    $matches_c      = delimMatch($note, $parsedVr, ',');
    $matchs_scc     = delimMatch($note, $parsedVr, ';,');
    $allMatches     = [$matchesFore, $matchesBack, $matches_sc, $matches_c, $matchs_scc];

    $matches = chooseMatches($allMatches, $parsedVr, $note);

    return $matches;
}


function parseBounds(string $valueRange, $parsedNote) {
    $bounds = explode('::', $valueRange);
    $bounds = array_map(function($e) {
        
        return trim(preg_replace('/NDAR.*/', '', $e));
    }, $bounds);
    
    if (!$parsedNote && count($bounds) == 2 && is_numeric($bounds[0]) && is_numeric($bounds[1])) {
        sort($bounds);
        return $bounds;
    } else {
        return [NULL, NULL];
    }
}


function getFieldType(string $parsedNote) {
    return $parsedNote ? "dropdown" : "text";
}


function getTextValidation(string $dataType, string $fieldType) {
    if ($fieldType !== "text") return NULL;
    switch ($dataType) {
        case "Date":
        return "date_mdy";
            break;
        case "Integer":
            return "integer";
            break;
        case "Float":
            return "number";
            break;
        default:
            return NULL;
	}
}

function getFieldValue(array $row, string $fieldName) {
    $value = $row[$fieldName];
    return $value ?? "";
}

function setFieldValue(array $row, string $fieldName, $value) {
    $row[$fieldName] = $value;
    return $row;
}

/**
 * Create a single data dictionary.
 * 
 * This does not create a header row so that combining multiple
 * files is easier.
 * 
 * @param array $csvArr Array of CSV values.
 * @param string $form Name of the form
 * @param string $duplicateAction What to do with duplicates:
 *                  ignore: keep duplicates in the data
 *                  remove: remove any field that already exists in the fieldArray
 * @param string $renameSuffix The suffix to append to field name when renaming
 * 
 * @return array Associative array representing data dictionary
 */
function createDataDictionary(array $csvArr, string $form, string $duplicateAction = "", $renameSuffix = "") {
    global $fieldArray;

    // populate fieldArray array as we go to detect duplicates
    if ($duplicateAction === "remove") {
        $csvArr = array_filter($csvArr, function ($field) use (&$fieldArray) {
            $elementName = getFieldValue($field, "name");
            $result = !in_array($elementName, $fieldArray, true);
            $result && array_push($fieldArray, $elementName);
            return $result;
        });
    } else if ($duplicateAction === "rename") {
        $csvArr = array_map(function($field) use (&$fieldArray, $form, $renameSuffix) {
            $suffix = $renameSuffix !== "" ? $renameSuffix : "_".$form;
            $elementName = getFieldValue($field, "name");
            $in_array = in_array($elementName, $fieldArray, true);
            while ($in_array) {
                $elementName = $elementName.$suffix;
                $in_array = in_array($elementName, $fieldArray, true);
            }
            $field = setFieldValue($field, "name", $elementName);
            array_push($fieldArray, $elementName);
            return $field;
        }, $csvArr);
    } else {
        foreach ($csvArr as $field) {
            $elementName = getFieldValue($field, "name");
            array_push($fieldArray, $elementName);
        }
    }

    // Create the Data Dictionary
    $result = array_map(function($field) use ($form, &$results) {
        $elementName        = getFieldValue($field, "name");
        $notes              = getFieldValue($field, "notes");
        $valueRange         = getFieldValue($field, "valueRange");
        $type               = getFieldValue($field, "type");
        $description        = getFieldValue($field, "description");
        $required           = getFieldValue($field, "required");
        
        $note               = preg_replace('/[\n\r]/', '', $notes);
        $pvr                = parseValueRange($valueRange);
        $parsed             = parseNote($note, $pvr);
        $parsedNote         = $parsed["matches"];
        $fieldNote          = $parsed["fieldNote"];
        $bounds             = parseBounds($valueRange, $parsedNote);
        $field_type         = getFieldType($parsedNote);
        $text_validation    = getTextValidation($type, $field_type);
        
        return array (
            "field_name"                                    => $elementName,
            "form_name"                                     => $form,
            "section_header"                                => NULL,
            "field_type"                                    => $field_type,
            "field_label"                                   => $description,
            "select_choices_or_calculations"                => $parsedNote,
            "field_note"                                    => $fieldNote,
            "text_validation_type_or_show_slider_number"    => $text_validation,
            "text_validation_min"                           => $bounds[0],
            "text_validation_max"                           => $bounds[1],
            "identifier"                                    => NULL,
            "branching_logic"                               => NULL,
            "required_field"                                => $required === "Required" ? "y" : NULL,
            "custom_alignment"                              => NULL,
            "question_number"                               => NULL,
            "matrix_group_name"                             => NULL,
            "matrix_ranking"                                => NULL,
            "field_annotation"                              => $note
        );
    }, $csvArr);

    return $result;
}



function convert_all_in_one($fileObject) {
    global $matchFields, $fieldArray;
    $duplicateAction = $fileObject["duplicateAction"] ?? "";
    $renameSuffix = $fileObject["renameSuffix"] ?? "";
    
    $finalResult = array();

    foreach($fileObject["fileArray"] as $fileData) {
        $data = $fileData["data"];
    
        if (!inputIsValid($data, $matchFields)) {
            $message = 'Error: Input file is not valid: ' . \REDCap::escapeHtml($fileData["formName"]);
            throw_error($message);
        }

        $result = createDataDictionary($data, $fileData["formName"], $duplicateAction, $renameSuffix);
        $finalResult = array_merge($finalResult, $result);
    }

    $duplicates = array_duplicates($fieldArray);
    if (count($duplicates) && !$duplicateAction) {
        throw_error('<strong>The following field names were duplicated in your'
        .' file(s)</strong>:<br>' . implode('<br>', $duplicates), 501);
    }

    return $finalResult;
}

function read_csv(string $filename) {
    $file = fopen($filename, 'r');
    $result = [];
    while (($data = fgetcsv($file)) !== FAlSE) {
        array_push($result, $data);
    }
    return $result;
}

function get_fields(array $dd) {
    $fields = [];
    foreach ($dd as $row) {
        $val = $row["field_name"] ?? $row[0];
        array_push($fields, $val);
    }
    return array_unique($fields);
}

function get_forms(array $dd) {
    $forms = [];
    foreach ($dd as $row) {
        $val = $row["form_name"] ?? $row[1];
        array_push($forms, $val);
    }
    return array_unique($forms);
}

function checkNewDict(array $combined_dd, array $orig_fields, array $orig_forms, array $new_dd) {
    
    $result = [
        "orig_fields" => [],
        "orig_forms" => [],
        "new_fields" => [],
        "new_forms" => []
    ];

    // fields and forms for instruments to be added
    $new_fields = get_fields($new_dd);
    $new_forms = get_forms($new_dd);

    // fields and forms grabbed from the combined dd (this is what we're testing)
    $combined_fields = get_fields($combined_dd);
    $combined_forms = get_forms($combined_dd);

    // Check that combined dd has all original forms and fields
    foreach ($orig_fields as $orig_field) {
        if (preg_match("/_complete$/", $orig_field)) continue;
        if (in_array($orig_field, $combined_fields, true)) {
            array_push($result["orig_fields"], $orig_field);
        } else {
            return FALSE;
        }
    }
    foreach ($orig_forms as $orig_form=>$orig_label) {
        if (in_array($orig_form, $combined_forms, true)) {
            array_push($result["orig_forms"], $orig_form);
        } else {
            return FALSE;
        }
    }

    // Check that combined dd has all new forms and fields
    foreach ($new_fields as $new_field) {
        if (preg_match("/_complete$/", $new_field)) continue;
        if (in_array($new_field, $combined_fields, true)) {
            array_push($result["new_fields"], $new_field);
        } else {
            return FALSE;
        }
    }
    foreach ($new_forms as $new_form) {
        if (in_array($new_form, $combined_forms, true)) {
            array_push($result["new_forms"], $new_form);
        } else {
            return FALSE;
        }
    }

    return $result;
    
}

function convert($fileObject) {
    global $project, $module, $fieldArray;
    
    $dd_array = \REDCap::getDataDictionary('array');
    $current_fields = \REDCap::getFieldNames();
    $current_forms = \REDCap::getInstrumentNames();

    $fieldArray = array_merge($fieldArray, $current_fields);
    
    $result = convert_all_in_one($fileObject);
    
    $filename = APP_PATH_TEMP."dd.csv";
    $combine_status = combine_dicts_to_file($result, $dd_array, $filename);
    if ($combine_status === FALSE) {
        throw_error("Failure to produce combined data dictionary.");
    }

    // Prepare a confirmation if things look good
    $newDictArr = read_csv($filename);
    $check = checkNewDict($newDictArr, $current_fields, $current_forms, $result);
    if (!$check) {
        throw_error("There was an error in the combined data dictionary.");
    }

    exit(json_encode(["result" => $check, "success" => TRUE, "dictionary" => $newDictArr]));

    $project_id = $project->getProjectId();
}

convert($payload);

?>