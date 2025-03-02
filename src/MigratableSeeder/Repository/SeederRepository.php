<?php

namespace SoftHeroes\MigratableSeeder\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SeederRepository implements SeederRepositoryInterface
{
    /**
     * The name of the environment to run in.
     *
     * @var string
     */
    public $environment;

    /**
     * The database connection resolver instance.
     *
     * @var ConnectionResolverInterface
     */
    protected $connectionResolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;


    /**
     * The name of the database name to use.
     *
     * @var string
     */
    public $database_name = null;

    /**
     * Create a new database migration repository instance.
     *
     * @param ConnectionResolverInterface $resolver
     * @param string                      $table
     */
    public function __construct(ConnectionResolverInterface $resolver, string $table, ?string $database_name = null)
    {
        $this->connectionResolver = $resolver;
        $this->database_name = $database_name;
        $this->table = $table;

        if (!empty($this->database_name)) {
            $this->setDatabaseName($this->database_name);
        }
    }

    /**
     * Set the database name and reconnect to the database
     *
     * @param string $databaseName The new database name to connect to
     */
    public function setDatabaseName(string $databaseName)
    {
        // Get the connection name
        $connectionName = $this->connectionResolver->getDefaultConnection();
        
        // Disconnect the current connection
        $this->connectionResolver->Connection()->disconnect();
        
        // Update the database configuration
        Config::set("database.connections.$connectionName.database", $databaseName);
        
        // Purge the existing connection from the connection manager
        DB::purge($connectionName);
        
        // Reconnect and store the new connection
        $this->connection = DB::reconnect($connectionName);
    }

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan(): array
    {
        return $this->table()
            ->whereIn('env', [$this->getEnvironment(), 'all'])
            ->pluck('seed')
            ->toArray();
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return Builder
     */
    protected function table(): Builder
    {
        $connection = $this->getConnection();
        $connection->disconnect();
        $connection->reconnect();

        return $connection->table($this->table);
    }

    /**
     * Resolve the database connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        $connection = $this->connectionResolver->connection($this->connection);
        if (!empty($this->database_name)) {
            $connection->setDatabaseName($this->database_name);
            $connection->disconnect();
            $connection->reconnect();
        }
        return $connection;
    }

    /**
     * Determines whether an environment has been set.
     *
     * @return bool
     */
    public function hasEnvironment(): bool
    {
        return !empty($this->getEnvironment());
    }

    /**
     * Gets the environment the seeds are ran against.
     *
     * @return string|null
     */
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnvironment(string $env): void
    {
        $this->environment = $env;
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast(): array
    {
        return $this->table()
            ->whereIn('env', [$this->getEnvironment(), 'all'])
            ->where('batch', $this->getLastBatchNumber())
            ->orderBy('seed', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    protected function getLastBatchNumber(): int
    {
        $max = $this->table()
            ->whereIn('env', [$this->getEnvironment(), 'all'])
            ->max('batch');

        return ($max) ?: 0;
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int    $batch
     *
     * @return void
     */
    public function log($file, $batch): void
    {
        $this->table()->insert([
            'seed' => $file,
            'env' => $this->getEnvironment(),
            'batch' => $batch,
        ]);
    }

    /**
     * Remove a migration from the log.
     *
     * @param $seeder
     */
    public function delete($seeder): void
    {
        $this->table()
            ->whereIn('env', [$this->getEnvironment(), 'all'])
            ->where('seed', $seeder->seed)
            ->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository(): void
    {
        $connection = $this->getConnection();

        $schema = $connection->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            // The migrations table is responsible for keeping track of which of the
            // migrations have actually run for the application. We'll create the
            // table to hold the migration file's path as well as the batch ID.
            $table->string('seed');
            $table->string('env');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists(): bool
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Set the information source to gather data.
     *
     * @param string $name
     */
    public function setSource($name): void
    {
        $this->connection = $name;
    }

    /**
     * Get list of migrations.
     *
     * @param int $steps
     *
     * @return array
     */
    public function getMigrations($steps): array
    {
        return $this->table()->get()->toArray();
    }

    /**
     * Get the completed migrations with their batch numbers.
     *
     * @return array
     */
    public function getMigrationBatches()
    {
        return $this->table()
            ->orderBy('batch', 'asc')
            ->orderBy('migration', 'asc')
            ->pluck('batch', 'migration')->all();
    }

    /**
     * Delete the migration repository data store.
     *
     * @return void
     */
    public function deleteRepository()
    {
        //   
    }

    /**
     * Get the list of the migrations by batch number.
     *
     * @param  int  $batchNumber
     * @return array
     */
    public function getMigrationsByBatch($batch)
    {
        return $this->table()
            ->where('batch', $batch)
            ->get()
            ->all();
    }
}
