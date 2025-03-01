<?php

namespace SoftHeroes\MigratableSeeder\Migration;

use Illuminate\Database\Seeder;

abstract class MigratableSeeder extends Seeder
{
    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * Get the migration connection name.
     *
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Run the database seeder.
     */
    abstract public function run(): void;

    /**
     * Reverses the database seeder.
     */
    abstract public function down(): void;
}
