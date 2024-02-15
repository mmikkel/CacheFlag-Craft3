# Cache Flag Changelog

## 2.0.0
### Added 
- Adds Craft 5.0 compatibility 
### Changed  
- Cache Flag's CP section has been moved to a utility  
### Removed  
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteAllFlaggedCaches()`
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteFlaggedCachesByElement()`
- Removed `mmikkel\cacheflag\services\CacheFlagService::deleteFlaggedCachesByFlags()`
- Removed `mmikkel\cacheflag\controllers\DefaultController::actionDeleteFlaggedCachesByFlags()`
- Removed `mmikkel\cacheflag\controllers\DefaultController::actionDeleteAllFlaggedCaches()`
- Removed `mmikkel\cacheflag\events\AfterDeleteFlaggedTemplateCachesEvent`
- Removed `mmikkel\cacheflag\events\BeforeDeleteFlaggedTemplateCachesEvent`
- Removed `mmikkel\cacheflag\records\Flagged`
- Removed `mmikkel\cacheflag\variables\CpVariable`
