<?php

namespace SoftHeroes\MigratableSeeder\Repository;

use DB;

trait DisableForeignKeysTrait
{
    private $commands = [
        'mysql' => [
            'enable'  => 'SET FOREIGN_KEY_CHECKS=1;',
            'disable' => 'SET FOREIGN_KEY_CHECKS=0;',
        ],
        'sqlite' => [
            'enable'  => 'PRAGMA foreign_keys = ON;',
            'disable' => 'PRAGMA foreign_keys = OFF;',
        ],
    ];

    /**
     * Disable foreign key checks for current db driver.
     */
    protected function disableForeignKeys()
    {
        DB::statement($this->getDisableStatement());
    }

    /**
     * Return current driver disable command.
     *
     * @return mixed
     */
    private function getDisableStatement()
    {
        return $this->getDriverCommands()['disable'];
    }

    /**
     * Returns command array for current db driver.
     *
     * @return mixed
     */
    private function getDriverCommands()
    {
        return $this->commands[DB::getDriverName()];
    }

    /**
     * Enable foreign key checks for current db driver.
     */
    protected function enableForeignKeys()
    {
        DB::statement($this->getEnableStatement());
    }

    /**
     * Return current driver enable command.
     *
     * @return mixed
     */
    private function getEnableStatement()
    {
        return $this->getDriverCommands()['enable'];
    }
}
