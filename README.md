# NDA Instrument Importer

This REDCap External Module (EM) allows users to search the NIMH Data Archive ([NDA](https://nda.nih.gov/data_dictionary.html)) and add selected instruments to their REDCap project. The EM handles converting the data dictionary from the NDA format into REDCap's data dictionary format. 

However, because NDA's format is not standardized in how multiple choice labels are written, occasional errors may appear in the translation. Please open an issue in this repo if you find any such errors, and include the name of the instrument, the expected result, and the actual result.

## Installation

This EM may be downloaded from this repo (choose the latest release zip) and unzipped in the `modules` directory of your REDCap's web server. Alernatively, it may be installed directly from the REDCap Repo.

## Usage and Configuration

The EM should be enabled in the project into which instruments should be imported. This will add a link in the left-side menu. All functionality of the EM is fairly straightforward and is documented within the EM's interface. There is no system- or project-level configuration.

## Roadmap

* Detect whether a selected instrument's name already exists in the project and give a warning and/or options on how to proceed (see [issue #2](https://github.com/AndrewPoppe/nda_instrument_importer/issues/2#issue-873983345))
* If an instrument that was added to a project has been modified in NDA, indicate this to the REDCap user somehow.
* Have the EM cache NDA data using a cron job periodically throughout the day, as opposed to current client-based implementation. 