# Commented Include for Twig

This is a simple Twig Extension which helps debugging and navigating through
many Twig partials in your project. It outputs a HTML comments before and after each
`include` statement while rendering the template. Comments look like this:

```html
<!-- Begin output of "_partials/_navigation.twig" -->
<div class="navigation" role="navigation" data-navigation>...</div>
<!-- / End output of "_partials/_navigation.twig" -->
```

Installation
------------
To install the latest stable version of this component, open a console and execute the following command:
```bash
composer require djboris88/twig-commented-include --dev
```

Usage
-----
The first step is to register the extension into the twig environment
```php
/** @var $twig Twig_Environment */
$twig->addExtension(new Djboris88\Twig\Extension\CommentedIncludeExtension());
```

Once registered, it will automatically add the HTML comments before and after every `include` tag
in the Twig files.

Symfony Bundle
--------------

If you want to use this extension in your Symfony application, you can enable the
Symfony Bundle included in this package.

The bundle will register the twig extension automatically.

### Symfony 2/3

```php
// app/AppKernel.php
if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
    $bundles[] = new Djboris88\Twig\Extension\CommentedIncludeTwigExtensionBundle();
}
```

### Symfony 4

```php
// config/bundles.php
//...
return [
    //...
    Djboris88\Twig\Extension\CommentedIncludeTwigExtensionBundle::class => ['dev' => true]
];
```

License
-------

This component is under the GPL 3.0 license. See the complete license in the [LICENSE] file.


Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker].


Author Information
------------------

Developed with ♥ by Boris Đemrovski.

If you find this component useful, please add a ★ in the [GitHub repository page] and/or the [Packagist package page].

[LICENSE]: LICENSE
[Github issue tracker]: https://github.com/djboris88/twig-include-comments/issues
[GitHub repository page]: https://github.com/djboris88/twig-include-comments
[Packagist package page]: https://packagist.org/packages/djboris88/twig-include-comments
