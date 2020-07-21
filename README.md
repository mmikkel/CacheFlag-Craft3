# Cache Flag plugin for Craft CMS 3.x

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mmikkel/CacheFlag-Craft3/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mmikkel/CacheFlag-Craft3/?branch=master)

Cold template caches that can be flagged and automatically invalidated.

## Requirements and installations

**This plugin requires Craft CMS 3.5.0-RC1 or later.**  

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mmikkel/cache-flag

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Cache Flag, or install via the Craft CLI:  

        ./craft plugin/install cache-flag

**Note:** Craft installs using Craft CMS 3.4.x or below should install Cache Flag 1.0.4:  

        composer require mmikkel/cache-flag:1.0.4

## What is Cache Flag?

Cache Flag adds a new `{% cacheflag %}` Twig tag to Craft CMS, which works just like the native `{% cache %}` tag, except that by default _it will not save element queries for automatic cache breaking_.

Cache Flag enables you to add _flags_ (i.e. keywords) to your content (i.e. per Section, Category Group, Asset Volume, Element Type etc) and `{% cacheflag %}` tags. When an element is saved, deleted or moved, Cache Flag will automatically invalidate any template caches with flags related to that element.  

**New:** Since Cache Flag 1.1.0 (Craft 3.5.0-RC1 or later) it's possible to save element queries (i.e. _collect element tags_) for automatic cache invalidation (similar to how the native `{% cache %}` tag works) by adding the new [`with elements`](#collecting-element-tags-for-automatic-cache-invalidation) directive to the `{% cacheflag %}` tag.   

_Cache Flag was inspired by the (admittedly vastly superior) Expression Engine plugin [CE Cache](https://docs.causingeffect.com/expressionengine/ce-cache/index.html), which implements _tags_ in a similar manner._

### But why.

The native `{% cache %}` tag is great, and in most cases Craft's system for automatically clearing caches based on element queries works perfectly. However, on setups with a lot of elements and/or relations, the element queries the `{% cache %}` tag creates can really bog down your system.

Cache Flag offers an alternate way to automatically clear your caches, without any scaling or performance issues.  

### So it's just like [Cold Cache](https://github.com/pixelandtonic/ColdCache), then?

Pretty much, except for the flagging thing. In fact, if you don't add any flags to your `{% cacheflag %}` tag (i.e., omit the `flagged` parameter), Cache Flag works *just* like Cold Cache – the only way your caches will be cleared, is if it expires, or if a user manually invalidates or clears the cache from the Control Panel.

### I'm going to need an example use case.

Sure. Let's say you have a section called "Awesome Entries", and there's a cache that you want to invalidate if the content in that section changes (i.e. whenever entries are saved, deleted, changes status etc). First, you add the flag `awesome` to the "Awesome Entries" section in Cache Flag's CP section. Then, you add that same `awesome` flag to any relevant caches, using Cache Flag's `flagged` parameter:  

```twig
{% cacheflag flagged "awesome" %}
    {% set entries = craft.entries... %}
    ...
{% endcacheflag %}
```

Now, whenever an entry in the Awesome Stuff section is saved, deleted, moved etc., the above cache will be invalidated. Sweet!

But, suppose you also want to have the above cache invalidated whenever a _category_ in any particular category group is saved or deleted (for example, because the entries in the "Awesome Stuff" section has a category field targeting that group). You could just add the flag `awesome` to that category group in Cache Flag's CP section as well, or you could add another flag to it entirely, e.g. `radical`, and then use a _pipe delimiter_ to add multiple flags to the relevant caches:  

```twig
{% cacheflag flagged "awesome|radical" %}
    {% set entries = craft.entries... %}
    ...
{% endcacheflag %}
```

Now, the above cache would be invalidated both when the content in the "Awesome Entries" section is changed _and_ whenever there are any changes to the categories in your category group.  

## Dynamic flags

Since Cache Flag 1.1.0, it's also possible to dynamically flag caches using element IDs (or UIDs). If you wanted to ensure that a cache is invalidated whenever a particular entry is saved (or deleted), you can do this:

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

It's also possible to just use `element:{element.id}` or `element:{element.uid}`, which works for all element types, including custom ones.  

Of course, it's also possible to combine both standard and dynamic cache flags for a single cache:  

```twig
{% cacheflag flagged "news|employees|entry:#{entry.id}|category:#{category.id}" %}
    ...
{% endcacheflag %}
```

Beyond the `flagged` and `with elements` parameters, the `{% cacheflag %}` tag _supports all the same parameters_ as [the native `{% cache %}` tag[(https://docs.craftcms.com/v3/dev/tags/cache.html#app)].  

## Collecting element tags for automatic cache invalidation

Since Cache Flag 1.1.0 (Craft 3.5.0-RC1 or later), it's possible to collect element tags (in addition to your own flags) for automatic cache invalidation just like the native `{% cache %}` tag does.  

If you want Cache Flag to collect element tags for automatic cache invalidation, you can add the `with elements` directive like this:  

```twig
{% cacheflag flagged "awesome" with elements %}
    ...
{% endcacheflag %}
```

(It's also possible to omit the `flagged` parameter and only use `with elements`, but at that point the `{% cacheflag %}` tag would work identically to the native `{% cache %}` tag, and you should probably just use the latter.)  

## Using Cache Flag

Install the plugin, then visit Cache Flag's CP Section (there'll be a "Cache Flag" button in your Control Panel's main menu), and add flags to your content as needed. Then, add `{% cacheflag %}` tags around the template code you want to cache.  

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
