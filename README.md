[<h1 align="center">Yii Sail</h1>](https://github.com/Muffin-Hayate/yii-sail)

<p align="center">
<a href="https://packagist.org/packages/muffin-hayate/yii-sail"><img src="https://img.shields.io/packagist/dt/muffin-hayate/yii-sail" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/muffin-hayate/yii-sail"><img src="https://img.shields.io/packagist/v/muffin-hayate/yii-sail" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/muffin-hayate/yii-sail"><img src="https://img.shields.io/packagist/l/muffin-hayate/yii-sail" alt="License"></a>
</p>

## Commands

### Install Services
`php yii sail/install`

### Add new Services
`php yii sail/add`

### Use Sail
`./vendor/bin/sail ...`

or.. use this nice alias from the [Laravel Sail Documentation](https://laravel.com/docs/11.x/sail#configuring-a-shell-alias):
```shell
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```
to then use it like

`sail ...` (from the root folder of the project)

## Changes

* Refactoring:
  * Removed as many Laravel dependencies as possible (there's no need for the framework here)
  * Switched from `Illuminate\Console\Command` to low-level Symfony Commands
  * Path references updated to the `muffin-hayate/yii-sail` absolute vendor path
* Added configurations (`config/params.php` & `composer.json > extras`) necessary to register the sail commands in a Yii Application
* Changed the start-command for the PHP Application (from `artisan serve` to `yii serve`) in the Dockerfiles (PHP Runtimes)
* Removed the following Features:
  * `.styleci.yml` preset changed from "laravel" to "psr-12"
  * all debugging commands removed (these were artisan specific)
  * `artisan` command replaced with `yii` command
  * `sail test` moved from PHPUnit to Codeception (specifically the `composer test` command)
  * `dusk` & `pint` commands removed (pint is laravel specific, dusk is inferior to codeception imo)
  * `sail tinker` command removed (laravel exclusive)
  * all sharing features removed
  * command to publish configuration files removed (laravel exclusive)
* Added the following features:
  * `codeception` command added
  * `psalm` & `rector` commands added

## Perspective / To-Do

* Thinking about removing the `illuminate/collections` package
* Maybe implementing a way to manipulate yii config files when installing / adding a service (like it's done with the `.env` in Laravel Sail) - (likely too complicated because it all depends on external packages etc...)
* Adding more hooks into Yii specific functionalities for the sail script (needs more Yii research on my end)

[<p align="center"><img width="294" height="69" src="/art/logo.svg" alt="Logo Laravel Sail"></p>](https://github.com/laravel/sail/)

<p align="center">
<a href="https://packagist.org/packages/laravel/sail"><img src="https://img.shields.io/packagist/dt/laravel/sail" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/sail"><img src="https://img.shields.io/packagist/v/laravel/sail" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/sail"><img src="https://img.shields.io/packagist/l/laravel/sail" alt="License"></a>
</p>

## Introduction

Sail provides a Docker powered local development experience for Laravel that is compatible with macOS, Windows (WSL2), and Linux. Other than Docker, no software or libraries are required to be installed on your local computer before using Sail. Sail's simple CLI means you can start building your Laravel application without any previous Docker experience.

#### Inspiration

Laravel Sail is inspired by and derived from [Vessel](https://github.com/shipping-docker/vessel) by [Chris Fidao](https://github.com/fideloper). If you're looking for a thorough introduction to Docker, check out Chris' course: [Shipping Docker](https://serversforhackers.com/shipping-docker).

## Official Documentation

Documentation for Sail can be found on the [Laravel website](https://laravel.com/docs/sail).

## Contributing

Thank you for considering contributing to Sail! You can read the contribution guide [here](https://github.com/laravel/sail/.github/CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/sail/security/policy) on how to report security vulnerabilities.

## License

Laravel Sail is open-sourced software licensed under the [MIT license](LICENSE.md).
