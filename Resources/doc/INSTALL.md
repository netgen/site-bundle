Netgen More Bundle installation instructions
============================================

Requirements
------------

* eZ Platform 1.0+

Installation steps
------------------

### Use Composer

Add the following to your composer.json and run `php composer.phar update netgen/more-bundle` to refresh dependencies:

```json
"repositories": [
    { "type": "composer", "url": "http://packagist.netgen.biz" }
],
"require": {
    "netgen/more-bundle": "~2.1.0"
}
```

### Activate the bundle

Activate the bundle (together with other required bundles) in `app/AppKernel.php` file.

```php
public function registerBundles()
{
   ...

    $bundles[] = new Netgen\Bundle\EzFormsBundle\NetgenEzFormsBundle();
    $bundles[] = new Netgen\Bundle\OpenGraphBundle\NetgenOpenGraphBundle();
    $bundles[] = new Netgen\Bundle\MetadataBundle\NetgenMetadataBundle();
    $bundles[] = new Netgen\Bundle\ContentTypeListBundle\NetgenContentTypeListBundle();
    $bundles[] = new Netgen\Bundle\EnhancedSelectionBundle\NetgenEnhancedSelectionBundle();
    $bundles[] = new Netgen\TagsBundle\NetgenTagsBundle();
    $bundles[] = new Netgen\Bundle\MoreBundle\NetgenMoreBundle();

    return $bundles;
}
```

### Clear the caches

Clear eZ Publish caches.

```bash
php app/console cache:clear
```
