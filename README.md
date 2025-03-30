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

**Add Namespace**

Add a namespace for loading migrations.

- `$namespace` is a string representing the namespace.

```php
$runner->addNamespace($namespace);
```

**Clear**

Clear all namespaces and migrations.

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

**Get Migrations**

Get all migrations.

```php
$migrations = $runner->getMigrations();
```

**Get Namespaces**

Get the namespaces.

```php
$namespaces = $runner->getNamespaces();
```

**Has Namespace**

Determine whether a namespace exists.

- `$namespace` is a string representing the namespace.

```php
$hasNamespace = $php->hasNamespace($namespace);
```

**Migrate**

Migrate to the latest version.

```php
$runner->migrate($latestVersion);
```

**Rollback**

Rollback to a previous version.

- `$batches` is a number representing the number of batches to rollback, and will default to *1*.
- `$steps` is a number representing the number of steps to rollback, and will default to *null*.

```php
$runner->rollback($batches, $steps);
```

**Set Connection**

Set the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

- `$connection` is the [*Connection*](https://github.com/elusivecodes/FyreDB#connections).

```php
$runner->setConnection($connection);
```

**Remove Namespace**

Remove a namespace.

- `$namespace` is a string representing the namespace.

```php
$runner->removeNamespace($namespace);
```


## Migrations

Migrations can be created by extending the `\Fyre\Migration\Migration` class, prefixing the class name with "*Migration_*".

To allow discovery of the migration, add the the namespace to the *MigrationRunner*.

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

- `$name` is a string representing the migration name.
- `$batch` is a number representing the batch number.

```php
$history->add($name, $batch);
```

**All**

Get the migration history.

```php
$all = $history->all();
```

**Delete**

Delete a migration from the history.

- `$name` is a string representing the migration name.

```php
$history->delete($name);
```

**Get Next Batch**

Get the next batch number.

```php
$batch = $history->getNextBatchNumber();
```


## Commands

### Migrate

Perform database migrations.

- `--db` is the [*ConnectionManager*](https://github.com/elusivecodes/FyreDB) config key, and will default to `ConnectionManager::DEFAULT`.

```php
$commandRunner->run('db:migrate', ['--db', 'default']);
```

### Rollback

Perform database rollbacks.

- `--db` is the [*ConnectionManager*](https://github.com/elusivecodes/FyreDB) config key, and will default to `ConnectionManager::DEFAULT`.
- `--batches` is the number of batches to rollback, and will default to *1*.
- `--steps` is the number of steps to rollback, and will default to *null*.

```php
$commandRunner->run('db:rollback', ['--db', 'default', '--batches', '1', '--steps', 1]);
```