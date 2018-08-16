Netgen Site Bundle installation instructions
============================================

Requirements
------------

* eZ Platform 2.0+

Installation steps
------------------

### Use Composer

Run the following to install the bundle:

```bash
composer require netgen/site-bundle
```

### Activate the bundle

Activate the bundle (together with other required bundles) in `app/AppKernel.php` file.

```php
public function registerBundles()
{
   ...

    $bundles[] = new Knp\Bundle\MenuBundle\KnpMenuBundle();
    $bundles[] = new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle();
    $bundles[] = new Netgen\Bundle\EzPlatformSiteApiBundle\NetgenEzPlatformSiteApiBundle();
    $bundles[] = new Netgen\Bundle\EzFormsBundle\NetgenEzFormsBundle();
    $bundles[] = new Netgen\Bundle\SiteAccessRoutesBundle\NetgenSiteAccessRoutesBundle();
    $bundles[] = new Netgen\Bundle\OpenGraphBundle\NetgenOpenGraphBundle();
    $bundles[] = new Netgen\Bundle\MetadataBundle\NetgenMetadataBundle();
    $bundles[] = new Netgen\Bundle\ContentTypeListBundle\NetgenContentTypeListBundle();
    $bundles[] = new Netgen\Bundle\EnhancedSelectionBundle\NetgenEnhancedSelectionBundle();
    $bundles[] = new Netgen\Bundle\BirthdayBundle\NetgenBirthdayBundle();
    $bundles[] = new Lolautruche\EzCoreExtraBundle\EzCoreExtraBundle();
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
