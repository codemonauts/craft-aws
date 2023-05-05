# Craft AWS Plugin Changelog

## 2.1.4 - 2023-05-05

### Fixed

- Some javascript are identified as java when determining the mime type. So we don't trust the answer and check the extension then. 

## 2.1.3 - 2022-11-18

### Fixed

- Fixed version number.

## 2.1.2 - 2022-11-18

### Fixed

- Fixed boolean environment suggestions in settings.

## 2.1.1 - 2022-10-17

### Added

- `\codemonauts\aws\services\S3::setCredentials() ` to set credentials for the next S3 operation.

## 2.1.0 - 2022-09-26

### Added

- `\codemonauts\aws\services\Cloudfront::invalidate() ` to create invalidation requests for Cloudfront.

## 2.0.1 - 2022-07-04

### Fixed

- Fixed boolean environment suggestions in settings.

## 2.0.0 - 2022-06-15

### Added

- Craft CMS 4 compatibility.

### Changed

- Requires Craft CMS >= 4.0
- Uses the General Config `buildId` for versioning the asset paths (if set).

### Removed

- Storing thumbnails on bucket: With Craft 4 the thumbnails for the CP are generated and stored the same way as every asset transformation. So no need for a special function anymore.

## 1.0.0-beta.2 - 2020-03-01

### Added

- Console command to generate thumbs without job/queues
- Config option for queue name for all mass updates (e.g. thumbs generating)

### Fixed

- If resourceRevision was a clousure, an error occured
- Fix AssetManager by adding S3 functions as trait
- Fix cached path of thumbnails
- Fix SVG mime type for older Craft CMS versions

## 1.0.0-beta.1 - 2019-04-06

### Added

- Initial release
