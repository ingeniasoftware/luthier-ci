# Debug

You can add [PHP Debug Bar](http://phpdebugbar.com) to your application thanks to the integration of Luthier CI with this fantastic tool.

<div class="alert alert-info">
    Being a mainly development tool, it will be automatically deactivated in any environment other than <strong>development</strong>
</div>

<!-- %index% -->

### Activation

The debugging capabilities of Luthier CI are disabled by default. To activate them, go to your `application/config/hooks.php` file and modify the `Luthier\Hook::getHooks()` method with the following:

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

### Debug Messages

To add debug messages, use the `Debug::log()` method:

```php
use Luthier\Debug;

Debug::log($variable, $type);
```

Where `$variable` is the variable (or expression) to debug, and `$type` is the type of message, which can be `info`, `warning` or `error`:

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

The `Debug::log()` method accepts a `$dataCollector` third argument, which is the name of the [data collector](http://phpdebugbar.com/docs/data-collectors.html) where the message will be logged:

```php
Debug::log('Custom data collector','error','my_custom_data_collector');
```

If you need to store a message to be displayed in the following request (for example, after submitting a form) use the `Debug::logFlash()` method:

```php
Debug::logFlash('Hey! this will be available in the next request','info');
```

<div class="alert alert-warning">
    <strong>Requires that there is data in the output buffer</strong><br />
    Luthier CI adds the PHP Debug Bar code to the output buffer BEFORE it is processed and sent to the browser by the <strong>CodeIgniter Output Library</strong>. Therefore, it is necessary to have used the <code>$this->load->view()</code> function at least once, or to have explicitly defined an output buffer on which to work. <code>echo</code> statements DO NOT produce any internal output buffer. In addition, stopping the execution of the script with the die or exit functions will prevent PHP Debug Bar from being displayed.
</div>

### Add external data collectors

To add an external data collector to the PHP Debug Bar instance, use the `Debug::addCollector()` method:

```php
use Luthier\Debug;

Debug::addCollector(new MyCollector());
```