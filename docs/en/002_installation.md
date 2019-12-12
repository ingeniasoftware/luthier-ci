# Installation

Installing Luthier CI is easy and, in most cases, it does not take more than 5 minutes. Be sure to meet the requirements described below and follow the installation steps.

<!-- %index% -->

### Requirements

* PHP >= 5.6 (Compatible with PHP 7)
* CodeIgniter >= 3.0

### Installation Steps

#### Step 1: Get Luthier CI

<div class="alert alert-info">
    <strong>Composer required</strong><br />
    Luthier CI is installed using Composer. You can get it  <a href="https://getcomposer.org/download/">here</a>.
</div>

Go to the CodeIgniter `application` folder of and execute the following command:

```bash
composer require luthier/luthier
```

#### Step 2: Enable composer autoload and hooks

It is necessary that both **Composer's autoload** and **hooks** are enabled in your application. In the `config.php` file modify the following:

```php
<?php
# application/config/config.php

// (...)

$config['enable_hooks'] = TRUE;
$config['composer_autoload'] = TRUE;

// (...)
```

#### Step 3: Connect Luthier CI with your application

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

### Initialization

The first time Luthier CI runs in your application, some files and folders are created automatically:

* `routes/web.php`: HTTP routes file
* `routes/api.php`: AJAX route file
* `routes/cli.php`: CLI route file
* `controllers/Luthier.php`: Fake controller, necessary to use some routes
* `middleware`: Folder to save middleware files

`Luthier\Hook::getHooks()` returns an array with the hooks used by Luthier CI, including the one needed to boot it.   `Luthier\Route::getRoutes()` returns an array with the routes in the format that CodeIgniter understands. All of the following is the normal execution of the framework.

<div class="alert alert-warning">
    <strong>Write permissions</strong>
    <br />
    If errors occur during the creation of the files mentioned above, it may be due to insufficient permissions. Make sure the application folder has write permissions
</div>