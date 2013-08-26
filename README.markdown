BuccioniSitemapBundle
=====================

The BuccioniSitemapBundle provides a way to create a sitemap writing files directly in Symfony2. For more information about sitemaps go to [sitemaps.org](http://www.sitemaps.org/). We can say that this Bundle is a 'clone' of the [BerriartSitemapBundle](https://github.com/falcacibar/BerriartSitemapBundle) and [AvalancheSitemapBundle](https://github.com/avalanche123/AvalancheSitemapBundle), but instead of using DoctrineMongoDBBundle or DoctrineBundle with MySQL, it works directly with the XML files..

Features include:

- Direct I/O to read and write XML files
- Generates sitemapindex and sitemaps
- Command for populating the sitemap with previusly created urls
- (Incomming) Command for update the sitemap with new content
- Compatibility with BerriartSitemapBundle

Documentation
-------------

The bulk of the documentation is stored in the `Resources/doc/index.md`
file in this bundle:

[Read the Documentation](https://github.com/falcacibar/BuccioniSitemapBundle/blob/master/Resources/doc/index.md)

Installation
------------

All the installation instructions are located in [documentation](https://github.com/falcacibar/BuccioniSitemapBundle/blob/master/Resources/doc/index.md).

License
-------

This bundle is under the [GPL2 license](https://github.com/falcacibar/BuccioniSitemapBundle/blob/master/Resources/meta/LICENSE). See the complete license in the bundle:

    Resources/meta/LICENSE

About
-----

BuccioniSitemapBundle idea was born in [Loogares.com](http://www.loogares.com) HQ

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/falcacibar/BuccioniSitemapBundle/issues)

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.