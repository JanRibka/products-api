<?php

declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'Migrations',
        'version_column_name' => 'Version',
        'version_column_length' => 512,
        'executed_at_column_name' => 'ExecutedAt',
        'execution_time_column_name' => 'ExecutionTime',
    ],
    'migrations_paths' => [
        'Migrations' => __DIR__ . '/../migrations',
    ],
    'all_or_nothing' => false,
    'transactional' => false,
    'check_database_platform' => true,
    'organize_migrations' => 'none',
    'connection' => null,
    'em' => null,
];
