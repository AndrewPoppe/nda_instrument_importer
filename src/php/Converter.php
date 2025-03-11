<?php

namespace YaleREDCap\NDAImporter;

class Converter
{

    public $module;
    public $payload;
    public $project;
    public $fieldArray = [];
    public $matchFields = [
        "name"        => "ElementName",
        "type"        => "DataType",
        "required"    => "Required",
        "description" => "ElementDescription",
        "valueRange"  => "ValueRange",
        "notes"       => "Notes"
    ];

    public function __construct(NDAImporter $module, array $payload)
    {
        $this->module  = $module;
        $this->payload = $payload;
        $this->project = $module->getProject();
    }

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


    private static function reformat_array(array $data, array $header)
    {
        return array_map(function ($row) use ($header) {
            $result = [];
            foreach ( $header as $field ) {
                $result[$field] = $row[$field];
            }
            return $result;
        }, $data);
    }

    private static function reorder_fields(array $oldDd, array $newData) 
    {
        $result = [];
        $prevForm = "";
        if (empty($newData)) {
            return $oldDd;
        }
        foreach ($oldDd as $oldDdRow) {
            $thisForm = $oldDdRow["form_name"];
            if (empty($prevForm)) $prevForm = $thisForm;
            if ( $prevForm !== $thisForm ) {
                foreach ($newData as $key => $newDataRow) {
                    if ($newDataRow["form_name"] === $prevForm) {
                        $result[] = $newDataRow;
                        unset($newData[$key]);
                    } 
                }
            } 
            $result[] = $oldDdRow;
            $prevForm = $thisForm;
        }
        foreach ($newData as $newDataRow) {
            $result[] = $newDataRow;
        }
        return $result;
    }

    private static function combine_dicts_to_file(array $newData, array $oldDD, string $filename)
    {
        $header    = array_keys(reset($oldDD));
        $dd        = self::reformat_array($oldDD, $header);
        $outstream = fopen($filename, 'w');
        $success   = TRUE;
        if ( $outstream === FALSE ) {
            return FALSE;
        }
        try {
            $combinedData = self::reorder_fields($dd, $newData);
            fputcsv($outstream, $header, ',', '"');
            foreach ( $combinedData as $row ) {
                fputcsv($outstream, $row, ',', '"');
            }
        } catch ( \Throwable $e ) {
            $success = FALSE;
        } finally {
            fclose($outstream);
            return $success;
        }
    }


    private static function array_duplicates(array $arr)
    {
        return array_unique(array_diff_assoc($arr, array_unique($arr)));
    }


    /**
     * Verify input format/fields
     * 
     * @param array $csvArr Parsed array of csv values
     * @param array $fields Array of field names to test against
     * 
     * @return bool Whether or not input is valid
     */
    private static function inputIsValid(array $csvArr, array $fields)
    {
        foreach ( $fields as $field => $label ) {
            foreach ( $csvArr as $row ) {
                if ( !in_array($field, array_keys($row ?? []), true) ) {
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
    private static function getRanges(array $arr)
    {
        return array_map(function ($element1) {
            $bounds = explode('::', $element1);
            $bounds = array_map(function ($bound) {
                return trim(preg_replace("/NDAR.*/", "", $bound));
            }, $bounds);
            if ( count($bounds) === 2 && is_numeric($bounds[0]) && is_numeric($bounds[1]) ) {
                $val1 = intval($bounds[0]);
                $val2 = intval($bounds[1]);
                return range(min($val1, $val2), max($val1, $val2));
            } else {
                return $bounds;
            }
        }, $arr);
    }


    private static function reduceRanges(array $arr)
    {
        return array_reduce($arr, function ($res, $item) {
            $item = array_map('strval', $item);
            return array_merge($res, $item);
        }, []);
    }


    private static function parseValueRange(string $vr)
    {
        $arr           = explode(';', $vr);
        $ranges        = self::getRanges($arr);
        $reducedRanges = self::reduceRanges($ranges);
        return array_unique($reducedRanges);
    }


    private static function clean($val)
    {
        return preg_replace('/^[,;\'"\s]*|[,;\'"\s]*$/', '', $val);
    }

    private static function reMatch(string $note, string $parsedVrString, string $re)
    {
        preg_match_all($re, $note, $matches, PREG_SET_ORDER);
        return array_map(function ($match) {
            return array(
                "key"   => self::clean($match["key"]),
                "value" => self::clean($match["val"])
            );
        }, $matches);
    }

    private static function delimMatch(string $note, array $parsedVr, string $delimiter)
    {
        $split  = preg_split("/[${delimiter}]/", $note);
        $result = array_filter($split, function ($el) {
            return strpos($el, '=') !== FALSE;
        });
        $result = array_map(function ($whole) use ($parsedVr) {
            $parts = explode('=', $whole);
            $parts = array_map('trim', $parts);
            if ( in_array($parts[1], $parsedVr, true) ) {
                $parts = array_reverse($parts);
            }
            if ( in_array($parts[0], $parsedVr, true) ) {
                return array(
                    "key"   => self::clean($parts[0]),
                    "value" => self::clean($parts[1])
                );
            }
            return NULL;
        }, $result);

        return array_filter($result, function ($piece) {
            return !is_null($piece);
        });
    }


    private static function chooseMatches(array $matches, array $parsedVr, string $note)
    {
        $pvrl = count($parsedVr);
        $eqs  = substr_count($note, '=');
        if ( $eqs === $pvrl ) {
            $matches = array_filter($matches, function ($match) use ($eqs) {
                return (count($match) === intval($eqs));
            });
            $matches = reset($matches);

        } else {
            // TODO: Do we need to sort? If so, can we re-order them at the end to match input data?
            usort($matches, function ($a, $b) {
                return count($b) - count($a);
            });

            $matches = reset($matches);

            if ( count($matches) > 0 ) {

                // If we have matches with duplicate keys, merge them
                $matches = array_reduce($matches, function ($acc, $el) {
                    $duplicate = FALSE;
                    foreach ( $acc as $i => $acc_el ) {
                        if ( $acc_el["key"] === $el["key"] ) {
                            $acc[$i]["value"] .= "; " . $el["value"];
                            $duplicate = TRUE;
                        }
                    }
                    if ( !$duplicate ) array_push($acc, $el);
                    return $acc;
                }, []);

                // If we have more values without matches, create them
                foreach ( $parsedVr as $val ) {
                    if ( in_array($val, array_column($matches, "key"), true) ) {
                        continue;
                    }
                    $key = preg_replace('/[^0-9A-Za-z._\-]/', '', $val);
                    if ( $key !== "" ) {
                        array_push($matches, array(
                            "key"   => $key,
                            "value" => $val
                        ));
                    }
                }
            } else if ( $pvrl < 20 ) {
                foreach ( $parsedVr as $i => $val ) {
                    $key = (string) (is_numeric($val) ? (int) $val : intval($i) + 1);
                    array_push($matches, array(
                        "key"   => $key,
                        "value" => $val
                    ));
                }
            } else {
                // This should catch situations where there is just a range of values with no labels (greater than 20 of them, see else if above)
                $matches = NULL;
            }

        }
        $finalMatches = "";
        $fieldNote    = $note;
        if ( $matches ) {
            $finalMatches = array_map(function ($match) {
                return $match["key"] . ", " . $match["value"];
            }, $matches);
            natcasesort($finalMatches);
            $finalMatches = implode(' | ', $finalMatches);
            $fieldNote    = array_map(function ($match) {
                return $match["key"] . " = " . $match["value"];
            }, $matches);
            natcasesort($fieldNote);
            $fieldNote = implode('; ', $fieldNote);
        }

        return array(
            "matches"   => $finalMatches,
            "fieldNote" => $fieldNote
        );
    }


    private static function parseNote(string $note, array $parsedVr = [])
    {
        $parsedVr       = array_map(function ($el) {
            if ( $el === "" ) return "";
            $result = preg_replace('/[^0-9A-Za-z._\-]/', '', trim($el));
            if ( $result === "" ) $result = "custom" . ord(trim($el));
            return $result;
        }, $parsedVr);
        $parsedVrString = array_map(function ($el) {
            return addcslashes($el, '\\');
        }, $parsedVr);
        $parsedVrString = implode('|', $parsedVrString);

        if ( $parsedVrString === "" ) return "";

        $matchesFore = self::reMatch($note, $parsedVrString, "/(?<key>${parsedVrString})\s*=(?<val>(?:(?!(${parsedVrString})\s*=).)*)/");
        $matchesBack = self::reMatch($note, $parsedVrString, "/(?<val>(?:(?!\s*=\s*(${parsedVrString})).)*)\s*=\s*(?<key>${parsedVrString})/");
        $matches_sc  = self::delimMatch($note, $parsedVr, ';');
        $matches_c   = self::delimMatch($note, $parsedVr, ',');
        $matchs_scc  = self::delimMatch($note, $parsedVr, ';,');
        $allMatches  = [ $matchesFore, $matchesBack, $matches_sc, $matches_c, $matchs_scc ];

        $matches = self::chooseMatches($allMatches, $parsedVr, $note);

        return $matches;
    }


    private static function parseBounds(string $valueRange, $parsedNote)
    {
        $bounds = explode('::', $valueRange);
        $bounds = array_map(function ($e) {

            return trim(preg_replace('/NDAR.*/', '', $e));
        }, $bounds);

        if ( !$parsedNote && count($bounds) == 2 && is_numeric($bounds[0]) && is_numeric($bounds[1]) ) {
            sort($bounds);
            return $bounds;
        } else {
            return [ NULL, NULL ];
        }
    }


    private static function getFieldType(string $parsedNote)
    {
        return $parsedNote ? "dropdown" : "text";
    }


    private static function getTextValidation(string $dataType, string $fieldType)
    {
        if ( $fieldType !== "text" ) return NULL;
        switch ($dataType) {
            case "Date":
                return "date_mdy";
            case "Integer":
                return "integer";
            case "Float":
                return "number";
            default:
                return NULL;
        }
    }

    private static function getFieldValue(array $row, string $fieldName)
    {
        $value = $row[$fieldName];
        return $value ?? "";
    }

    private static function setFieldValue(array $row, string $fieldName, $value)
    {
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
    private function createDataDictionary(array $csvArr, string $form, string $duplicateAction = "", $renameSuffix = "")
    {
        // populate fieldArray array as we go to detect duplicates
        if ( $duplicateAction === "remove" ) {
            $csvArr = array_filter($csvArr, function ($field) {
                $elementName = self::getFieldValue($field, "name");
                $result = !in_array($elementName, $this->fieldArray, true);
                $result && array_push($this->fieldArray, $elementName);
                return $result;
            });
        } else if ( $duplicateAction === "rename" ) {
            $csvArr = array_map(function ($field) use ($form, $renameSuffix) {
                $suffix      = $renameSuffix !== "" ? $renameSuffix : "_" . $form;
                $elementName = self::getFieldValue($field, "name");
                $in_array = in_array($elementName, $this->fieldArray, true);
                while ( $in_array ) {
                    $elementName = $elementName . $suffix;
                    $in_array = in_array($elementName, $this->fieldArray, true);
                }
                $field = self::setFieldValue($field, "name", $elementName);
                array_push($this->fieldArray, $elementName);
                return $field;
            }, $csvArr);
        } else {
            foreach ( $csvArr as $field ) {
                $elementName = self::getFieldValue($field, "name");
                array_push($this->fieldArray, $elementName);
            }
        }

        // Create the Data Dictionary
        $result = array_map(function ($field) use ($form) {
            $elementName = self::getFieldValue($field, "name");
            $notes       = self::getFieldValue($field, "notes");
            $valueRange  = self::getFieldValue($field, "valueRange");
            $type        = self::getFieldValue($field, "type");
            $description = self::getFieldValue($field, "description");
            $required = self::getFieldValue($field, "required");

            $note            = preg_replace('/[\n\r]/', '', $notes);
            $pvr             = self::parseValueRange($valueRange);
            $parsed          = self::parseNote($note, $pvr);
            $parsedNote      = $parsed["matches"] ?? "";
            $fieldNote       = $parsed["fieldNote"] ?? "";
            $bounds          = self::parseBounds($valueRange, $parsedNote);
            $field_type      = self::getFieldType($parsedNote);
            $text_validation = self::getTextValidation($type, $field_type);

            return array(
                "field_name"                                 => $elementName,
                "form_name"                                  => $form,
                "section_header"                             => NULL,
                "field_type"                                 => $field_type,
                "field_label"                                => $description,
                "select_choices_or_calculations"             => $parsedNote,
                "field_note"                                 => $fieldNote,
                "text_validation_type_or_show_slider_number" => $text_validation,
                "text_validation_min"                        => $bounds[0],
                "text_validation_max"                        => $bounds[1],
                "identifier"                                 => NULL,
                "branching_logic"                            => NULL,
                "required_field"                             => $required === "Required" ? "y" : NULL,
                "custom_alignment"                           => NULL,
                "question_number"                            => NULL,
                "matrix_group_name"                          => NULL,
                "matrix_ranking"                             => NULL,
                "field_annotation"                           => $note
            );
        }, $csvArr);

        return $result;
    }



    private function convert_all_in_one()
    {
        $duplicateAction = $this->payload["duplicateAction"] ?? "";
        $renameSuffix    = $this->payload["renameSuffix"] ?? "";

        $finalResult = array();

        foreach ( $this->payload["fileArray"] as $fileData ) {
            $data = $fileData["data"];

            if ( !self::inputIsValid($data, $this->matchFields) ) {
                $message = 'Error: Input file is not valid: ' . \REDCap::escapeHtml($fileData["formName"]);
                return [ "error" => $message ];
            }

            $result      = $this->createDataDictionary($data, $fileData["formName"], $duplicateAction, $renameSuffix);
            $finalResult = array_merge($finalResult, $result);
        }

        $duplicates = self::array_duplicates($this->fieldArray);
        if ( count($duplicates) && empty($duplicateAction) ) {
            return [ "error" => '<strong>The following field names were duplicated in your'
                . ' file(s)</strong>:<br>' . implode('<br>', $duplicates), "code" => 501 ];
        }

        return $finalResult;
    }

    private static function read_csv(string $filename)
    {
        $file   = fopen($filename, 'r');
        $result = [];
        while ( ($data = fgetcsv($file)) !== FAlSE ) {
            array_push($result, $data);
        }
        return $result;
    }

    private static function get_fields(array $dd)
    {
        $fields = [];
        foreach ( $dd as $row ) {
            $val = $row["field_name"] ?? $row[0];
            array_push($fields, $val);
        }
        return array_unique($fields);
    }

    private static function get_forms(array $dd)
    {
        $forms = [];
        foreach ( $dd as $row ) {
            $val = $row["form_name"] ?? $row[1];
            array_push($forms, $val);
        }
        return array_unique($forms);
    }

    private static function checkNewDict(array $combined_dd, array $orig_fields, array $orig_forms, array $new_dd)
    {

        $result = [
            "orig_fields" => [],
            "orig_forms"  => [],
            "new_fields"  => [],
            "new_forms"   => []
        ];

        // fields and forms for instruments to be added
        $new_fields = self::get_fields($new_dd);
        $new_forms  = self::get_forms($new_dd);

        // fields and forms grabbed from the combined dd (this is what we're testing)
        $combined_fields = self::get_fields($combined_dd);
        $combined_forms  = self::get_forms($combined_dd);

        // Check that combined dd has all original forms and fields
        foreach ( $orig_fields as $orig_field ) {
            if ( preg_match("/_complete$/", $orig_field) ) continue;
            if ( in_array($orig_field, $combined_fields, true) ) {
                array_push($result["orig_fields"], $orig_field);
            } else {
                return FALSE;
            }
        }
        foreach ( $orig_forms as $orig_form => $orig_label ) {
            if ( in_array($orig_form, $combined_forms, true) ) {
                array_push($result["orig_forms"], $orig_form);
            } else {
                return FALSE;
            }
        }

        // Check that combined dd has all new forms and fields
        foreach ( $new_fields as $new_field ) {
            if ( preg_match("/_complete$/", $new_field) ) continue;
            if ( in_array($new_field, $combined_fields, true) ) {
                array_push($result["new_fields"], $new_field);
            } else {
                return FALSE;
            }
        }
        foreach ( $new_forms as $new_form ) {
            if ( in_array($new_form, $combined_forms, true) ) {
                array_push($result["new_forms"], $new_form);
            } else {
                return FALSE;
            }
        }

        return $result;

    }

    public function convert()
    {
        $dd_array       = \REDCap::getDataDictionary('array') ?? [];
        $current_fields = \REDCap::getFieldNames();
        $current_forms  = \REDCap::getInstrumentNames();

        $this->fieldArray = array_merge($this->fieldArray, $current_fields);

        $result = $this->convert_all_in_one();
        if ( $result['error'] ) {
            return [ "success" => FALSE, "error" => $result['error'], "code" => $result['code'] ];
        }

        $filename       = $this->module->framework->createTempFile();

        $combine_status = self::combine_dicts_to_file($result, $dd_array, $filename);
        if ( $combine_status === FALSE ) {
            return [ "success" => FALSE, "error" => "Failure to produce combined data dictionary." ];
        }

        // Prepare a confirmation if things look good
        $newDictArr = self::read_csv($filename);
        $check      = self::checkNewDict($newDictArr, $current_fields, $current_forms, $result);
        if ( !$check ) {
            return [ "success" => FALSE, "error" => "There was an error in the combined data dictionary." ];
        }

        return [ "result" => $check, "success" => TRUE, "dictionary" => $newDictArr ];
    }
}

?>