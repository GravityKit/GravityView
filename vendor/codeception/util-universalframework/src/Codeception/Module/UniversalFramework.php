<?php
namespace Codeception\Module;

use Codeception\Configuration;
use Codeception\Lib\Connector\Universal as Connector;
use Codeception\Lib\Framework;

class UniversalFramework extends Framework
{

    public function _initialize()
    {
        if (isset($this->config['index'])) {
            $index = $this->config['index'];
        } else {
            $index = Configuration::dataDir() . '/app/index.php';
        }
        $this->client = new Connector();
        $this->client->setIndex($index);
    }

    public function useUniversalFramework()
    {

    }
}
