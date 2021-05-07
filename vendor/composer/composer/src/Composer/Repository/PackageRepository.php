<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Repository;

use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\ValidatingArrayLoader;

/**
 * Package repository.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class PackageRepository extends ArrayRepository
{
    private $config;

    /**
     * Initializes filesystem repository.
     *
     * @param array $config package definition
     */
    public function __construct(array $config)
    {
        parent::__construct();
        $this->config = $config['package'];

        // make sure we have an array of package definitions
        if (!is_numeric(key($this->config))) {
            $this->config = array($this->config);
        }
    }

    /**
     * Initializes repository (reads file, or remote address).
     */
    protected function initialize()
    {
        parent::initialize();

        $loader = new ValidatingArrayLoader(new ArrayLoader(null, true), false);
        foreach ($this->config as $package) {
            try {
                $package = $loader->load($package);
            } catch (\Exception $e) {
                throw new InvalidRepositoryException('A repository of type "package" contains an invalid package definition: '.$e->getMessage()."\n\nInvalid package definition:\n".json_encode($package));
            }

            $this->addPackage($package);
        }
    }

    public function getRepoName()
    {
        return preg_replace('{^array }', 'package ', parent::getRepoName());
    }
}
