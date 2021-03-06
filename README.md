# moodle-block_slider

## Description:

This block creates a slideshow of images.

It should work with all bootstrap based themes.

## Installation
Download, extract, and upload the "slider" folder into moodle/blocks/

## Supported Moodle versions:
I have tested plugin on clean install of Moodle 2.6, 2.7, 2.8, 2.9, 3.6

## Version history
- 0.1.0
  - First release
- 0.1.1
  - fixed wrong risks in db/access
  - fixed PHP notice when trying to get not yet set config property
  - deleted unnecessary functions from code
  - used moodle_url::make_file_url() to get file list instead of SQL
  - removed font-awesome - using Moodle core theme icons to navigate forward/backward
  - added option to disable auto-play
  - tested and working on Moodle 2.9
- 0.1.2
  - added support for Moodle 3.0
  - now allowed multiple instances of block
- 0.1.3
  - plugin is supported by Moodle 3.1, 3.2, 3.3, 3.4, 3.5
  - now using AMD format Javascript Modules
- 0.1.4
  - added URL field for slides. If populated the slide will be wrapped in a link.
- 0.1.5
  - fix configuration for filemanager to populate images for other users.