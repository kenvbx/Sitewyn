<?php

namespace Sitewyn\Core\Base\Exceptions;

use RuntimeException;

class PluginDependencyException extends RuntimeException
{
    /**
     * @param  array<int, string>  $related
     */
    public function __construct(
        string $message,
        public readonly string $slug,
        public readonly array $related = [],
    ) {
        parent::__construct($message);
    }
}
