<?php

namespace SoftHeroes\MigratableSeeder\Command;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;
use SoftHeroes\MigratableSeeder\Migration\SeederMigratorInterface;

abstract class AbstractSeedMigratorCommand extends Command
{
    /* Constant for all environments */
    const ALL_ENVIRONMENTS = 'all';

    /** @var string */
    protected $environment;

    /** @var SeederMigratorInterface */
    protected $migrator;

    /** @var array */
    protected $migrationOptions = [];

    /** @var array */
    protected $migrationPaths = [];

    /**
     * Constructor.
     *
     * @param SeederMigratorInterface $migrator
     */
    public function __construct(SeederMigratorInterface $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Prepares the migrator for usage.
     */
    protected function prepareMigrator(array $dir = []): void
    {
        $this->connectToRepository();
        $this->resolveEnvironment();
        $this->resolveMigrationPaths($dir);
        $this->resolveMigrationOptions();

        // Ensure output is set on the migrator
        if (method_exists($this->migrator, 'setOutput')) {
            $this->migrator->setOutput($this->getOutput());
        }
    }

    /**
     * Prepares the repository for usage.
     */
    protected function connectToRepository(): void
    {
        $database = $this->input->getOption('database');
        $database_name = $this->input->getOption('database-name');

         if(!empty($database_name)){
            $this->getMigrator()->getRepository()->setDatabaseName($database_name);
        }
        $this->getMigrator()->setConnection($database);

        if (!$this->getMigrator()->repositoryExists()) {
            $this->call('seed:install', [
                '--database' => $database,
                '--database-name' => $database_name
            ]);
        }
    }

    /**
     * Gets the migrator instance.
     *
     * @return SeederMigratorInterface
     */
    public function getMigrator(): SeederMigratorInterface
    {
        return $this->migrator;
    }

    /**
     * Sets the migrator instance.
     *
     * @param SeederMigratorInterface $migrator
     */
    public function setMigrator(SeederMigratorInterface $migrator)
    {
        $this->migrator = $migrator;
    }

    /**
     * Sets up the environment for the migrator.
     */
    protected function resolveEnvironment(): void
    {
        $env = $this->input->getOption('env') ?: config('seeders.env');

        $this->setEnvironment($env);

        $this->getMigrator()->setEnvironment($this->getEnvironment());
    }

    /**
     * Gets the environment.
     *
     * @return string
     */
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    /**
     * Sets the environment.
     *
     * @param string $env
     */
    public function setEnvironment(string $env): void
    {
        $this->environment = $env;
    }

    /**
     * Resolves the paths for the migration files to run the migrator against.
     */
    protected function resolveMigrationPaths(array $dir = []): void
    {
        $pathsFromConfig = count($dir) > 0 ? $dir : config('seeders.dir');

        foreach ($pathsFromConfig as $eachPath) {

            foreach ((new Finder)->in($eachPath) as $path) {

                // Add the 'all' environment path to migration paths
                $allEnvPath = $path->getPath() . DIRECTORY_SEPARATOR . self::ALL_ENVIRONMENTS;
                $this->addMigrationPath($allEnvPath);

                // Add the targeted environment path to migration paths
                $pathWithEnv = $path->getPath() . DIRECTORY_SEPARATOR . $this->getEnvironment();
                $this->addMigrationPath($pathWithEnv);
            }
        }
    }

    /**
     * Appends a migration path to the list of paths.
     *
     * @param string $path
     */
    public function addMigrationPath(string $path): void
    {
        $this->migrationPaths[] = $path;

        $this->migrationPaths = array_unique($this->migrationPaths);
    }

    /**
     * Resolves the options for the migrator.
     */
    protected function resolveMigrationOptions(): void
    {
        $pretend = $this->input->getOption('pretend');

        if ($pretend) {
            $this->addMigrationOption('pretend', $pretend);
        }
    }

    /**
     * Adds an option to the list of migration options.
     *
     * @param string $key
     * @param string $value
     */
    public function addMigrationOption(string $key, string $value): void
    {
        $this->migrationOptions[$key] = $value;
    }

    /**
     * Execute the console command.
     */
    abstract public function handle(): void;

    /**
     * Gets the paths for the migration files to run the migrator against.
     *
     * @return array
     */
    public function getMigrationPaths(): array
    {
        return $this->migrationPaths;
    }

    /**
     * Sets the paths for the migration files to run the migrator against.
     *
     * @param array $paths
     */
    public function setMigrationPaths(array $paths): void
    {
        $this->migrationPaths = $paths;
    }

    /**
     * Gets the options for the migrator.
     *
     * @return array
     */
    public function getMigrationOptions(): array
    {
        return $this->migrationOptions;
    }

    /**
     * Sets the options for the migrator.
     *
     * @param array $migrationOptions
     */
    public function setMigrationOptions(array $migrationOptions)
    {
        $this->migrationOptions = $migrationOptions;
    }
}
