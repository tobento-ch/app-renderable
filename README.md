# App Renderable

App Renderable wiring the [Renderable Service](https://github.com/tobento-ch/service-renderable) with the app: file urls using the [App Media](https://github.com/tobento-ch/app-media) file download features, pictures, HTML sanitizing and the current language.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Renderable Boot](#renderable-boot)
        - [Renderable Config](#renderable-config)
    - [Basic Usage](#basic-usage)
        - [Rendering Repository Entities](#rendering-repository-entities)
        - [Rendering In Views](#rendering-in-views)
    - [File Urls](#file-urls)
    - [Pictures](#pictures)
    - [Html Sanitizing](#html-sanitizing)
    - [Locale](#locale)
    - [Adding Renderable Types](#adding-renderable-types)
        - [Using The Config](#using-the-config)
        - [Using The App On Method](#using-the-app-on-method)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app renderable project running this command.

```
composer require tobento/app-renderable
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Renderable Boot

The renderable boot does the following:

* installs and loads renderable config file
* implements renderable interfaces based on renderable config

```php
use Tobento\App\AppFactory;
use Tobento\Service\Renderable\CompositeRenderableFactoryInterface;
use Tobento\Service\Renderable\RenderableFactoryInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots:
$app->boot(\Tobento\App\Renderable\Boot\Renderable::class);
$app->booting();

// Implemented interfaces:
$factory = $app->get(RenderableFactoryInterface::class);
$factory = $app->get(CompositeRenderableFactoryInterface::class);

// Run the app
$app->run();
```

Both interfaces resolve to the same factory instance.

### Renderable Config

The configuration for the renderable is located in the ```app/config/renderable.php``` file at the default App Skeleton config location where you can configure the renderable factory for your application, such as the file url generator, the html sanitizer or custom renderable types.

## Basic Usage

### Rendering Repository Entities

Use the entity factory with a [Storage Repository](https://github.com/tobento-ch/service-repository-storage) to get renderable entities:

```php
use Tobento\Service\Renderable\Repository\EntityFactory;
use Tobento\Service\Repository\Storage\StorageRepository;

class ArticleRepository extends StorageRepository
{
    //
}

$repository = new ArticleRepository(
    storage: $storage,
    table: 'articles',
    entityFactory: new EntityFactory(
        // Attribute name to renderable type:
        mapping: [
            'title' => 'text',
            'description' => 'html',
            'image' => 'picture',
            'downloads' => 'files',
        ],
    ),
);

$article = $repository->findById(1);
```

Check out the [Renderable Service](https://github.com/tobento-ch/service-renderable) to learn more about renderables, the available renderable types and the repository entity.

### Rendering In Views

```php
<?php /** @var \Tobento\Service\Renderable\Repository\Entity $article */ ?>

<h1><?= $view->esc($article->get('title')) ?></h1>

<?= $view->esc($article->get('image')) ?>

<?= $view->esc($article->get('description')) ?>

<?php if ($article->exists('downloads')) { ?>
    <?= $view->esc($article->get('downloads')) ?>
<?php } ?>
```

The `esc` method does not escape renderables as they are already safe for HTML output.

## File Urls

The `File` and `Files` renderables get their urls from the `Tobento\App\Renderable\FileUrlGenerator`, using the [App Media](https://github.com/tobento-ch/app-media) file download features:

* files of public storages get a download url using the `media.file.download` route.
* files of private storages get a signed download url using the `media.file.download.signed` route, only if the file has a `signed_url_expires` attribute (in minutes).

Make sure the file download features are enabled and support the storages in the ```app/config/media.php``` file:

```php
'features' => [
    new Feature\FileDownload(
        // Supported public storages:
        supportedStorages: ['uploads-public', 'downloads'],
    ),
    new Feature\FileDownloadSigned(
        // Supported private storages:
        supportedStorages: ['uploads-private'],
    ),
],
```

If a feature is not enabled, files are rendered without link.

**Custom route names**

You may change the route names in the ```app/config/renderable.php``` file:

```php
use Tobento\App\Renderable\FileUrlGenerator;

fileUrlGenerator: new FileUrlGenerator(
    storages: $container->get(StoragesInterface::class),
    router: $container->get(RouterInterface::class),
    routeName: 'media.file.download',
    signedRouteName: 'media.file.download.signed',
),
```

## Pictures

The `Picture` renderable uses the picture generator provided by the [App Media](https://github.com/tobento-ch/app-media) picture feature. Make sure it is enabled in the ```app/config/media.php``` file:

```php
'features' => [
    new Feature\Picture(),
],
```

**Picture Definitions**

The renderable boot installs the default picture definitions in the ```views/picture-definitions/``` directory, which you may adjust to your needs:

| Definition | Renderable | Used for |
| --- | --- | --- |
| `renderable-image` | [Picture](https://github.com/tobento-ch/service-renderable#picture) | rendering the picture |
| `renderable-file` | [File](https://github.com/tobento-ch/service-renderable#file) | image previews in custom views |
| `renderable-files` | [Files](https://github.com/tobento-ch/service-renderable#files) | image previews in custom views |

Check out the [App Media](https://github.com/tobento-ch/app-media) to learn more about picture definitions.

**Generating Images In Background**

By default, images are generated on the first request. If you have a running queue worker, you may generate them in background instead by setting `generateImagesInBackground` to `true` in the ```app/config/renderable.php``` file.

## Html Sanitizing

The `Html` renderable sanitizes untrusted HTML using the default sanitizer of the [App Html Sanitizer](https://github.com/tobento-ch/app-html-sanitizer). You may configure the sanitizer in the ```app/config/html_sanitizer.php``` file, or change it in the ```app/config/renderable.php``` file:

```php
use Tobento\App\HtmlSanitizer\HtmlSanitizersInterface;

htmlSanitizer: static fn (string $html): string => $container->get(HtmlSanitizersInterface::class)->get('custom')->sanitize($html),
```

## Locale

The renderable factory uses the current language key and fallbacks of the `LanguagesInterface` provided by the [App Language](https://github.com/tobento-ch/app-language), as repositories store translations by language key. If your repositories use other languages, such as area languages, adjust the `locale` and `localeFallbacks` in the ```app/config/renderable.php``` file accordingly:

```php
use Tobento\App\Language\AreaLanguagesInterface;

Renderable\RenderableFactoryInterface::class => static function (
    ContainerInterface $container,
    AreaLanguagesInterface $areaLanguages,
): Renderable\RenderableFactoryInterface {
    $languages = $areaLanguages->get('resources');
    
    return new Renderable\RenderableFactory(
        // ...
        locale: $languages->current()->key(),
        localeFallbacks: $languages->fallbacks('key'),
    );
},
```

## Adding Renderable Types

### Using The Config

You may add custom renderable types in the ```app/config/renderable.php``` file:

```php
factories: [
    'badge' => BadgeFactory::class,
    
    // or with constructor parameters:
    'price' => [PriceFactory::class, 'currency' => 'CHF'],
],
```

Check out the [Renderable Service - Custom Renderable Types](https://github.com/tobento-ch/service-renderable#custom-renderable-types) to learn more about creating renderable factories.

### Using The App On Method

You may add custom renderable types from within a boot, for instance from an app bundle, using the app `on` method. The factory is only modified once it gets resolved:

```php
use Tobento\App\Boot;
use Tobento\Service\Renderable\CompositeRenderableFactoryInterface;
use Tobento\Service\Renderable\RenderableFactoryInterface;

class BadgeBoot extends Boot
{
    public const BOOT = [
        // you may ensure the renderable boot:
        \Tobento\App\Renderable\Boot\Renderable::class,
    ];
    
    public function boot(): void
    {
        $this->app->on(
            RenderableFactoryInterface::class,
            static function (RenderableFactoryInterface $factory): void {
                if ($factory instanceof CompositeRenderableFactoryInterface) {
                    $factory->addFactory(type: 'badge', factory: BadgeFactory::class);
                }
            }
        );
    }
}
```

Use the `RenderableFactoryInterface` with the `on` method, not the `CompositeRenderableFactoryInterface`, as renderables resolve the factory by the `RenderableFactoryInterface`.

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)