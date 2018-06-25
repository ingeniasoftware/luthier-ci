[//]: # ([author] Anderson Salas, translated by Julio Cedeño)
[//]: # ([meta_description] Learn how to get Luthier CI and install it in your CodeIgniter application with step-by-step instructions, it takes no more than 5 minutes!)

# Installation

### Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
   1. [Get Luthier CI](#get-luthier-ci)
   2. [Enable Composer autoload and hooks](#enable-composer-autoload-and-hooks)
   3. [Connect Luthier CI with your application](#connect-luthier-ci-with-your-application)
3. [Initialization](#initialization)

### <a name="requirements"></a> Requirements

* PHP >= 5.6 (Compatible con PHP 7)
* CodeIgniter 3

### <a name="installation"></a> Installation

#### <a name="get-luthier-ci"></a> Get Luthier CI

<div class="alert alert-info">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <strong>Composer required</strong>
    <br />
    Luthier CI is installed through Composer. You can get it <a href="https://getcomposer.org/download/">here</a>.
</div>

Go to the `application` folder and execute the following command:

```bash
composer require luthier/luthier
```

#### <a name="enable-composer-autoload-and-hooks"></a> Enable Composer _autoload_ and _hooks_

For Luthier CI to work, it's necessary that both the Composer **autoload** and the **hooks** are enabled in the framework. In the file `config.php` modify the following:

```php
<?php
# application/config/config.php

// (...)

$config['enable_hooks']      = TRUE;
$config['composer_autoload'] = TRUE;

// (...)
```

#### <a name="connect-luthier-ci-with-your-application"></a> Connect Luthier CI with your application

In the `hooks.php` file, assign the Luthier CI hooks to the `$hook` variable:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

In the `routes.php` file, assign the Luthier CI routes to the `$route` variable:

```php
<?php
# application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$route = Luthier\Route::getRoutes();
```

### <a name="initialization"></a> Initialization

The first time that Luthier CI is executed some files and folders are created automatically:

* `routes/web.php`: HTTP routes file
* `routes/api.php`: AJAX routes file
* `routes/cli.php`: CLI routes file
* `controllers/Luthier.php`: Fake controller, necessary to use some routes
* `middleware`: Folder to save the middleware files

During the initialization of the framework the hooks are called: `Luthier\Hook::getHooks()` returns an array with the hooks used by Luthier CI, including the one needed to boot it. At this point, Luthier CI analyzes and compiles all the routes defined in the first three files mentioned above. Then, when the framework loads the routes in the `application/config/routes.php` file, `Luthier\Route::getRoutes()` returns an array with the routes in the format that CodeIgniter understands. All the following is the normal execution of the framework.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Writing permissions</strong>
    <br />
    If you get errors during the creation of the Luthier CI base files, it may be due to insufficient permissions. Make sure that the <code>application</code> folder has write permission
</div>
