<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__ . '/src');

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_import_per_statement' => false,
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => true,
        ],
        'no_unused_imports' => true,
        'fully_qualified_strict_types' => true,
        'operator_linebreak' => ['position' => 'beginning'],
    ])
    ->setFinder($finder)
;
