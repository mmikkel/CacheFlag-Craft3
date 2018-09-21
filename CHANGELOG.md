# Cache Flag Changelog

## 1.0.2 - 2018-09-21
### Fixed
- Fixes breaking SQL issues on installs running PostgreSQL (#1)
- Fixes a JavaScript issue in CacheFlag's CP section, where flag input fields could be cleared when saving flags
- Fixes a bug where CacheFlag would fail to break caches for sources with multiple flags
### Improved
- Adds additional, super fun random messages when flags are saved in the CP section. Those are entertaining, right?

## 1.0.1 - 2018-07-19
### Fixed
- Fixes various minor issues

## 1.0.0 - 2018-07-16
### Added
- Initial release for the Craft 3 port of Cache Flag
### Improved
- Caches created inside the `{% cacheflag %}` tag pair are now being stored in a single transaction, which should resolve a rare issue with orphaned template caches
- Changing the `flagged` attribute for an existing `{% cacheflag %}` tag will now result in a new cache (just like changing the `key` will for both`{% cache %}` and `{% cacheflag %}`)
- The "deleteFlaggedCaches" event has been to renamed "afterDeleteFlaggedCaches", for clarity
