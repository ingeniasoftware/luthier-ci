<?php

/**
 * DatabaseDataCollector class
 *
 * This is a generic Data Collector for executed queries within the application.
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Debug;

use DebugBar\DataCollector\MessagesCollector;

class DatabaseDataCollector extends MessagesCollector
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'database';
    }
}