# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/).

## Unreleased

## 1.2.4 - 2019-06-05

### Fixed
- taxonomy_index table doesnt loose the record on tags-merge anymore

## 1.2.1 - 2018-01-19

### Fixed
- fixed the cancel button on merge confirm page

## 1.2.0 - 2018-01-19

### Added
- added usages table on the single delete page
- added description to the usages tables

### Changed
- removed autofill javascript

## 1.1.2 - 2018-01-16

### Changed
- multi delete route was changed
- name of the active term while merging now displays in the textfield

### Added
- references table on multi delete confirm page added

## 1.1.1 - 2018-01-15

### Changed
- few routes were changed
- routing file clean up

## 1.1.0 - 2018-01-15

### Added 
- overview of the references before merging

## 1.0.3 - 2018-01-15

### Changed
- All classes have uniform names
- IndexForm now extends AbstractForm
- IndexForm now uses service methods
- Few more methods were moved to service

## 1.0.2 - 2018-01-05

### Changed
- updated search field description
- updated page titles
- terms are displayed on first pageload

## 1.0.1 - 2018-01-04

### Changed
- removed unnecessary title
- removed blank lines

### Added
- added drupal package information

### Fixed
- fixed search issue with special characters

## 1.0.0 - 2018-01-04
### Added
- Basic Project setup
- Terms are searchable (singe and multiple)
- Delete multiple Terms at once
- Merge multiple Terms to one Term
- User permissions to view single Vocabularies
