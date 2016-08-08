<?php

namespace AlanCole\N98;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class ConfigDiff extends AbstractMagentoCommand
{

    /**
     * Holds our pdo connection
     *
     * @var PDO
    */
    protected $pdo;

    /**
     * Holds our remote config array.
     *
     * @var array
    */
    protected $config = [];

    /**
     * Display table headers
     *
     * @var array
    */
    protected $table_headers = ["Scope", "Scope ID", "Path", "Remote Value", "Local Value"];

    /**
     * Holds all changed rows
     *
     * @var array
    */
    protected $changed_rows = [];

    /**
     * Holds all missing rows
     *
     * @var array
    */
    protected $missing_rows = [];

    /**
     * Sets up our function
     *
     * @return void
    */
    protected function configure()
    {
        $this->setName('config:diff')
            ->setDescription('Will diff the config tables of two connections')
            ->addOption('dsn', null, InputOption::VALUE_REQUIRED, 'The DSN string for the second database to compare too.')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Database username.', false)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Database password.', '')
            ->addOption('prefix', null, InputOption::VALUE_OPTIONAL, 'Does the other db use a table prefix? if so what is it.', '')
            ->addOption('show-missing', null, InputOption::VALUE_OPTIONAL, 'Only show missing values', false)
            ->addOption('show-different', null, InputOption::VALUE_OPTIONAL, 'Show different values', true)
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Limit to given scope', false)
            ->addOption('scope-id', null, InputOption::VALUE_OPTIONAL, 'Scope id value', '');
    }

    /**
    * Main command method.
    *
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {

            /**
             * Parase options
            */
            $dsn = $input->getOption('dsn');
            $username = $input->getOption('username');
            $password = $input->getOption('password');
            $prefix = $input->getOption('prefix') ? $input->getOption('prefix') . "_" : "";
            $c_scope = $input->getOption('scope');
            $c_scope_id = $input->getOption('scope-id');

            $show_missing = $input->getOption('show-missing') == "0" || $input->getOption('show-missing') == "1"  ? $input->getOption('show-missing') : false;
            $show_different = $input->getOption('show-different') == "0" || $input->getOption('show-different') == "1" ? $input->getOption('show-different') : true;
            /**
             * End options
            */

            $this->pdo = new \PDO($dsn, $username, $password);

            if (!$this->pdo) {
                $output->writeLn("<error>Could not connect to remote database.</error>");
                return false;
            }

            if ($scope) {
                $where = "WHERE scope = $c_scope AND scope_id = $c_scope_id";
            }

            $this->fetchRemoteConfigRows();
            $local = $this->getLocalConfigRows($where);

            foreach ($local as $row) {

                /**
                 * Missing = a row in local thats not in remote.
                */
                if ($show_missing && !$this->exists($row)) {
                    $this->addMissing($row);
                }

                /**
                 * Changed = a value in local thats different in remote.
                */
                if($show_different && $this->exists($row) && $this->isChanged($row)) {
                    $this->addChangedValue($row);
                }
            }

            if ($show_different && count($this->changed_rows) > 0) {
                $this->showChangedTable($output);
            }

            if ($show_missing && count($this->missing_rows) > 0) {
                $this->showMissingTable($output);
            }
        }
    }

    /**
     * Receives a row a checks if its value is changed.
     *
     * @param  array  $row
     * @return boolean
    */
    protected function isChanged($row)
    {
        return $this->config[$row['scope']][$row['scope_id']][$row['path']]['remote_value'] !== $row['value'];
    }

    /**
     * Exists
     *
     * @param  array  $row
     * @return boolean
    */
    protected function exists($row)
    {
        return isset($this->config[$row['scope']][$row['scope_id']][$row['path']]['remote_value']);
    }

    /**
     * Parse rows to workable array
     *
     * @param array $rows
     * @return void
    */
    protected function parseRowsToConfigArray($rows = [])
    {
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $this->config[$row['scope']][$row['scope_id']][$row['path']]['remote_value'] = $row['value'];
            }
        }
    }

    /**
     * Get local config array
     *
     * @return void
    */
    protected function getLocalConfigRows($where = "")
    {
        $res = \Mage::getSingleton('core/resource')->getConnection('core_read');
        $prefix = (string)\Mage::getConfig()->getTablePrefix() ? (string)\Mage::getConfig()->getTablePrefix() . "_" : "";
        return $res->fetchAll("SELECT * FROM " . $prefix."core_config_data $where");
    }

    /**
     * fetch remote config rows
     *
     * @param string $where
     * @return void
    */
    protected function fetchRemoteConfigRows($prefix, $where = "")
    {
        $remote = $this->pdo->query("SELECT * FROM " . $prefix . "core_config_data $where")->fetchAll();
        $this->parseRowsToConfigArray($remote);
    }

    /**
     * print our changed table
     *
     * @param $output
     * @return void
    */
    protected function showChangedTable($output)
    {
        $output->writeLn("<info>All values in local that are different from remote. " . count($this->changed_rows) . " Found.</info>");
        $table = new Table($output);
        $table->setHeaders($this->table_headers);
        $table->setRows($this->changed_rows);
        $table->render();
    }

    /**
     * print our missing table
     *
     * @param $output
     * @return void
    */
    protected function showMissingTable($output)
    {
        $output->writeLn("<info>All values in local not found in remote. " . count($this->missing_rows) . " Found.</info>");
        $table = new Table($output);
        $table->setHeaders($this->table_headers);
        $table->setRows($this->missing_rows);
        $table->render();
    }

    /**
     * add a changed row
     *
     * @param array $row
     * @return void
    */
    protected function addChangedValue($row = [])
    {
        $this->changed_rows[] = [
            'scope' => $row['scope'],
            'scope_id' => $row['scope_id'],
            'path' => $row['path'],
            'remote_value' => $this->getRemoteValue($row), //$config[$scope][$scope_id][$path]['remote_value'],
            'local_value' => $row['value']
        ];
    }

    /**
     * add a missing row
     *
     * @param array $row
     * @return void
    */
    protected function addMissing($row = [])
    {
        $this->missing_rows[] = [
            'scope' => $row['scope'],
            'scope_id' => $row['scope_id'],
            'path' => $row['path'],
            'remote_value' => "", // its missin obvs.
            'local_value' => $row['value']
        ];
    }

    /**
     * get remote value
     *
     * @param array $row
     * @return mixed
    */
    protected function getRemoteValue($row = [])
    {
        if (!$this->exists($row)) {
            return false;
        }

        return $this->config[$row['scope']][$row['scope_id']][$row['path']]['remote_value'];
    }
}
