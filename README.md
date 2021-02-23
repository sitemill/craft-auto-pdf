# Auto PDF plugin for Craft CMS

Seamlessly create PDF thumbnails using Craft's built in image transformer.


## Requirements

This plugin requires Craft CMS 3.6 or later, Imagick, and Ghostscript. 

This plugin makes use of [spatie/pdf-to-image](https://github.com/spatie/pdf-to-image), if you have issues with Ghostscript see [common issues](https://github.com/spatie/pdf-to-image#issues-regarding-ghostscript).

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require sitemill/auto-pdf

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Auto PDF.

## AutoPDF Overview

AutoPDF will allow you to perform standard Craft transforms on any PDF file, as well as generating PDF thumbnails in the control panel.

Flattening a PDF can be pretty stessful for your server, so to help it out AutoPDF creates and stores a hi-res flattened version of your uploaded PDF and creates subsequent transforms from this.  

## Configuring Auto PDF

Auto PDF requires you to setup a seperate volume to store the hi-res image files. It's recommended that you hide this volume from your users to avoid confusion.

## Auto PDF Roadmap

Some things to do, and ideas for potential features:

* Delete counterpart on PDF deletion
* Option to output PNG's

Brought to you by [Sitemill](sitemill.co)
