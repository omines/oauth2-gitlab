<?php

$header = <<<EOF
Gitlab OAuth2 Provider
(c) Omines Internetbureau B.V. - https://omines.nl/

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/test')
;

$config = new PhpCsFixer\Config();
return $config
    // ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'header_comment' => ['header' => $header, 'location' => 'after_open'],

    //    'mb_str_functions' => true,
        'ordered_imports' => true,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_var_without_name' => false,
    ])
    ->setFinder($finder)
    ;
