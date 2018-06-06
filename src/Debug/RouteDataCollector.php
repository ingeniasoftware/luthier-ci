<?php

/**
 * RouteDataCollector class
 *
 * This is a generic Data Collector for Luthier-CI internal routing.
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier\Debug;

use DebugBar\DataCollector\MessagesCollector;

class RouteDataCollector extends MessagesCollector
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'routing';
    }
}