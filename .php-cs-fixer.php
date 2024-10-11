<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['vendor', 'var', 'web'])
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'heredoc_to_nowdoc' => true,
        'no_extra_blank_lines' => ['tokens' => ['break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block']],
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'php_unit_strict' => false,
        'phpdoc_order' => true,
        'phpdoc_to_param_type' => true,
        // 'psr4' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'concat_space' => ['spacing' => 'one'],
        // Pulled in by @Symfony:risky but we still support PHP 7.4
        'modernize_strpos' => false,
        // Pulled in by @Symfony, we cannot add property types until we bump PHP to ≥ 7.4
        'no_null_property_initialization' => false,
    ])
    ->setFinder($finder)
;
