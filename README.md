# ![Islandora](https://cloud.githubusercontent.com/assets/2371345/25624809/f95b0972-2f30-11e7-8992-a8f135402cdc.png) Islandora
[![Build Status][1]](https://travis-ci.com/Islandora/islandora)
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

This is the core module of Islandora's digital repository ecosystem. The `islandora` module provides:
- Fedora 5 integration via the [flysystem](https://drupal.org/project/flysystem) module
- Integration with the [context](https://drupal.org/project/context) module to control your digital repository's behaviour
- Publishing messages to a queue so they can be processed in the background

`islandora` contains several submodules and features:
- `islandora_core_feature` (**required**)
  - Configuration required by the `islandora` module
- `islandora_image`
  - Integrates with a Houdini (Imagemagick) server for image processing
- `islandora_audio` and `islandora_video`
  - Integrate with a Homarus (`ffmpeg`) server for audio/video processing 
- `islandora_text_extraction` and `islandora_text_extraction_defaults`
  - Integrate with a Hypercube (`tessseract` and `pdftotext`) server for text extraction
- `islandora_breadcrumbs`
  - Provides breadcrumbs following collection structure
- `islandora_iiif`
  - Provides IIIF manifests for repository content 

## Requirements / Installation

For our full digital repository solution, see our [installation documentation](https://islandora.github.io/documentation/installation/).

To download/enable just this module from the command line:

```bash
$ composer require islandora/islandora
$ drush en islandora_core_feature
$ drush mim islandora_tags
```

## Configuration

You can set the following configuration at `admin/config/islandora/core`:
- Broker URL
  - The URL to your message broker (i.e. Activemq)
- JWT Expiry
  - Set to increase the amount of time that authorization tokens remain valid.  If you have a long running derivative processes or a migration, you may need to set this.  Otherwise, it's best to leave it alone.
- Gemini URL
  - The URL to your Gemini server, which keeps track of where Islandora content is in Fedora.
- Fedora URL Display
  - Selected bundles can display the Fedora URL for repository content.

## Maintainers

Current maintainers:

* [Danny Lamb](https://github.com/dannylamb)

## Development

If you would like to contribute, please get involved by attending our weekly 
[Tech Call][4]. We love to hear from you!

If you would like to contribute code to the project, you need to be covered by 
an Islandora Foundation [Contributor License Agreement][5] or 
[Corporate Contributor License Agreement][6]. Please see the 
[Contributors][7] pages on Islandora.ca for more information.

## License

[GPLv2](http://www.gnu.org/licenses/gpl-2.0.txt)
[1]: https://travis-ci.org/Islandora/islandora.png?branch=8.x-1.x
[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-GPLv2-blue.svg?style=flat-square
[4]: https://github.com/Islandora/documentation/wiki
[5]: http://islandora.ca/sites/default/files/islandora_cla.pdf
[6]: http://islandora.ca/sites/default/files/islandora_ccla.pdf
[7]: http://islandora.ca/resources/contributors

