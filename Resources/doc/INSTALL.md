Netgen More Bundle installation instructions
============================================

Requirements
------------

* eZ Platform 2.0+

Installation steps
------------------

### Use Composer

Add the following to your `composer.json` and run `composer update netgen/more-bundle` to refresh dependencies:

```json
"repositories": [
    { "type": "composer", "url": "https://packagist.netgen.biz" }
],
"require": {
    "netgen/more-bundle": "~4.0.0"
}
```

### Activate the bundle

Activate the bundle (together with other required bundles) in `app/AppKernel.php` file.

```php
public function registerBundles()
{
   ...

    $bundles[] = new Knp\Bundle\MenuBundle\KnpMenuBundle();
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

Clear eZ Platform caches.

```bash
php bin/console cache:clear
```
