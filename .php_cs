<?php

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'concat_space' => ['spacing' => 'one'],
        'array_syntax' => false,
        'simplified_null_return' => false,
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_to_comment' => false,
        'cast_spaces' => false,
        'blank_line_after_opening_tag' => false,
        'phpdoc_no_alias_tag' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__)
            ->exclude([
                'vendor',
                'bin/.ci',
                'bin/.travis',
                'doc',
                'app/cache',
                'var/cache',
                'ezpublish_legacy',
                'var/cache',
                'node_modules'
            ])
            ->notPath('app/autoload.php')
            ->notPath('app/check.php')
            ->notPath('app/SymfonyRequirements.php')
            ->notPath('web/index_rest.php')
            ->notPath('web/index_cluster.php')
            ->files()->name('*.php')
    )
;
