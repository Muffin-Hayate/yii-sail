<?php

namespace MuffinHayate\Yii3\Sail\Command;

use MuffinHayate\Yii3\Sail\Command\Concerns\InteractsWithDockerComposeServices;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[AsCommand(name: 'sail:add')]
class AddCommand extends Command
{
    use InteractsWithDockerComposeServices;

    protected function configure(): void
    {
        $this->setDescription("Add a service to an existing Sail installation")
            ->addOption('services', 's', InputOption::VALUE_REQUIRED, 'The services that should be added');

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
        if ($input->getOption('services')) {
            $services = $input->getOption('services') == 'none' ? [] : explode(',', $input->getOption('services'));
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

        $this->prepareInstallation($services);

        info('Additional Sail services installed successfully.');

        return ExitCode::OK;
    }
}
