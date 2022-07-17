<?php

declare(strict_types=1);
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->append([__FILE__])
;

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'yoda_style' => false,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author'],
        ],
        'no_useless_return' => true,
        'phpdoc_to_comment' => false,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'phpdoc_var_annotation_correct_order' => true,
        'void_return' => true,
    ])
;
