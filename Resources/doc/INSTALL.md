Netgen More Bundle installation instructions
============================================

Requirements
------------

* eZ Publish 5.4+ / eZ Publish Community Project 2014.11+

Installation steps
------------------

### Use Composer

Add the following to your composer.json and run `php composer.phar update netgen/more-bundle` to refresh dependencies:

```json
"repositories": [
    { "type": "composer", "url": "http://packagist.netgen.biz" }
],
"require": {
    "netgen/more-bundle": "~2.0.0"
}
```

### Activate the bundle

Activate the bundle (together with other required bundles) in `ezpublish\EzPublishKernel.php` file.

```php
public function registerBundles()
{
   ...

    $bundles[] = new \Netgen\Bundle\MetadataBundle\NetgenMetadataBundle();
    $bundles[] = new \Netgen\Bundle\ContentTypeListBundle\NetgenContentTypeListBundle();
    $bundles[] = new \Netgen\Bundle\EnhancedSelectionBundle\NetgenEnhancedSelectionBundle();
    $bundles[] = new \Netgen\Bundle\MoreGeneratorBundle\NetgenMoreGeneratorBundle();
    $bundles[] = new \Netgen\Bundle\MoreBundle\NetgenMoreBundle();

    return $bundles;
}
```

### Clear the caches

Clear eZ Publish 5 caches.

```bash
php ezpublish/console cache:clear
```
