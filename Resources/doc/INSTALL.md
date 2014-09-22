Netgen More Bundle installation instructions
============================================

Requirements
------------

* eZ Publish 5.3+ / eZ Publish Community Project 2014.07+

Installation steps
------------------

### Use Composer

Add the following to your composer.json and run `php composer.phar update netgen/more-bundle` to refresh dependencies:

```json
"repositories": [
    { "type": "composer", "url": "http://packagist.netgen.biz" }
],
"require": {
    "netgen/more-bundle": "dev-master"
}
```

### Activate the bundle

Activate the bundle (together with other required bundles) in `ezpublish\EzPublishKernel.php` file.

```php
use Netgen\Bundle\MoreBundle\NetgenMoreBundle;
use Netgen\Bundle\MetadataBundle\NetgenMetadataBundle;
use Netgen\Bundle\ContentTypeListBundle\NetgenContentTypeListBundle;
use Netgen\Bundle\EnhancedSelectionBundle\NetgenEnhancedSelectionBundle;

...

public function registerBundles()
{
   $bundles = array(
       new FrameworkBundle(),
       ...
       new NetgenMetadataBundle(),
       new NetgenContentTypeListBundle(),
       new NetgenEnhancedSelectionBundle(),
       new NetgenMoreBundle()
   );

   ...
}
```

### Edit configuration

Put the following config in your `ezpublish/config/config.yml` file to be able to use legacy page layouts provided with the bundle:

```yml
ez_publish_legacy:
    system:
        YOUR_SITEACCESS_NAME:
            templating:
                view_layout: NetgenMoreBundle::pagelayout_legacy.html.twig
                module_layout: NetgenMoreBundle::pagelayout_module.html.twig
        YOUR_SITEACCESS_NAME:
            templating:
                view_layout: NetgenMoreBundle::pagelayout_legacy.html.twig
                module_layout: NetgenMoreBundle::pagelayout_module.html.twig
```

Be sure to replace `YOUR_SITEACCESS_NAME` text with the name of your frontend siteaccess.

### Clear the caches

Clear eZ Publish 5 caches.

```bash
php ezpublish/console cache:clear
```
