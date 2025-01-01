<?php

declare(strict_types=1);

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'sail/install' => \MuffinHayate\Yii3\Sail\Command\InstallCommand::class,
            'sail/add' => \MuffinHayate\Yii3\Sail\Command\AddCommand::class,
        ],
    ],
];
