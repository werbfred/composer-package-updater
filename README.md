# Composer Package Updater

This project provides a way to have scripts be executed on packages when they are being installed, updated and uninstalled.

Currently only scripts defined in the root __*composer.json*__ file are being executed. This is a workaround to Composer's [Issue 1993](https://github.com/composer/composer/issues/1193).

For security reasons, __*Composer\Script\PackageEvent*__ will only be forwarded to the packages for which these events are being raised.

In my case, I have Drupal modules and themes hosted in a git repo. Once installed/updated via composer I want to:
- Run __*compass*__ to generate .css files
- Remove some files (.gitignore, .sass directory, ...)

Each module or theme being responsible to perform its own install/update/uninstall tasks.

## Setting it up !

Install this project via packagist :

```
composer require mathias-meyer/composer-package-update
```

Open the root  __*composer.json*__ file to update it with configuration below:

### Autoload

Add `"ComposerPackageUpdater\\composer\\": "vendor/mathias-meyer/src/composer"` to `psr-4` in `autoload` section.

#### Example:

```
"autoload": {
   "psr-4": {
      "ComposerPackageUpdater\\composer\\": "vendor/mathias-meyer/src/composer"
   }
}
```

This will tell Composer where files of a given namespace are located. In our case Composer will be able to load our __*PackageUpdater*__ class. 

<ins>__*Note*__</ins>: More information on `autoload` section can be found [here](https://getcomposer.org/doc/04-schema.md#autoload).

### Scripts

Add below entries to the `scripts` section.

```
"scripts": {
   "pre-package-install": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent",
   "post-package-install": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent",
   "pre-package-update": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent",
   "post-package-update": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent",
   "pre-package-uninstall": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent",
   "post-package-uninstall": "ComposerPackageUpdater\\composer\\PackageUpdater::processPackageEvent"
}
```

The above will ensure that below __*PackageEvents*__ can be forwarded :
- pre-package-install
- post-package-install
- pre-package-update
- post-package-update
- pre-package-uninstall
- post-package-uninstall

<ins>__*Note*__</ins>: More information on `scripts` section can be found  [here](https://getcomposer.org/doc/articles/scripts.md) and [there](https://getcomposer.org/doc/04-schema.md#scripts).

### Configuration

Configuration must be added under `dependency-scripts` in `extra` section.

#### Parameters:

|Parameter|type|Default|Description|
|-|-|-|-|
|run|bool|false|If __*true*__ package scripts will be called. Any other value will be considered as __*false*__.|
|trust|array|[]|List of packages for which you want the __*Composer\Script\PackageEvent*__  be dispatched.|

#### Example:

```
"extra": {
   "dependency-scripts": {
      "run": true,
      "trust": [ "dummy"
               , "foobar" ]
   }
}
```

<ins>__*Note*__</ins>: More information on `extra` section can be found [here](https://getcomposer.org/doc/04-schema.md#extra).

## What do I need to put in my package's __composer.json*__ file ?

You can now fill your `scripts` section with your own ones.

<ins>__*Be careful*__</ins>: Pathes in your `autoloads` section must be relative to your package's location.

## Usefull Links

- Composer.json schema : [https://getcomposer.org/doc/04-schema.md]()
- More on scripts : [https://getcomposer.org/doc/articles/scripts.md]()
