# FyreMigration

**FyreMigration** is a free, open-source migration library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Migration Runners](#migration-runners)
    - [Migrations](#migrations)



## Installation

**Using Composer**

```
composer require fyre/migration
```

In PHP:

```php
use Fyre\Migration\MigrationRunner;
```


## Migration Runners

**Clear**

Clear loaded migrations.

```php
MigrationRunner::clear();
```

**Current Version**

Get the current version.

```php
$currentVersion = MigrationRunner::currentVersion();
```

**Get Connection**

Get the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

```php
$connection = MigrationRunner::getConnection();
```

**Get Forge**

Get the [*Forge*](https://github.com/elusivecodes/FyreForge#forges).

```php
$forge = MigrationRunner::getForge();
```

**Get History**

Get the migration history.

```php
$history = MigrationRunner::getHistory();
```

**Get Migration**

Get a [*Migration*](#migrations).

- `$version` is a number representing the migration version.

```php
$migration = MigrationRunner::getMigration($version);
```

**Get Migrations**

Get all migrations.

```php
$migrations = MigrationRunner::getMigrations();
```

**Get Namespace**

Get the namespace.

```php
$namespace = MigrationRunner::getNamespace();
```

**Has Migration**

Check if a migration version exists.

- `$version` is a number representing the migration version.

```php
$hasMigration = MigrationRunner::hasMigration($version);
```

**Migrate**

Migrate to a version.

- `$version` is a number representing the migration version, and will default to *null*.

```php
MigrationRunner::migrate($version);
```

**Rollback**

Rollback to a version.

- `$version` is a number representing the migration version, and will default to *null*.

```php
MigrationRunner::rollback($version);
```

**Set Connection**

Set the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

- `$connection` is the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

```php
MigrationRunner::setConnection($connection);
```

**Set Namespace**

Set the namespace.

- `$namespace` is a string representing the migration namespace.

```php
MigrationRunner::setNamespace($namespace);
```


### Migrations

Migrations can be created by extending `\Fyre\Migration\Migration`, ensuring all below methods are implemented.

Your migrations must be placed in the same namespace as defined by the `setNamespace` method above.

Migration classes should follow the naming convention of `Migration_{version}` where `{version}` is the version number.

**Down**

Perform a "down" migration.

```php
$migration->down();
```

**Up**

Perform an "up" migration.

```php
$migration->up();
```