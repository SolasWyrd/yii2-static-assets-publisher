<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new Config())
    ->setRules([
        '@PER-CS2x0' => true,

        'blank_line_before_statement' => [
            'statements' => [
                'return',
                'throw',
                'continue',
                'if',
                'foreach',
                'for',
                'while',
                'do',
                'switch',
                'try',
                'yield',
                'yield_from',
                'break',
            ],
        ],

        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'return',
                'use',
                'curly_brace_block',
                'parenthesis_brace_block',
                'switch',
                'case',
                'default',
            ],
        ],

        'single_import_per_statement' => true,
        'single_line_after_imports' => true,
        'no_unused_imports' => true,
        'no_closing_tag' => true,
        'single_blank_line_at_eof' => true,
        'no_trailing_whitespace' => true,
        'no_empty_statement' => true,
        'no_whitespace_in_blank_line' => true,
        'phpdoc_trim' => true,
        'phpdoc_indent' => true,
        'phpdoc_scalar' => true,
        'cast_spaces' => ['space' => 'none'],
        'concat_space' => ['spacing' => 'one'],
        'type_declaration_spaces' => true,
        'types_spaces' => true,

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],

        'no_blank_lines_after_phpdoc' => true,
        'single_quote' => true,

        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],

        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
            ],
        ],

        'return_type_declaration' => [
            'space_before' => 'none',
        ],

        'visibility_required' => [
            'elements' => [
                'method',
                'property',
                'const',
            ],
        ],

        'blank_line_after_opening_tag' => true,
        'indentation_type' => true,
        'line_ending' => true,
        'unary_operator_spaces' => true,
        'single_space_around_construct' => true,
        'class_definition' => true,
        'no_blank_lines_after_class_opening' => true,
        'function_declaration' => true,

        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],

        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => null,
            ],
        ],

        'array_syntax' => [
            'syntax' => 'short',
        ],
    ])
    ->setUsingCache(false)
    ->setFinder($finder);