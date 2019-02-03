<?php

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        'concat_space' => [
            'spacing' => 'one',
        ],
        'ordered_imports' => true,
        'phpdoc_order' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'array_syntax' => [
            'syntax' => 'long',
        ],
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->exclude([
                'vendor',
            ])
            ->in(__DIR__)
    )
;
