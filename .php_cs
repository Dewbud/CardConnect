<?php

$finder = PhpCsFixer\Finder::create()
    ->notPath('vendor')
    ->in(__DIR__)
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$rules = [
    '@Symfony' => true,
    'binary_operator_spaces' => [
        'align_double_arrow' => true,
        'align_equals' => true,
    ],
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(true);
