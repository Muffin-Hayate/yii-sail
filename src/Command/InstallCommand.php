<?php

namespace MuffinHayate\Yii3\Sail\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;

#[AsCommand(name: 'sail:install')]
class InstallCommand extends Command
{
    use Concerns\InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this->setDescription('Install Yii Sail\'s default Docker Compose file')
            ->addOption('with', 'w', InputOption::VALUE_REQUIRED, 'The services that should be included in the installation')
            ->addOption('devcontainer', 'd', InputOption::VALUE_NONE, 'Create a .devcontainer configuration directory')
            ->addOption('php', 'p', InputOption::VALUE_REQUIRED, 'The PHP version that should be used');

        parent::configure();
    }

    /**
     * Execute the console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('with')) {
            $services = $input->getOption('with') == 'none' ? [] : explode(',', $input->getOption('with'));
        } elseif ($input->getOption('no-interaction')) {
            $services = $this->defaultServices;
        } else {
            $services = $this->gatherServicesInteractively();
        }

        if ($invalidServices = array_diff($services, $this->services)) {
            error('Invalid services ['.implode(',', $invalidServices).'].');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->buildDockerCompose($services, $input);
        $this->replaceEnvVariables($services);
        $this->configurePhpUnit();

        if ($input->getOption('devcontainer')) {
            $this->installDevContainer();
        }

        $this->prepareInstallation($services);

        info('Sail scaffolding installed successfully. You may run your Docker containers using Sail\'s "up" command.');

        note('➜ ./vendor/bin/sail up');

        if (in_array('mysql', $services) ||
            in_array('mariadb', $services) ||
            in_array('pgsql', $services)) {
            info('A database service was installed. Run "yii migrate:up" to prepare your database:');

            note('➜ ./vendor/bin/sail yii migrate:up');
        }

        return ExitCode::OK;
    }
}
