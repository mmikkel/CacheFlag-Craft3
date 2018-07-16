# Cache Flag plugin for Craft CMS 3.x

Cold template caches that can be flagged and automatically cleared.

## Requirements

This plugin requires Craft CMS 3.0.0-RC1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require mmikkel/cache-flag

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Cache Flag.

## What is Cache Flag?

Cache Flag adds a new `{% cacheflag %}` Twig tag to Craft CMS, which works just like the native `{% cache %}` tag, except that _it will not save element queries for automatic cache breaking_.

Instead of element queries, Cache Flag enables you to add _flags_ (i.e. keywords) to your content (i.e. per Section, Category Group, Asset Volume, Element Type etc) and `{% cacheflag %}` tags. When an element is saved, deleted or moved, Cache Flag will automatically clear any caches with flags related to that element.

_Cache Flag was inspired by the (admittedly vastly superior) Expression Engine plugin [CE Cache](https://docs.causingeffect.com/expressionengine/ce-cache/index.html), which implements _tags_ in a similar manner._

## But why.

The native `{% cache %}` tag is great, and in most cases Craft's system for automatically clearing caches based on element queries works perfectly. However, on setups with a lot of elements and/or relations, the element queries the `{% cache %}` tag creates can really bog down your system.

Cache Flag offers an alternate way to automatically clear your caches, without any scaling or performance issues.

## So it's just like Cold Cache, then?

Pretty much, except for the flagging thing. In fact, if you don't add any flags to your `{% cacheflag %}` tag (i.e., omit the `flagged` parameter), Cache Flag works *just* like Cold Cache – the only way your caches will be cleared, is if it expires, or if a user automatically clears the cache from the Control Panel.

## I'm going to need an example use case.

Ok, sure. Let's say you have a section called "Awesome Entries", and there's a cache that you want to clear every time the content in that section changes (i.e. an Entry is saved, deleted, changes status etc). First, you add the flag `awesome` to the "Awesome Entries" section in Cache Flag's CP Section. Then, you flag the cache(s) you want to clear with awesome in your template, using Cache Flag's flagged parameter:

```twig
{% cacheflag flagged "awesome" %}
    {% set entries = craft.entries... %}
    ...
{% endcacheflag %}
```

Now, whenever an entry in the Awesome Stuff section is saved or deleted, the above cache will be cleared. Sweet!

Suppose you also want to have the above cache cleared whenever a Category in a particular Category Group is published or deleted (e.g. because entries in the "Awesome Stuff" section has a Category Field targeting that group). You could just add the flag `awesome` to the relevant category group as well, or you could add another flag to it entirely, e.g. `radical`. You can use a pipe delimiter to specify multiple flags in your template:

```twig
{% cacheflag flagged "awesome|radical" %}
    {% set entries = craft.entries... %}
    ...
{% endcacheflag %}
```

Beyond the `flagged` parameter, the `{% cacheflag %}` tag _supports all the same parameters_ as the native `{% cache %}` tag – so I'll just refer to [the official documentation for the latter](https://docs.craftcms.com/v3/dev/tags/cache.html#app).

## Using Cache Flag

Install the plugin, then visit Cache Flag's CP Section (there'll be a "Cache Flag" button in your Control Panel's main menu), and add flags to your content as needed. Then, add `{% cacheflag %}` tags around the template code you want to cache.

## Events

Cache Flag dispatches two events:

* `beforeDeleteFlaggedCaches`  

Dispatched just before Cache Flag deletes one or several flagged template caches.  

* `afterDeleteFlaggedCaches`  

Dispatched immediately after Cache Flag has deleted one or several template caches by flag.  

Both events have two parameters:  

* `flags` (array of flags having caches deleted)
* `ids` (the IDs of all the actual templatecaches being deleted)   

### Listening to Cache Flag events

```php
use mmikkel\cacheflag\events\AfterDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\events\BeforeDeleteFlaggedTemplateCachesEvent;
use mmikkel\cacheflag\services\CacheFlagService;
use yii\base\Event;

Event::on(
    CacheFlagService::class,
    CacheFlagService::EVENT_BEFORE_DELETE_FLAGGED_CACHES,
    function (BeforeDeleteFlaggedTemplateCachesEvent $event) {
        $flags = $event->flags;
        ...
    }
);

Event::on(
    CacheFlagService::class,
    CacheFlagService::EVENT_AFTER_DELETE_FLAGGED_CACHES,
    function (AfterDeleteFlaggedTemplateCachesEvent $event) {
        $flags = $event->flags;
        ...
    }
);
