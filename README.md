# FyreMigration

**FyreMigration** is a free, open-source migration library for *PHP*.


## Table Of Contents
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Methods](#methods)
- [Migrations](#migrations)
- [Migration Histories](#migration-histories)
- [Commands](#commands)
    - [Migrate](#migrate)
    - [Rollback](#rollback)



## Installation

**Using Composer**

```
composer require fyre/migration
```

In PHP:

```php
use Fyre\Migration\MigrationRunner;
```


## Basic Usage

- `$container` is a [*Container*](https://github.com/elusivecodes/FyreContainer).
- `$loader` is a [*Loader*](https://github.com/elusivecodes/FyreLoader).
- `$connectionManager` is a [*ConnectionManager*](https://github.com/elusivecodes/FyreDB).
- `$forgeRegistry` is a [*ForgeRegistry*](https://github.com/elusivecodes/FyreForge).

```php
$runner = new MigrationRunner($container, $loader, $connectionManager, $forgeRegistry);
```

**Autoloading**

It is recommended to bind the *MigrationRunner* to the [*Container*](https://github.com/elusivecodes/FyreContainer) as a singleton.

```php
$container->singleton(MigrationRunner::class);
```

Any dependencies will be injected automatically when loading from the [*Container*](https://github.com/elusivecodes/FyreContainer).

```php
$runner = $container->use(MigrationRunner::class);
```


## Methods

**Clear**

Clear loaded migrations.

```php
$runner->clear();
```

**Get Connection**

Get the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

```php
$connection = $runner->getConnection();
```

**Get Forge**

Get the [*Forge*](https://github.com/elusivecodes/FyreForge#forges).

```php
$forge = $runner->getForge();
```

**Get History**

Get the [*MigrationHistory*](#migration-histories).

```php
$history = $runner->getHistory();
```

**Get Migration**

Get a [*Migration*](#migrations).

- `$version` is a number representing the migration version.

```php
$migration = $runner->getMigration($version);
```

**Get Migrations**

Get all migrations.

```php
$migrations = $runner->getMigrations();
```

**Get Namespace**

Get the namespace.

```php
$namespace = $runner->getNamespace();
```

**Has Migration**

Determine whether a migration version exists.

- `$version` is a number representing the migration version.

```php
$hasMigration = $runner->hasMigration($version);
```

**Migrate**

Migrate to a version.

- `$version` is a number representing the migration version, and will default to *null*.

```php
$runner->migrate($version);
```

**Rollback**

Rollback to a version.

- `$version` is a number representing the migration version, and will default to *null*.

```php
$runner->rollback($version);
```

**Set Connection**

Set the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

- `$connection` is the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

```php
$runner->setConnection($connection);
```

**Set Namespace**

Set the namespace.

- `$namespace` is a string representing the migration namespace.

```php
$runner->setNamespace($namespace);
```


## Migrations

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


## Migration Histories

**Add**

Add a migration version to the history.

- `$version` is a number representing the migration version.

```php
$history->add($version);
```

**All**

Get the migration history.

```php
$all = $history->all();
```

**Current**

Get the current version.

```php
$version = $history->current();
```


## Commands

### Migrate

Perform database migrations.

- `--db` is the [*ConnectionManager*](https://github.com/elusivecodes/FyreDB) config key, and will default to `ConnectionManager::DEFAULT`.
- `--version` is the migration version, and will default to *null*.

```php
$commandRunner->run('db:migrate', ['--db', 'default', '--version', '2']);
```

### Rollback

Perform database rollbacks.

- `--db` is the [*ConnectionManager*](https://github.com/elusivecodes/FyreDB) config key, and will default to `ConnectionManager::DEFAULT`.
- `--version` is the migration version, and will default to *null*.

```php
$commandRunner->run('db:rollback', ['--db', 'default', '--version', '1']);
```