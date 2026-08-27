# Cache Flag Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.0.4 - 2026-08-27
### Fixed
- Fixed an issue where re-ordering structure entries did not break Cache Flag's caches, on Craft 5.9.0+. [#29](https://github.com/mmikkel/CacheFlag-Craft3/issues/29)

## 2.0.3 - 2026-05-05
### Fixed
- Fixed a PDO exception that could occur when installing Craft with pre-existing cache flags in the project config YAML files. [#28](https://github.com/mmikkel/CacheFlag-Craft3/issues/28)

## 2.0.2 - 2025-06-16
### Fixed
- Fixed a bug where CacheFlag would cache content for preview and tokenised requests

## 2.0.1 - 2025-02-27
### Fixed
- Fixed a PDO exception that could occur when applying project config changes after uninstalling Cache Flag

## 2.0.0 - 2024-03-28

### Added
- Added Craft 5.0 compatibility

### Changed
- Cache Flag's CP section has been moved to a utility

### Removed
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteAllFlaggedCaches()`
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteFlaggedCachesByElement()`
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteFlaggedCachesByFlags()`
- Removed `mmikkel\cacheflag\services\CacheFlagService::flagsHasCaches()`
- Removed `mmikkel\cacheflag\controllers\DefaultController::actionDeleteFlaggedCachesByFlags()`
- Removed `mmikkel\cacheflag\controllers\DefaultController::actionDeleteAllFlaggedCaches()`
- Removed `mmikkel\cacheflag\events\AfterDeleteFlaggedTemplateCachesEvent`
- Removed `mmikkel\cacheflag\events\BeforeDeleteFlaggedTemplateCachesEvent`
- Removed `mmikkel\cacheflag\records\Flagged`
- Removed `mmikkel\cacheflag\variables\CpVariable`
