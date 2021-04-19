
const csv = require('neat-csv');
const {createObjectCsvStringifier, createObjectCsvWriter} = require('csv-writer'); // https://www.npmjs.com/package/csv-writer
const fs = require('fs');
const JSZip = require('jszip');


/////////////////////////////////
/////  PARSING VALUE RANGE  /////
/////////////////////////////////

// start and end are integers; start <= end; otherwise returns empty array
function range(start, end) {
	if (!Number.isInteger(start) || !Number.isInteger(end) || !(end>=start)) return [];
	return Array(end - start + 1).fill().map((_, idx) => start + idx);
}

function getRanges(arr) {
	return arr.map(e1 => {
		let bounds = e1.split('::')
			.map(e2 => e2.replace('NDAR*', '').trim());
		if (bounds.length == 2 && +bounds[0] !== NaN && +bounds[1] !== NaN) {
			return range(Math.min(+bounds[0],+bounds[1]), Math.max(+bounds[0],+bounds[1]));
		} else {
			return bounds;
		}
	})
}

function reduceRanges(arr) {
	return arr.reduce((res, e3) => res.concat(e3),[])
		.map(e4 => e4.toString());
}

// Takes single item's value range string
// returns array of strings representing possible values
function parseValueRange(vr) {
	let arr = vr.split(';');
	let ranges = getRanges(arr);
	let reducedRanges = reduceRanges(ranges);
	return Array.from(new Set(reducedRanges));
}

///////////////////////////
/////  PARSING NOTES  /////
///////////////////////////

//// note         - single item's notes value string
//// parsedVr - array, result of parseValueRange for single item
//// returns      -
//// NOTE - THIS IS OLDER IMPLEMENTATION - USE parseNote3
// function parseNote(note, parsedVr) {
//     parsedVr = Array.from(parsedVr);
//     const re = /-*\w+(?=\s*=\s*).+?(?:(?!-*\w+=).)*/g;
//     let matches = note.match(re);
//     if (matches === null) return null;
//     return matches
//             .map(el => el.split(/;|,/g))
//             .reduce((res, x) => res.concat(x), [])
//             .filter(el => el.match(/=/))
//             .map(el => {
//                 let res;
//                 el = el.split(/=/)
//                     .map(el2 => el2.trim())
//                 if (parsedVr.includes(el[1])) el.reverse();
//                 if (parsedVr.includes(el[0])) {
//                     res =  el.join(', ');
//                     parsedVr.splice(parsedVr.indexOf(el[0]),1);
//                 }
//                 return res;
//             })
//             .filter(el => el !== undefined)
//             .join(' | ')
// }

//// NOTE - THIS IS OLDER IMPLEMENTATION - USE parseNote3
// function parseNote2(note, parsedVr = []) {
//     parsedVrString = parsedVr.map(el => el.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
//     if (parsedVrString === "") return "";
//     const re = new RegExp(`(?<key>${parsedVrString})\\s*=(?<val>(?:(?!(${parsedVrString})\\s*=).)*)`,'g');
//     re2 = new RegExp(`(?<val>(?:(?!\\s*=\\s*(${parsedVrString})).)*)\\s*=\\s*(?<key>${parsedVrString})`,'g');
//     let matches = [...note.matchAll(re)];
//     let matchKeys = matches.map(match=>match.groups?.key);
//     return parsedVr.map(val => {
//         if (matchKeys.includes(val)) return matches.filter(match => match.groups?.key === val)[0];
//         return {
//             groups: {
//                 key: val,
//                 val: val
//             }
//         };
//     }).map(el => `${el.groups.key}, ${el.groups.val.trim().replace(/\s*[.,;]+$/, '')}`)
//     .join(' | ');
// }

/////////////////////////////
/////  MATCH FUNCTIONS  /////
/////////////////////////////

// Each match function should produce an array of matches (or empty array)
// Each match should be an object with:
//      key     - string matching a parsedVr element
//      value   - string with the value for that key

function clean(val) {
    return val.replace(new RegExp(`^[,;'"\\s]*|[,;'"\\s]*$`,'g'), '');
}

// Matches based on key search 
// only matches form of key = value and not value = key
// delimiter agnostic
function match1(note, parsedVrString) {
    let re = new RegExp(`(?<key>${parsedVrString})\\s*=(?<val>(?:(?!(${parsedVrString})\\s*=).)*)`,'g');
    let matches = [...note.matchAll(re)];
    return matches.map(match => {
        return {
            key: clean(match.groups.key), 
            value: clean(match.groups.val)
        }
    });
}

// Matches based on key search
// only matches form of value = key and not key = value
// delimiter agnostic
function match2(note, parsedVrString) {
    let re = new RegExp(`(?<val>(?:(?!\\s*=\\s*(${parsedVrString})).)*)\\s*=\\s*(?<key>${parsedVrString})`,'g');
    let matches = [...note.matchAll(re)];
    return matches.map(match => {
        return {
            key: clean(match.groups.key),
            value: clean(match.groups.val)
        }
    });
}

// Splits based on a delimiter
// then keeps only splits that contain "=" 
// and contain a key from parsedVr
function match3(note, parsedVr, delimiter) {
    parsedVr = Array.from(parsedVr);
    return note.split(new RegExp(`[${delimiter}]`, 'g'))
        .reduce((res, x) => res.concat(x), [])
        .filter(el => el.match(/=/))
        .map(el => {
            el = el.split(/=/)
                .map(el2 => el2.trim())
            if (parsedVr.includes(el[1])) el.reverse();
            if (parsedVr.includes(el[0])) {
                return {
                    key: clean(el[0]),
                    value: clean(el[1])
                }
            }
            return;
        })
        .filter(el => el !== undefined);
}

function parseNote3(note, parsedVr = []) {
    parsedVr = Array.from(parsedVr).map(el => {
    	if (el === "") return "";
        let result = el.trim().replace(/[^0-9A-Za-z._\-]/g, '');
        if (result === "") result = `custom${el.trim().charCodeAt(0)}`;
        return result;
    });
    parsedVrString = parsedVr.map(el => el.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    if (parsedVrString === "") return "";
    const matches1    = match1(note, parsedVrString);
    const matches2    = match2(note, parsedVrString);
    const matches_sc  = match3(note, parsedVr, ';');
    const matches_c   = match3(note, parsedVr, ',');
    const matches_scc = match3(note, parsedVr, ';,');
    const allMatches  = [matches1, matches2, matches_c, matches_sc, matches_scc];
    const eqs         = note.match(/=/g)?.length || 0;
    const pvrl        = parsedVr.length;

    let matches;
    if (eqs === pvrl) {
        matches = allMatches.filter(matches => matches.length === eqs)[0];
    } else {
        matches = allMatches.sort((a,b) => a.length > b.length)[0];
        if (matches.length) {

            // If we have matches with duplicate keys, merge them
            matches = matches.reduce((acc,el) => {
                let duplicate = false;
                acc.forEach((acc_el, i) => {
                    if (acc_el.key === el.key) {
                        acc[i].value += `; ${el.value}`;
                        duplicate = true;
                    } 
                });
                if (!duplicate) acc.push(el);
                return acc;
            },[])

            // If we have more values without matches, create them
            matches.forEach(match => parsedVr.splice(parsedVr.indexOf(match.key), 1)); 
            parsedVr.forEach(val => {
                let key = val.replace(/[^0-9A-Za-z._\-]/g, '');
                if (key === "") return;
                matches.push({
                    key: key,
                    value: val
                });
            });
        } else {
            parsedVr.forEach((val, i) => {
                let key = String(!isNaN(+val) ? +val : i + 1);
                matches.push({
                    key: key,
                    value: val
                });
            });
        }
    }
    let finalMatches = "";
    let fieldNote = note;
    if (matches) {
	    finalMatches = matches
	        .map(match => {return `${match.key}, ${match.value}`})
	        .join(' | '); 
	    fieldNote = matches
	        .map(match => {return `${match.key} = ${match.value}`})
	        .join('; ');
	} 
    return {
        matches: finalMatches,
        fieldNote: fieldNote
    }
}



////////////////////////////
/////  PARSING BOUNDS  /////
////////////////////////////

// vr 			- single item's ValueRange
// parsedNote 	- single item's parsed Notes
// returns		- array of [min,max] or [null,null]
function parseBounds(valueRange, parsedNote) {
	let bounds = valueRange.split('::')
		.map(e => e.replace('NDAR*', '').trim());
	if (parsedNote === null && bounds.length == 2 && !isNaN(+bounds[0]) && !isNaN(+bounds[1])) {
		return [Math.min(+bounds[0],+bounds[1]), Math.max(+bounds[0],+bounds[1])];
	} else {
		return [null,null];
	}
}

/////////////////////////
/////  FIELD TYPES  /////
/////////////////////////

// parsedNote 	- single item's parsed Notes
// returns 		- string, one of "dropdown" or "text"
function getFieldType(parsedNote) {
	return !parsedNote ? "text" : "dropdown";
} 

/////////////////////////////
/////  TEXT VALIDATION  /////
/////////////////////////////

// dataType 	- single item's DataType
// fieldType 	- single item's fieldType
// returns		- string text validation or null
function getTextValidation(dataType, fieldType) {
	if (fieldType !== "text") return null; 
	switch(dataType) {
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
			return null;
	}
}

///////////////////////////////
/////  CREATE SINGLE CSV  /////
///////////////////////////////

// DOES NOT CREATE HEADER ROW
// so that combining multiple files is easier
// csvDat 		- array from neat-csv
// form			- string, name of the form
// fieldArray	- array of field names to check for duplicates
// duplicateAction	- string, what to do with duplicates (one of "ignore", "remove", or undefined)
//						ignore: keep duplicates in the data
//						remove: remove any field that already exists in the fieldArray
// returns		- array of objects
function createDataDictionary(csvDat, form, fieldArray = [], duplicateAction) {
	let internalFields = [];
	return csvDat
		.filter(field => {
			let result = duplicateAction !== "remove" || (!fieldArray.includes(field.ElementName) && !internalFields.includes(field.ElementName));
			result && internalFields.push(field.ElementName);
			return result;
		})
		.map(field => {
            let note                = field.Notes.replace(/[\n\r]/g,'');
            let pvr                 = parseValueRange(field.ValueRange);
            let parsed              = parseNote3(note, pvr);
            let parsedNote          = parsed.matches;
            let fieldNote           = parsed.fieldNote;
            let bounds              = parseBounds(field.ValueRange, parsedNote);
            let field_type          = getFieldType(parsedNote);
            let text_validation     = getTextValidation(field.DataType, field_type);

            let result = {
                variable:       field.ElementName,
                form:           form,
                header:         null,
                type:           field_type,
                label:          field.ElementDescription,
                choices:        parsedNote,
                note:           fieldNote,
                validation:     text_validation,
                min:            bounds[0],
                max:            bounds[1],
                id:             null,
                branching:      null,
                required:       field.Required === "Required" ? "y" : null,
                alignment:      null,
                question_num:   null,
                matrix_name:    null,
                matrix_rank:    null,
                annotation:     note
            };
            return result;
        });
}

/////////////////////////////////
/////  VERIFY INPUT FORMAT  /////
/////////////////////////////////

// csvDat 		- parsed csv array from neat-csv
// fields		- array of field names
// returns		- boolean
function inputValidated(csvDat, fields) {
	return fields.every(field => Object.keys(csvDat[0]).includes(field));
}





const header = [
	{id: 'variable', 		title: 'Variable / Field Name'},
	{id: 'form', 			title: 'Form Name'},
	{id: 'header', 			title: 'Section Header'},
	{id: 'type', 			title: 'Field Type'},
	{id: 'label', 			title: 'Field Label'},
	{id: 'choices', 		title: 'Choices, Calculations, OR Slider Labels'},
	{id: 'note', 			title: 'Field Note'},
	{id: 'validation', 		title: 'Text Validation Type OR Show Slider Number'},
	{id: 'min', 			title: 'Text Validation Min'},
	{id: 'max', 			title: 'Text Validation Max'},
	{id: 'id', 				title: 'Identifier?'},
	{id: 'branching', 		title: 'Branching Logic (Show field only if...)'},
	{id: 'required', 		title: 'Required Field?'},
	{id: 'alignment', 		title: 'Custom Alignment'},
	{id: 'question_num', 	title: 'Question Number (surveys only)'},
	{id: 'matrix_name', 	title: 'Matrix Group Name'},
	{id: 'matrix_rank', 	title: 'Matrix Ranking?'},
	{id: 'annotation', 		title: 'Field Annotation'},
];

const matchFields = [
	"ElementName", 
	"DataType", 
	"Required", 
	"ElementDescription", 
	"ValueRange", 
	"Notes"
];

async function asyncForEach(array, callback) {
  for (let index = 0; index < array.length; index++) {
    await callback(array[index], index, array);
  }
}

function findDuplicates(arr) { return arr.filter((item, index) => arr.indexOf(item) != index) };
function findUnique(arr) { return arr.filter((item,index) => arr.indexOf(item) == index) };


exports.converter = {
	convert: async function(fileObject) {
		//fileObject = JSON.parse(fileObject);
		const fileArray = fileObject.fileArray;
		const allInOne = fileObject.allInOne;
		const instrumentZip = fileObject.instrumentZip;
		const duplicateAction = fileObject.duplicateAction;
		const csvStringifier = createObjectCsvStringifier({header: header});

		
		if (allInOne) {

			let csvString = "";	
			let fieldArray = [];
			csvString += csvStringifier.getHeaderString();
			for (const fileData of fileArray) {
			//await asyncForEach(fileArray, async (fileData) => {
				let csvDat = await csv(fileData.data);
				if (!inputValidated(csvDat, matchFields)) throw(`Error: Input file is not valid: ${fileData.formName}`);
				let result = createDataDictionary(csvDat, fileData.formName, fieldArray, duplicateAction);
				fieldArray = fieldArray.concat(csvDat.map(el => el.ElementName));
				csvString += csvStringifier.stringifyRecords(result);
			}
			//});
			let duplicates = findDuplicates(fieldArray);
			if (duplicates.length && !duplicateAction) {
				let error = new Error("<strong>The following field names were duplicated in your file(s)</strong>:<br>"+findUnique(duplicates).join('<br>'));
				error.duplicates = true;
				throw(error);
			}
			if (instrumentZip) {
				let fileZip = new JSZip();
				fileZip.file('instrument.csv', csvString);
				let filename = `RC_instrumentzip_${fileArray[0].formName}.zip`;
				let content = await fileZip.generateAsync({type:"base64", compression: "DEFLATE"});
				return JSON.stringify([{file:filename, type:"application/zip", data:content}]);
			} else {
				let filename = fileArray.length > 1 ? "RC_datadictionary.csv" : `RC_datadictionary_${fileArray[0].formName}.csv`;
				return JSON.stringify([{file:filename, type:"text/csv", data:csvString}]);
			}

		} else {

			let results = new JSZip();
			await asyncForEach(fileArray, async (fileData) => {
				let csvString = csvStringifier.getHeaderString();
				let csvDat = await csv(fileData.data);
				let fieldArray = csvDat.map(el => el.ElementName);
				let duplicates = findDuplicates(fieldArray);
				if (duplicates.length && !duplicateAction) {
					let error = new Error("<strong>The following field names were duplicated in your file(s)</strong>:<br>"+findUnique(duplicates).join('<br>'));
					error.duplicates = true;
					throw(error);
				}
				if (!inputValidated(csvDat, matchFields)) throw(`Error: Input file is not valid: ${fileData.formName}`);
				let result = createDataDictionary(csvDat, fileData.formName);
				csvString += csvStringifier.stringifyRecords(result);
				if (instrumentZip) {
					let fileZip = new JSZip();
					fileZip.file('instrument.csv', csvString);
					results.file(`RC_instrumentzip_${fileData.formName}.zip`, await fileZip.generateAsync({type:"nodebuffer", compression: "DEFLATE"}));
				} else {
					results.file(`RC_datadictionary_${fileData.formName}.csv`, csvString);
				}
			});
			let filename = instrumentZip ? "RC_instrumentzips.zip" : "RC_datadictionaries.zip";
			let content = await results.generateAsync({type:"base64", compression: "DEFLATE"});
			return JSON.stringify([{file:filename, type:"application/zip", data:content}]);
		}
	},
};
