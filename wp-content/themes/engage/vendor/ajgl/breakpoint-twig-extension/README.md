AjglBreakpointTwigExtension
===========================

The AjglBreakpointTwigExtension component allows you set breakpoints in twig templates.

[![Build Status](https://github.com/ajgarlag/AjglBreakpointTwigExtension/workflows/tests/badge.svg?branch=master)](https://github.com/ajgarlag/AjglBreakpointTwigExtension/actions)
[![Latest Stable Version](https://poser.pugx.org/ajgl/breakpoint-twig-extension/v/stable.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)
[![Latest Unstable Version](https://poser.pugx.org/ajgl/breakpoint-twig-extension/v/unstable.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)
[![Total Downloads](https://poser.pugx.org/ajgl/breakpoint-twig-extension/downloads.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)
[![Montly Downloads](https://poser.pugx.org/ajgl/breakpoint-twig-extension/d/monthly.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)
[![Daily Downloads](https://poser.pugx.org/ajgl/breakpoint-twig-extension/d/daily.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)
[![License](https://poser.pugx.org/ajgl/breakpoint-twig-extension/license.png)](https://packagist.org/packages/ajgl/breakpoint-twig-extension)

This component requires the [Xdebug] PHP extension to be installed.


Installation
------------

To install the latest stable version of this component, open a console and execute the following command:
```bash
composer require ajgl/breakpoint-twig-extension --dev
```


Usage
-----

The first step is to register the extension into the twig environment
```php
/* @var $twig Twig_Environment */
$twig->addExtension(new Ajgl\Twig\Extension\BreakpointExtension());
```

Once registered, you can call the new `breakpoint` function:
```twig
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>title</title>
  </head>
  <body>
    {{ breakpoint() }}
  </body>
</html>
```

Once stopped, your debugger will allow you to inspect the `$environment` and `$context` variables.

### Function arguments

Any argument passed to the twig function will be added to the `$arguments` array, so you can inspect it easily.

```twig
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>title</title>
  </head>
  <body>
    {{ breakpoint(app.user, app.session) }}
  </body>
</html>
```

Symfony Bundle
--------------

If you want to use this extension in your Symfony application, you can enable the
Symfony Bundle included in this package.

The bundle will register the twig extension automatically. So, once enabled, you
can insert the `breakpoint` twig function in your templates.

### Symfony 4/5/6

```php
// config/bundles.php
//...
return [
    //...
    Ajgl\Twig\Extension\SymfonyBundle\AjglBreakpointTwigExtensionBundle::class => ['dev' => true]
];
```

License
-------

This component is under the MIT license. See the complete license in the [LICENSE] file.


Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker].


Author Information
------------------

Developed with ♥ by [Antonio J. García Lagar].

If you find this component useful, please add a ★ in the [GitHub repository page].

[Xdebug]: https://xdebug.org/
[LICENSE]: LICENSE
[Github issue tracker]: https://github.com/ajgarlag/AjglBreakpointTwigExtension/issues
[Antonio J. García Lagar]: http://aj.garcialagar.es
[GitHub repository page]: https://github.com/ajgarlag/AjglBreakpointTwigExtension
[Packagist package page]: https://packagist.org/packages/ajgl/breakpoint-twig-extension
