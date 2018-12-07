<?php

declare(strict_types=1);

namespace App\Config;

use Symfony\Component\Yaml\Yaml;

class Configuration
{
    private $data = [];
    private $configFilename;
    private $dataFilename;
    private $modifiedAt;

    public function __construct()
    {
        $this->configFilename = dirname(dirname(__DIR__)) . '/config/config.yml';
        $this->dataFilename = dirname(dirname(__DIR__)) . '/data/table.yml';
        $this->initialize();
    }

    private function initialize()
    {
        $this->config = Yaml::parseFile($this->configFilename);
        $this->data = Yaml::parseFile($this->dataFilename);
        $this->modifiedAt = filemtime($this->dataFilename);
    }


    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function set(array $data)
    {
        $this->data = $data;
    }

    /**
     * Write Yaml data.
     */
    public function write()
    {
        $yaml = Yaml::dump($this->data, 4);

        file_put_contents($this->dataFilename, $yaml);
    }
    
    public function modifiedAt() 
    {
        return $this->modifiedAt;
    }
    
}
