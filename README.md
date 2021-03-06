Laravel Log Keeper
======

[![Latest Version](https://img.shields.io/github/release/lifeonscreen/laravel-log-keeper.svg?style=flat-square)](https://github.com/lifeonscreen/laravel-log-keeper/releases)
[![Total Downloads](https://img.shields.io/packagist/dt/lifeonscreen/laravel-log-keeper.svg?style=flat-square)](https://packagist.org/packages/lifeonscreen/laravel-log-keeper)

Laravel Log Keeper helps rotating your logs while storing them anywhere you want with custom local/remote retention policies.

A typical usage of Laravel Log Keeper would be to set a cron job to run daily and store local logs in a S3 bucket, (s)ftp, Dropbox or any other FileSystem driver

You can define a local and/or a remote retention and/or upload after remote days, which by default is 7, 30 and 1 day subsequently.

- Files older than 1 day will be compressed and uploaded to remote disk.
- Local files older than 7 days removed from local disk.
- Remote files older than 31 (remote days+upload after days) days will be permanently deleted from the remote disk.

### Highlights

- Have full control of where/when/how you archive your logs
- Prevent your server from running out of space
- Custom retention policies for Local and Remote archiving

## Installation

Laravel Log Keeper is available via Composer:

```json
{
    "require": {
        "lifeonscreen/laravel-log-keeper": "1.*"
    }
}
```

## Setup

### Laravel

#### Register Service Provider

```php
// config/app.php

'providers' => [
    ...
    LifeOnScreen\LaravelLogKeeper\Providers\LaravelServiceProvider::class,
    ...
],
```

#### Register the cron job
```php
// app/Console/Kernel.php

protected $commands = [
    ...
    \LifeOnScreen\LaravelLogKeeper\Commands\LogKeeper::class
    ...
];

...

protected function schedule(Schedule $schedule)
{
    ...
    $schedule->command('laravel-log-keeper')->daily();
    ...
}

```

### Log Format
To use Laravel Log Keeper your log files have to be in the daily format, which is defined in your `config/app.php`
```php
    ...
    'log' => 'daily',
    ...
```

### Environment
You can override the following variables placing them in your .env

Example:

```
# .env
...

LARAVEL_LOG_KEEPER_REMOTE_DISK           = "s3"
LARAVEL_LOG_KEEPER_LOCAL_RETENTION_DAYS  = 3
LARAVEL_LOG_KEEPER_REMOTE_RETENTION_DAYS = 15
LARAVEL_LOG_KEEPER_REMOTE_PATH           = "myproject1-prod-01"

...

```

Can publish config with this command:
```
php artisan vendor:publish --provider=LifeOnScreen\LaravelLogKeeper\Providers\LaravelServiceProvider
```

Config file:
```php
// ----------------------------------------------------------------------------
    // Enable or Disable the Laravel Log Keeper.
    // If it is set to false, no operations will be performed and it will be logged
    // if the logs are enabled
    // ----------------------------------------------------------------------------
    'enabled'                     => env('LARAVEL_LOG_KEEPER_ENABLED', true),

    // ----------------------------------------------------------------------------
    // Enable or Disable the Laravel Log Keeper for remote operations.
    // if it is set to false, the local files older than the local retention will be
    // delete without being uploaded to the remote disk
    // ----------------------------------------------------------------------------
    'enabled_remote'              => env('LARAVEL_LOG_KEEPER_ENABLED_REMOTE', true),

    // ----------------------------------------------------------------------------
    // Where in the remote location it will be stored. You can leave it blank
    // or specify a custom folder like proj1-prod or proj1-integ so that you could
    // use the same s3 bucket for storing the logs in different environments
    // ----------------------------------------------------------------------------
    'remote_path'                 => rtrim(env('LARAVEL_LOG_KEEPER_REMOTE_PATH'), '/'),

    // ----------------------------------------------------------------------------
    // How many days a file will be kept on the local disk before
    // being uploaded to the remote disk.
    // Default is 7 days.
    // Local files with more than 7 days will be compressed using bzip2 and uploaded
    // to the remote disk. They will also be deleted from the local disk after being
    // uploaded
    // If value is set to 0 logs will be kept forever.
    // ----------------------------------------------------------------------------
    'local_retention_days'        => env('LARAVEL_LOG_KEEPER_LOCAL_RETENTION_DAYS', 7),

    // ----------------------------------------------------------------------------
    // When file be uploaded to remote location.
    // Default is 1 day.
    // ----------------------------------------------------------------------------
    'upload_to_remote_after_days' => env('LARAVEL_LOG_KEEPER_UPLOAD_TO_REMOTE_DAYS', 1),

    // ----------------------------------------------------------------------------
    // How many days a file will be kept on the remote for.
    // The days here means days after the upload on server. So 30 would actually
    // 30 + 1 = 31
    // Only files older than 31 days would be deleted from the remote disk
    // If value is set to 0 logs will be kept forever.
    // ----------------------------------------------------------------------------
    'remote_retention_days'       => env('LARAVEL_LOG_KEEPER_REMOTE_RETENTION_DAYS', 30),

    'remote_retention_days_calculated' =>
        env('LARAVEL_LOG_KEEPER_REMOTE_RETENTION_DAYS', 30) +
        env('LARAVEL_LOG_KEEPER_UPLOAD_TO_REMOTE_DAYS', 1),

    // ----------------------------------------------------------------------------
    // Which config/filesystems.php disk will be used for remote disk.
    // This would be typically a AWS S3 Disk, (s)ftp, Dropbox or any other configured
    // disk that will store the old logs
    // ----------------------------------------------------------------------------
    'remote_disk'                      => env('LARAVEL_LOG_KEEPER_REMOTE_DISK'),

    // ----------------------------------------------------------------------------
    // Define whether Laravel Log Keeper will log actions or not.
    // The log will be stored in the logs folders with name
    // laravel-log-keeper-{yyyy-mm-dd}.log
    // ----------------------------------------------------------------------------
    'log'                              => env('LARAVEL_LOG_KEEPER_LOG', true)
```

## Security

If you discover any security related issues, please constant us directly.

## Credits

- [Mathias Grimm](https://github.com/mathiasgrimm)
- [Jani Cerar](https://github.com/janicerar)

