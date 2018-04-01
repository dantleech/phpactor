---
currentMenu: configuration
---
Configuration
=============

Configuration files are loaded from your current directory, and then from the
XDG standard user and system directories, for example:

 - `/home/daniel/www/phpactor/phpactor/.phpactor.yml`
 - `/home/daniel/.config/phpactor/phpactor.yml`
 - `/etc/xdg/phpactor/phpactor.yml`

Phpactor will merge configuration files, with more specific configurations
overriding the less specific ones.

Config Dump
-----------

Use the `config:dump` command to show the currently loaded configuration files
and all of the current settings:

```bash
$ phpactor config:dump
Config files:               
 [✔] /home/daniel/www/phpactor/phpactor/.phpactor.yml
 [✔] /home/daniel/.config/phpactor/phpactor.yml
 [𐄂] /etc/xdg/phpactor/phpactor.yml                                   

 code_transform.class_new.variants:
	exception:exception    
	autoload:vendor/autoload.php

 # ... etc
```

Reference
---------

### Core

#### autoload

*Default*: `vendor/autoload.php`

Phpactor will automatically look to see if it can use the
[composer](https://getcomposer.org) autoloader at this
path. The autoloader helps Phpactor locate classes.

#### autoload.deregister

*Default*: `true`

By default Phpactor will deregister the included autoloader to prevent
any potential conflicts

#### cache_dir

*Default*: `true`

> Directory Phpactor uses for the cache (e.g. the PhpStorm stub index).

#### logging.enabled

*Default*: `false`

> Phpactor can log information, notably RPC requests and responses in addition
to other debug information.

#### logging.fingers_crossed

*Default*: `false`

> If set to `true` only log when an error occurs, but when an error does occur
include all the log levels.

#### logging.level

*Default*: `DEBUG`

> The default logging level.

#### logging.path

*Default*: `phpactor.log`

> Where the log file is

### Code Transform Extension

#### code_transform.class_new.variants

```
code_transform.class_new.variants:                                                             
  exception: exception                          
  symfony_command: symfony_command
```

The variants available when generating new classes. The name of the variant
should match a directory in a `templates` directory, e.g.:

```
<your project root>/.phpactor/templates/
    exception/
        SourceCode.php.twig
```

or any of the XDG directories (e.g. `$HOME/.config/phpactor/templates`).

#### code_transform.template_paths

*Default*: `<xdg paths>/templates` and local project `.phpactor/templates`

Directories where class templates can be located.

#### code_transform.indentation

Indentation *characters* to use (e.g. `    ` rather than `4`).

### Navigator Extension

#### navigator.destinations

The navigator allows navigation between different aspects of the source code
(e.g. source and tests). A simple configuration would look as follows:

```
navigator.destinations:
  source:lib/<kernel>.php                      
  unit_test:tests/Unit/<kernel>Test.php 
```

This would enable you to jump (`context menu > navigate`) from
`lib/Acme/Post.php` to `tests/Unit/Acme/Post.php`.

#### navigator.autocreate

If a navigator destination doesn't exist, you can automatically create them
using a one of the `code_transform.class_new_variants`:

```
code_transform.class_new.variants:                                                             
  source: default
  unit_test: phpunit_test
  exception:exception                          
  symfony_command:symfony_command
```
