<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

function elog_example_project_root(): string
{
    $root = getenv('ELOG_EXAMPLE_PROJECT_ROOT');

    if (is_string($root) && $root !== '') {
        return $root;
    }

    return dirname(__DIR__);
}
