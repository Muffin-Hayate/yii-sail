<?php

namespace MuffinHayate\Yii3\Sail\Command\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Yiisoft\Aliases\Aliases;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

trait InteractsWithDockerComposeServices
{

    private Aliases $aliases;

    /**
     * The available services that may be installed.
     *
     * @var array<string>
     */
    protected array $services = [
        'mysql',
        'pgsql',
        'mariadb',
        'mongodb',
        'redis',
        'valkey',
        'memcached',
        'meilisearch',
        'typesense',
        'minio',
        'mailpit',
        'selenium',
        'soketi',
    ];

    /**
     * The default services used when the user chooses non-interactive mode.
     *
     * @var string[]
     */
    protected array $defaultServices = ['mysql', 'redis', 'selenium', 'mailpit'];

    public function __construct(Aliases $aliases)
    {
        $this->aliases = $aliases;
        parent::__construct();
    }

    /**
     * Gather the desired Sail services using an interactive prompt.
     *
     * @return array
     */
    protected function gatherServicesInteractively(): array
    {
        if (function_exists('\Laravel\Prompts\multiselect')) {
            return \Laravel\Prompts\multiselect(
                label: 'Which services would you like to install?',
                options: $this->services,
                default: ['mysql'],
            );
        }

        return $this->choice('Which services would you like to install?', $this->services, 0, null, true);
    }

    /**
     * Build the Docker Compose file.
     *
     * @param array $services
     * @param InputInterface $input
     * @return void
     */
    protected function buildDockerCompose(array $services, InputInterface $input): void
    {
        $composePath = $this->aliases->get('@root/docker-compose.yml');

        $compose = file_exists($composePath)
            ? Yaml::parseFile($composePath)
            : Yaml::parse(file_get_contents(__DIR__ . '/../../../stubs/docker-compose.stub'));

        // Prepare the installation of the "mariadb-client" package if the MariaDB service is used...
        if (in_array('mariadb', $services)) {
            $compose['services']['yii.test']['build']['args']['MYSQL_CLIENT'] = 'mariadb-client';
        }

        // Adds the new services as dependencies of the yii.test service...
        if (! array_key_exists('yii.test', $compose['services'])) {
            warning('Couldn\'t find the yii.test service. Make sure you add ['.implode(',', $services).'] to the depends_on config.');
        } else {
            $compose['services']['yii.test']['depends_on'] = collect($compose['services']['yii.test']['depends_on'] ?? [])
                ->merge($services)
                ->unique()
                ->values()
                ->all();
        }

        // Add the services to the docker-compose.yml...
        collect($services)
            ->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['services'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['services'][$service] = Yaml::parseFile(__DIR__ . "/../../../stubs/{$service}.stub")[$service];
            });

        // Merge volumes...
        collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'mongodb', 'redis', 'valkey', 'meilisearch', 'typesense', 'minio']);
            })->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['volumes'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['volumes']["sail-{$service}"] = ['driver' => 'local'];
            });

        // If the list of volumes is empty, we can remove it...
        if (empty($compose['volumes'])) {
            unset($compose['volumes']);
        }

        $yaml = Yaml::dump($compose, Yaml::DUMP_OBJECT_AS_MAP);

        $yaml = str_replace('{{PHP_VERSION}}', $input->getOption('php') ? $input->getOption('php') : '8.4', $yaml);

        file_put_contents($this->aliases->get('@root/docker-compose.yml'), $yaml);
    }

    /**
     * Replace the Host environment variables in the app's .env file.
     *
     * @param  array  $services
     * @return void
     */
    protected function replaceEnvVariables(array $services): void
    {
        $environment = file_get_contents($this->aliases->get('@root/.env'));

        if (in_array('mysql', $services) ||
            in_array('mariadb', $services) ||
            in_array('pgsql', $services)) {
            $defaults = [
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'yii',
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => '',
                'DB_CONNECTION' => '',
            ];

            foreach ($defaults as $key => $default) {
                if (!str_contains($environment, $key)) {
                    $environment .= "\n" . $key . "=" . $default;
                }
            }
        }

        if (in_array('mysql', $services)) {
            $environment = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mysql', $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mysql", $environment);
        }elseif (in_array('pgsql', $services)) {
            $environment = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=pgsql', $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=pgsql", $environment);
            $environment = str_replace('DB_PORT=3306', "DB_PORT=5432", $environment);
        } elseif (in_array('mariadb', $services)) {
            $environment = preg_replace('/DB_CONNECTION=.*/', 'DB_CONNECTION=mariadb', $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mariadb", $environment);
        }

        $environment = str_replace('DB_USERNAME=root', "DB_USERNAME=sail", $environment);
        $environment = preg_replace("/DB_PASSWORD=(.*)/", "DB_PASSWORD=password", $environment);

        if (in_array('memcached', $services)) {
            if (!str_contains($environment, 'MEMCACHED_HOST')) {
                $environment .= "\nMEMCACHED_HOST=memcached";
            } else {
                $environment = preg_replace('/MEMCACHED_HOST=.*/', 'MEMCACHED_HOST=memcached', $environment);
            }
        }

        if (in_array('redis', $services)) {
            if (!str_contains($environment, 'REDIS_HOST')) {
                $environment .= "\n\nREDIS_HOST=redis";
            }
        }

        if (in_array('valkey',$services)){
            if (!str_contains($environment, 'REDIS_HOST')) {
                $environment .= "\n\nREDIS_HOST=valkey";
            }
        }

        if (in_array('mongodb', $services)) {
            $environment .= "\n\nMONGODB_URI=mongodb://mongodb:27017";
            $environment .= "\nMONGODB_DATABASE=yii";
        }

        if (in_array('meilisearch', $services)) {
//            $environment .= "\nSCOUT_DRIVER=meilisearch";
            $environment .= "\n\nMEILISEARCH_HOST=http://meilisearch:7700\n";
            $environment .= "\nMEILISEARCH_NO_ANALYTICS=false\n";
        }

        if (in_array('typesense', $services)) {
//            $environment .= "\nSCOUT_DRIVER=typesense";
            $environment .= "\n\nTYPESENSE_HOST=typesense";
            $environment .= "\nTYPESENSE_PORT=8108";
            $environment .= "\nTYPESENSE_PROTOCOL=http";
            $environment .= "\nTYPESENSE_API_KEY=xyz\n";
        }

        if (in_array('soketi', $services)) {
            $environment .= "\n\nBROADCAST_DRIVER=pusher";
            $environment .= "\nPUSHER_HOST=pusher";
            $environment .= "\nPUSHER_PORT=6001";
            $environment .= "\nPUSHER_SCHEME=http";
            $environment .= "\nPUSHER_APP_KEY=token";
            $environment .= "\nPUSHER_APP_SECRET=app-secret";
            $environment .= "\nPUSHER_APP_ID=app-id";
        }

        if (in_array('mailpit', $services)) {
            warning("Don't forget to setup your yii/mailer configuration: https://github.com/yiisoft/mailer-symfony/blob/master/config/params.php");

            $environment .= "\n\nMAIL_MAILER=smtp";
            $environment .= "\nMAIL_HOST=mailpit";
            $environment .= "\nMAIL_PORT=1025";
        }

        file_put_contents($this->aliases->get('@root/.env'), $environment);
    }

    /**
     * Configure PHPUnit to use the dedicated testing database.
     *
     * @return void
     */
    protected function configurePhpUnit(): void
    {
        if (! file_exists($path = $this->aliases->get('@root/phpunit.xml'))) {
            $path = $this->aliases->get('@root/phpunit.xml.dist');

            if (! file_exists($path)) {
                return;
            }
        }

        $phpunit = file_get_contents($path);

        $phpunit = preg_replace('/^.*DB_CONNECTION.*\n/m', '', $phpunit);
        $phpunit = str_replace('<!-- <env name="DB_DATABASE" value=":memory:"/> -->', '<env name="DB_DATABASE" value="testing"/>', $phpunit);

        file_put_contents($this->aliases->get('@root/phpunit.xml'), $phpunit);
    }

    /**
     * Install the devcontainer.json configuration file.
     *
     * @return void
     */
    protected function installDevContainer(): void
    {
        if (! is_dir($this->aliases->get('@root/.devcontainer'))) {
            mkdir($this->aliases->get('@root/.devcontainer'), 0755, true);
        }

        file_put_contents(
            $this->aliases->get('@root/.devcontainer/devcontainer.json'),
            file_get_contents(__DIR__.'/../../../stubs/devcontainer.stub')
        );

        $environment = file_get_contents($this->aliases->get('@root/.env'));

        $environment .= "\nWWWGROUP=1000";
        $environment .= "\nWWWUSER=1000\n";

        file_put_contents($this->aliases->get('@root/.env'), $environment);
    }

    /**
     * Prepare the installation by pulling and building any necessary images.
     *
     * @param array $services
     * @return void
     */
    protected function prepareInstallation(array $services): void
    {
        // Ensure docker is installed...
        if ($this->runCommands(['docker info > /dev/null 2>&1']) !== 0) {
            return;
        }

        if (count($services) > 0) {
            $this->runCommands([
                './vendor/bin/sail pull '.implode(' ', $services),
            ]);
        }

        $this->runCommands([
            './vendor/bin/sail build',
        ]);
    }

    /**
     * Run the given commands.
     *
     * @param array $commands
     * @return int
     */
    protected function runCommands(array $commands): int
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (\RuntimeException $e) {
                warning($e->getMessage());
            }
        }

        return $process->run(function ($type, $line) {
            info($line);
        });
    }
}
