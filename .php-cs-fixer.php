<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->notPath('vendor')
    ->in(__DIR__)
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

$rules = [
    '@Symfony' => true,
    'binary_operator_spaces' => [
        'operators' => [
            '=>' => 'align',
            '=' => 'align',
        ],
    ],
];

return (new Config())
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(true);