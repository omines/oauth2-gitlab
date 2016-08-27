<?php

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader(<<<EOF
Gitlab OAuth2 Provider
(c) Omines Internetbureau B.V. - www.omines.nl

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF
);

return Symfony\CS\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers([
        '-phpdoc_params',
        '-phpdoc_separation',
        '-phpdoc_var_without_name',
        '-return',
        'concat_with_spaces',
        'header_comment',
        'newline_after_open_tag',
        'short_array_syntax',
        'strict_param',
    ])
    ->finder(
        Symfony\CS\Finder::create()
            ->files()
            ->name('*.php')
            ->in(__DIR__.'/src')
            ->in(__DIR__.'/test')
    )
    ;