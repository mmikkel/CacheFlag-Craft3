# Cache Flag Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

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
