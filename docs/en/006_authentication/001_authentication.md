# Authentication

CodeIgniter comes with everything you need to build your user authentication system, however, it does not include a library dedicated exclusively to this function.

Luthier CI addresses this with a Symfony-inspired model, looking for the flexibility that the developer needs to start working quickly, without reinventing the wheel.

### Activating the authentication system

By default, the authentication capabilities of Luthier CI are disabled. To activate them, go to the `application/config/hooks.php` file and modify the `Luthier\Hook::getHooks()` method with the following:


```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks(
    [
        'modules' => ['auth']
    ]
);
```





