[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] You can add PHP Debug Bar to your application thanks to the integration of Luthier CI with this fantastic tool)

# Debug

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Experimental feature</strong>
    <br />
    We have made an effort to make things work properly, but errors may occur rendering and / or charging of the assets required by this feature. Please <a href="https://github.com/ingeniasoftware/luthier-ci/issues/new">notify us</a> if you have had an incident during its use.
</div>

### Contents

1. [Introduction](#introduction)
2. [Activation](#activation)
3. [Debug messages](#debug-messages)
4. [Add your own data collectors](#add-your-own-data-collectors)


### <a name="introduction"></a> Introduction

You can add [PHP Debug Bar](http://phpdebugbar.com) to your application thanks to the integration of Luthier CI with this fantastic tool.

### <a name="activation"></a> Activation

To activate this feature (which is disabled by default) go to your `application/config/hooks.php` file and replace:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

With:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks(
    [
        'modules' => ['debug']
    ]
);
```

You should see the debug bar at the bottom of the window:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/19/luthier-ci-debugbar.png" alt="Luthier CI PHP Debug Bar" class="img-responsive" />
</p>

### <a name="debug-messages"></a> Debug messages


To add debug messages, use the `log()` static method of the `Luthier\Debug` class:

```php
# use Luthier\Debug;
Debug::log($variable, $type, $dataCollector);
```

Where `$variable` is the variable to debug, and `$type` is the type of message, which can be `'info'`, `'warning'` or `'error'`.

Example:

```php
<?php
# application/controllers/TestController.php

use Luthier\Debug;

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function index()
    {
        Debug::log('Welcome to Luthier-CI ' . LUTHIER_CI_VERSION . '!');
        Debug::log('Hello world!','info');
        Debug::log('This is a warning, watch out!','warning');
        Debug::log('Oh snap! an error was occurred!','error');
        $this->load->view('welcome_message');
    }
}
```

And the result:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/19/luthier-ci-debugbar-log.png" alt="Luthier CI PHP Debug Bar" class="img-responsive" />
</p>

An optional `$dataCollector` argument is the name of the [data collector](http://phpdebugbar.com/docs/data-collectors.html) where the message will be stored:

```php
Debug::log('Custom data collector','error','my_custom_data_collector');
```

If you need to store a message to be shown in the next request (for example, after submitting a form) use the `logFlash()` method, whose syntax is identical to the `log()` static method:

```php
Debug::logFlash('Hey! this will be available in the next request','info');
```

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Deactivated in production environments</strong>
    <br />
    If you set the environment of your application to <code>production</code> this feature will be automatically disabled, and any debugging code will be ignored
</div>

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Requires that there is data in the output buffer</strong>
    <br />
    Luthier CI adds the PHP Debug Bar code in the output buffer BEFORE it is processed and sent to the browser by the <code> output</code> library of CodeIgniter. Therefore, it is necessary to have used at least once the function<code>$this->load-> view()</code> or have explicitly defined an output buffer to work on. The <code>echo</code> statements DO NOT produce any internal output buffer. In addition, stopping the execution of the script with the functions <code>die</code> or <code>exit</code> will prevent the PHP Debug Bar from being displayed.
</div>

### <a name="add-your-own-data-collectors"></a> Add your own data collectors

It is possible to add your own data collectors and store messages in them. To add a data collector to the PHP Debug Bar instance, use the `addCollector()` static method:

```php
# use Luthier\Debug;
Debug::addCollector(new MyCollector());
```