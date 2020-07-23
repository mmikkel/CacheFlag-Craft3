# Cache Flag plugin for Craft CMS 3

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mmikkel/CacheFlag-Craft3/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mmikkel/CacheFlag-Craft3/?branch=master)

Cache Flag is a Craft CMS plugin that adds an alternative cache invalidation strategy to template caches, using manually defined keywords ("flags").  

## TL;DR (and a note re: Craft 3.5)   

Cache Flag was originally designed to circumvent performance issues in Craft 2 and 3 related to the element queries created by the native `{% cache %}` tag for automatic cache invalidation.  

Craft 3.5 (due to release in August 2020) has a new template caching system with [a tag-based cache invalidation strategy](https://github.com/craftcms/cms/issues/1507#issuecomment-633147835). This should solve said performance issues, and makes Cache Flag redundant for its primary use case.    

**If you're only using Cache Flag to avoid performance issues with the native `{% cache %}` tag, you probably don't need it after upgrading to Craft 3.5.0 or later :)**  

That said, Cache Flag is fully compatible with Craft 3.5 (it can even be combined w/ th native `{% cache %}` tag's automatic invalidation strategy) and is still a valid alternative to the native `{% cache %}` tag if you want to  

* Do automatic or manual bulk template cache invalidation  
* Cache arbitrary Twig output and implement your own invalidation strategies  
* Have completely cold template caches  

## Table of contents  

* [Requirements and installation](#requirements-and-installation)  
* [Using Cache Flag](#using-cache-flag)  
* [Dynamic flags](#dynamic-flags)  
* [Arbitrary flags](#arbitrary-flags)  
* [Collecting element tags for automatic cache invalidation](#collecting-element-tags-for-automatic-cache-invalidation)  
* [Cold caches](#cold-caches)  
* [Clearing flagged caches](#invalidating-flagged-caches)
* [Additional parameters](#additional-parameters)
* [Project Config and `allowAdminChanges`](#project-config)  
* [Upgrading from Craft 2](#upgrading-from-craft-2)  
* [Events](#events)  

## Requirements and installation

**This plugin requires Craft CMS 3.5.0-RC1 or later. Craft installs using Craft CMS 3.4.x or below should install Cache Flag v. 1.0.4:** `composer require mmikkel/cache-flag:1.0.4`  

To install the plugin, follow these instructions:

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mmikkel/cache-flag

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Cache Flag, or install via the Craft CLI:  

        ./craft plugin/install cache-flag

## Using Cache Flag  

Cache Flag adds a new `{% cacheflag %}` Twig tag to Craft CMS, which works just like the native `{% cache %}` tag - except that by default, Cache Flag's template caches are "cold" (i.e. _Cache Flag will not save element queries for automatic cache invalidation_).  

For cache invalidation, Cache Flag adds the ability to "flag" template caches and content with keywords ("flags"). Whenever an element is saved, moved or deleted, Cache Flag will automatically invalidate any flagged template caches matching that element's flags.  

_Here's how it looks in action:_  

```twig
{% cacheflag flagged "news|images" %}
    {% set entries = craft.entries.section('news').all() %}
    ...
{% endcacheflag %}
```

**Note that multiple flags are separated using the pipe delimiter (`|`).**  

**Tip:** In addition to the `flagged` parameter it's also possible to have Cache Flag clear caches automatically in the same way the native `{% cache %}` tag does, using the new [`with elements`](#collecting-element-tags-for-automatic-cache-invalidation) directive.  

### I'm going to need an example.  

Sure. Let's assume you have a section called "News", and there's a cache that you want to invalidate whenever the content in that section changes (i.e. if entries are saved, deleted, changes status etc). First, you add the flag `news` (or whatever, the flags can be anything, really) to the "News" section in Cache Flag's CP section: 

![The Cache Flag CP section](resources/cpsection.png)  

Then, you add that same `news` flag to any relevant caches, using the `{% cacheflag %}` tag and the `flagged` parameter:  

```twig
{% cacheflag flagged "news" %}
    {% set entries = craft.entries... %}
    ...
{% endcacheflag %}
```

Now, whenever an entry in the "News" section is saved, moved, deleted or changes status, any caches flagged with `news` will be automatically invalidated.      

## Dynamic flags

It's possible to flag caches using dynamic flags based on element IDs and/or UIDs. If you wanted to ensure that a cache is invalidated whenever a particular element is edited, moved or deleted, you can do this:  

```twig
{% cacheflag flagged "entry:#{entry.id}" %}
    ...
{% endcacheflag %}
```  

or if you prefer:  

```twig
{% cacheflag flagged "entry:#{entry.uid}" %}
    ...
{% endcacheflag %}
```  

All native element types can be used in dynamic flags:  

`entry:#{entry.id}`  
`asset:#{asset.id}`  
`category:#{category.id}`  
`tag:#{tag.id}`  
`globalSet:#{globalSet.id}`  
`user:#{user.id}`  

It's also possible to use the `element` prefix, which works for all element types (including custom/third party ones):  

`element:#{element.id}`  
`element:#{element.uid}`  

Of course, it's possible to combine both standard and dynamic cache flags for a single cache:  

```twig
{% cacheflag flagged "news|employees|entry:#{entry.id}|category:#{category.id}" %}
    ...
{% endcacheflag %}
```

## Arbitrary flags  

The flags you add to your `{% cachetags %}` caches can be literally anything - and they don't have to be added to an element source (or be dynamic).    

A good use case for _arbitrary flags_ is when you've got a cache that don't involve any elements, for example if you wanted to cache output dependent on an external API call or something else that is time-consuming to parse on every request, e.g. something like this:  

```twig
{% cacheflag flagged "somearbitraryflag" %}
    {% set data = craft.somePlugin.doExpensiveApiCall() %}
    ...
{% endcacheflag %}
```

If you use arbitrary flags, keep in mind that there's nothing that will actually invalidate those caches automatically (they'll essentially be _cold_ caches, albeit flagged). Read up on [the different options available for invalidating these - and other - flagged caches here](#invalidating-flagged-caches).  

## Collecting element tags for automatic cache invalidation

Since Cache Flag 1.1.0 (Craft 3.5.0-RC1 or later), it's possible to collect element tags (in addition to your own flags) for automatic cache invalidation just like the native `{% cache %}` tag does.  

If you want Cache Flag to collect element tags for automatic cache invalidation, you can add the `with elements` directive like this:  

```twig
{% cacheflag flagged "awesome" with elements %}
    ...
{% endcacheflag %}
```

Note: It's also possible to omit the `flagged` parameter and only use `with elements`, but at that point the `{% cacheflag %}` tag would work identically to the native `{% cache %}` tag, and you should probably just use the latter.  

## Cold caches  

If both `flagged` and `with elements` are omitted from a `{% cacheflag %}` tag, that cache will be completely "cold", and it will only be invalidated if/when it expires, or if a user manually invalidates it (or clears the entire data cache) via the Control Panel or the Craft CLI (see also _[invalidating flagged caches](#invalidating-flagged-caches)_):  

```twig
{% cacheflag for 360 days %}
    ...
{% endcacheflag %}
```

**Tip:** If you're upgrading a Craft 2 site that uses the [Cold Cache plugin](https://straightupcraft.com/craft-plugins/cold-cache), this is one way to achive the same thing on Craft 3.  

## Invalidating flagged caches

Caches flagged with flags saved to one or multiple element sources or [dynamic flags](#dynamic-flags), your caches will be automatically invalidated.  

Cold caches and caches using [arbitrary flags](#arbitrary-flags) must be invalidated manually or programmatically.  

### Manual cache invalidation

Flagged caches can be manually invalidated by  

* Checking the "Flagged template caches" checkbox in the native Clear Caches utility in the Craft CP. This will invalidate _all_ flagged caches.  
* Via Cache Flag's CP section
* Hitting the `cache-flag/caches/invalidate` web controller with a GET or POST request. This controller invalidates _all_ flagged caches, unless a parameter `flags` (array of flags you want to clear) is present in the request.  
* Running the CLI console command `cache-flag/caches/invalidate`. This command will invalidate _all_ flagged caches, unless you specify one or multiple flags (comma separated list):  

        ./craft cache-flag/caches/invalidate news,images

**Additionally, flushing Craft's data cache (via CLI or the Clear Caches CP utility) will _delete_ all flagged template caches.**  

### Programmatic cache invalidation  

```php

use mmikkel\cacheflag\CacheFlag;

// Invalidate all flagged caches
CacheFlag::getInstance()->cacheFlag->invalidateAllFlaggedCaches();

// Invalidate caches for a particular element
CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByElement($entry);

// Invalidate caches for one or several flags
CacheFlag::getInstance()->cacheFlag->invalidateFlaggedCachesByFlags(['news', 'images']);

```

## Additional parameters

Beyond the `flagged` and `with elements` parameters, the `{% cacheflag %}` tag _supports all the same parameters_ as [the native `{% cache %}` tag[(https://docs.craftcms.com/v3/dev/tags/cache.html#app)].  

## Project Config and `allowAdminChanges`

Cache Flag supports [Project Config](https://docs.craftcms.com/v3/project-config.html) since v. 1.2.0 (Craft 3.5.0 or later only). **If you're upgrading from an earlier version of Cache Flag, the relevant `.yaml` files will be automatically created after upgrading and running migrations.**  

Note that Cache Flag's CP section is inaccessible in environments where the  [`allowAdminChanges`](https://docs.craftcms.com/v3/config/config-settings.html#allowadminchanges) config setting is set to `false`.  

## Upgrading from Craft 2

**Since v. 1.2.0, Cache Flag will attempt to automatically migrate flags from the old `templatecaches_flagged` database table after installation (or, after upgrading from an earlier version of Cache Flag for Craft 3).** 

The migration only runs if    

1. The old Craft 2 database table `templatecaches_flagged` is still in the database  
2. There are no flags currently in the Craft 3 database table (`cacheflag_flags`)

After the migration has completed, make sure that all flags have carried over. Any missing flags will have to be manually entered in Cache Flag's CP section (this can happen if there isn't parity between element source IDs in the Craft 2/3 database tables).    

## Events

Cache Flag dispatches two events:

* `beforeInvalidateFlaggedCaches`  
_Dispatched just before Cache Flag invalidates one or several flagged template caches._  

* `afterInvalidateFlaggedCaches`  
_Dispatched immediately after Cache Flag has invalidated one or several flagged template caches._  

Both events include a parameter `flags`, which is an array of the flags Cache Flag is invalidating caches for.    

### Listening to Cache Flag events

```php
use mmikkel\cacheflag\events\FlaggedTemplateCachesEvent;
use mmikkel\cacheflag\services\CacheFlagService;
use yii\base\Event;

Event::on(
    CacheFlagService::class,
    CacheFlagService::EVENT_BEFORE_INVALIDATE_FLAGGED_CACHES,
    function (FlaggedTemplateCachesEvent $event) {
        $flags = $event->flags;
        ...
    }
);

Event::on(
    CacheFlagService::class,
    CacheFlagService::EVENT_AFTER_INVALIDATE_FLAGGED_CACHES,
    function (FlaggedTemplateCachesEvent $event) {
        $flags = $event->flags;
        ...
    }
);
```

Note: Before Cache Flag 1.1.0, the `EVENT_AFTER_DELETE_FLAGGED_CACHES` (now deprecated in favor of `EVENT_AFTER_INVALIDATE_FLAGGED_CACHES`) would only be dispatched if caches were actually deleted. In Cache Flag 1.1.0+, the `EVENT_AFTER_INVALIDATE_FLAGGED_CACHES` event is dispatched regardless of whether any caches were actually cleared.  
