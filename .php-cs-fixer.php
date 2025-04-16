<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = new Config();
$config->setParallelConfig(ParallelConfigFactory::detect());
$config->setRiskyAllowed(true);
$config->setFinder(
    (new Finder())
        ->ignoreDotFiles(true)
        ->ignoreVCSIgnored(true)
        ->in(__DIR__ . '/src')
        ->in(__DIR__ . '/tests')
);

$config
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@Symfony'                   => true,
        'concat_space'               => ['spacing' => 'one'],
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => true,
        ],
        'yoda_style'       => false,
        'phpdoc_summary'   => false,
        'phpdoc_line_span' => [
            'method'   => 'single',
            'property' => 'single',
        ],
        'global_namespace_import'     => ['import_classes' => true],
        'phpdoc_to_comment'           => false,
        'class_attributes_separation' => [
            'elements' => [
                'method'   => 'one',
                'property' => 'none',
            ],
        ],
        'single_line_comment_style' => true,
        'phpdoc_tag_type'           => false,
        'binary_operator_spaces'    => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => 'single_space',
            ],
        ],
        'increment_style'            => false,
        'native_function_invocation' => [
            'exclude' => ['sprintf'],
        ],
        'no_leading_import_slash' => true,
        'self_static_accessor'    => true,
        'ordered_class_elements'  => [
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
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'php_unit_data_provider_return_type'     => true,
        'php_unit_method_casing'                 => ['case' => 'snake_case'],
        'php_unit_set_up_tear_down_visibility'   => true,
        'php_unit_attributes'                    => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ])
;

return $config;
