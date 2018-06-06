<?php

namespace Luthier\Debug;

use DebugBar\DataCollector\MessagesCollector;

class AuthDataCollector extends MessagesCollector
{
    public function getName()
    {
        return 'auth';
    }
}
