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
        ->append([__FILE__])
);

$config
    ->setCacheFile(__DIR__ . '/build/.php-cs-fixer.cache')
    ->setRules([
        '@Symfony'                   => true,
        'concat_space'               => ['spacing' => 'one'],
        'no_superfluous_phpdoc_tags' => [
            'remove_inheritdoc' => true,
        ],
        'yoda_style'       => false,
        'phpdoc_summary'   => false,
        'phpdoc_line_span' => [
            'class'    => 'single',
            'const'    => 'single',
            'method'   => 'single',
            'property' => 'single',
        ],
        'global_namespace_import'     => ['import_classes' => true],
        'phpdoc_to_comment'           => false,
        'class_attributes_separation' => ['elements' => ['property' => 'none']],
        'phpdoc_tag_type'             => false,
        'binary_operator_spaces'      => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => 'single_space',
            ],
        ],
        'increment_style'            => false,
        'native_function_invocation' => [
            'exclude' => ['sprintf'],
        ],
        'ordered_class_elements'               => ['sort_algorithm' => 'alpha'],
        'php_unit_data_provider_return_type'   => true,
        'php_unit_method_casing'               => ['case' => 'snake_case'],
        'php_unit_set_up_tear_down_visibility' => true,
        'php_unit_attributes'                  => true,

        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
        ],
    ])
;

return $config;
