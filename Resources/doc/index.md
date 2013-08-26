BuccioniSitemapBundle Documentation
===================================

## Requirements

This BuccioniSitemapBundle only requires Twig to process the templates

## Credits

This Bundle is fully inspired by the [BerriartSitemapBundle](https://github.com/artberri/BerriartSitemapBundle) and [AvalancheSitemapBundle](https://github.com/avalanche123/AvalancheSitemapBundle), but instead of using DoctrineMongoDBBundle or DoctrineBundle with MySQL, it works directly with the XML files.

## Installation

Follow these steps to complete the installation:

1. Download BuccioniSitemapBundle
2. Configure the Autoloader
3. Enable the Bundle
4. Configure the BuccioniSitemapBundle
5. Import BuccioniSitemapBundle routing

### Step 1: Download BuccioniSitemapBundle

Ultimately, the BuccioniSitemapBundle files should be downloaded to the
`vendor/bundles/Buccioni/Bundle/SitemapBundle` directory.

This can be done in several ways, depending on your preference. The first
method is the standard Symfony2 method.

**Using the vendors script**

Add the following lines in your `deps` file:

```
[BuccioniSitemapBundle]
    git=http://github.com/falcacibar/BuccioniSitemapBundle.git
    target=bundles/Buccioni/Bundle/SitemapBundle
```

Now, run the vendors script to download the bundle:

``` bash
$ php bin/vendors install
```

**Using submodules**

If you prefer instead to use git submodules, then run the following:

``` bash
$ git submodule add http://github.com/falcacibar/BuccioniSitemapBundle.git vendor/bundles/Buccioni/Bundle/SitemapBundle
$ git submodule update --init
```

### Step 2: Configure the Autoloader

Add the `Buccioni` namespace to your autoloader:

``` php
<?php
// app/autoload.php

$loader->registerNamespaces(array(
    // ...
    'Buccioni' => __DIR__.'/../vendor/bundles',
));
```

### Step 3: Enable the bundle

Finally, enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Buccioni\Bundle\SitemapBundle\BuccioniSitemapBundle(),
    );
}
```

### Step 4: Configure the BuccioniSitemapBundle

The next step is to configure the bundle. Add the following configuration to
your `config.yml` based on your project's url.

``` yaml
# app/config/config.yml
buccioni_sitemap:
    base_url: http://example.org # it will be used if you store relative urls
```

Or if you prefer XML:

``` xml
# app/config/config.xml
<!-- app/config/config.xml -->

<buccioni_sitemap:config
    base-url="orm"
/>
```

The default alias of the bundle's sitemap service is `sitemap`, you can change it
adding the alias configuration.

The default number of urls (locs) per sitemap page is 50000, you can change it from
the bundle configuration too.

``` yaml
# app/config/config.yml
buccioni_sitemap:
    ## Base URL in case of relative paths
    base_url: http://example.com
    ## Your own sitemap alias
    #alias: sitemap
    ## Sitemap default file name
    #file_name: sitemap
    ## Sitemap Index default file name
    #index_file_name: sitemapindex
    # Default directory to store files
    #dir: web/assets/sitemap
    ## Server side directory of stored files
    #dir_server_path: /assets/sitemap
    ## Swap Sitemap name with Index in case of multiple sitemaps
    #swap_sitemap_file_name: false
    ## Max URL Limit per sitemap file
    #url_limit: 50000
```

**Note:**

> The `base_url` will be added to the relative urls added to the sitemap.

**Warning:**

> You need either to use the `auto_mapping` option of the corresponding bundle
> (done by default for DoctrineBundle in the standard distribution) or to
> activate the mapping for BuccioniSitemapBundle otherwise the mapping
> will be ignored.

### Step 5: Import BuccioniSitemapBundle routing file

By importing the routing file you will activate the `/sitemapindex.xml` and
`/sitemap.xml` routes. If you prefer others, create your own routings.

In YAML:

``` yaml
# app/config/routing.yml
buccioni_sitemap:
    resource: "@BuccioniSitemapBundle/Resources/config/routing.yml"
    prefix:   /
```

Or if you prefer XML:

``` xml
<!-- app/config/routing.xml -->
<import resource="@BuccioniSitemapBundle/Resources/config/routing.yml"/>
```

### Next Steps

Now that you have completed the basic installation and configuration of the
BuccioniSitemapBundle, you are ready to learn how to use it.

The following documents are available:

- [Adding/Editing/Removing urls from sitemap](manage_sitemap.md)
- [Populating the sitemap with existing urls](populating_sitemap.md)

## Future features

We are planning to add this features, if you have any better idea suggest it to us.

- Update Chain
- URL Deleting

Remember, Code Is Poetry
