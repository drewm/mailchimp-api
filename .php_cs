<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => [
            'syntax' => 'short'
        ],
        'combine_consecutive_unsets' => true,
        'binary_operator_spaces' => [
            'align_equals' => false,
            'align_double_arrow' => true
        ],
        'combine_consecutive_unsets' => true,
        'concat_space' => [
            'spacing' => 'one'
        ],
        'declare_strict_types' => false,
        'dir_constant' => true,
        'ereg_to_preg' => true,
        'error_suppression' => true,
        'function_to_constant' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => [
                'expectedException',
                'expectedExceptionMessage',
                'expectedExceptionMessageRegExp'
            ]
        ],
        'is_null' => true,
        'linebreak_after_opening_tag' => true,
        'mb_str_functions' => true,
        'modernize_types_casting' => true,
        'native_constant_invocation' => [
            'fix_built_in' => false,
            'include' => [
                'DIRECTORY_SEPARATOR',
                'PHP_SAPI',
                'PHP_VERSION_ID',
            ],
        ],
        'native_function_invocation' => true,
        'no_alias_functions' => true,
        'no_homoglyph_names' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'no_php4_constructor' => true,
        'no_unreachable_default_argument_value' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'php_unit_construct' => true,
        'php_unit_strict' => true,
        'phpdoc_order' => true,
        'psr4' => true,
        'self_accessor' => true,
        'semicolon_after_instruction' => true,
        'set_type_to_cast' => true,
        'simplified_null_return' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'trailing_comma_in_multiline_array' => true
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php_cs.cache');
