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

namespace Composer\Command;

use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\Request;
use Composer\Installer;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Json\JsonFile;
use Composer\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Package\BasePackage;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class RemoveCommand extends BaseCommand
{
    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('remove')
            ->setDescription('Removes a package from the require or require-dev.')
            ->setDefinition(array(
                new InputArgument('packages', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Packages that should be removed.'),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Removes a package from the require-dev section.'),
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs the operations but will not execute anything (implicitly enables --verbose).'),
                new InputOption('no-progress', null, InputOption::VALUE_NONE, 'Do not output download progress.'),
                new InputOption('no-update', null, InputOption::VALUE_NONE, 'Disables the automatic update of the dependencies (implies --no-install).'),
                new InputOption('no-install', null, InputOption::VALUE_NONE, 'Skip the install step after updating the composer.lock file.'),
                new InputOption('no-scripts', null, InputOption::VALUE_NONE, 'Skips the execution of all scripts defined in composer.json file.'),
                new InputOption('update-no-dev', null, InputOption::VALUE_NONE, 'Run the dependency update with the --no-dev option.'),
                new InputOption('update-with-dependencies', 'w', InputOption::VALUE_NONE, 'Allows inherited dependencies to be updated with explicit dependencies. (Deprecrated, is now default behavior)'),
                new InputOption('update-with-all-dependencies', 'W', InputOption::VALUE_NONE, 'Allows all inherited dependencies to be updated, including those that are root requirements.'),
                new InputOption('with-all-dependencies', null, InputOption::VALUE_NONE, 'Alias for --update-with-all-dependencies'),
                new InputOption('no-update-with-dependencies', null, InputOption::VALUE_NONE, 'Does not allow inherited dependencies to be updated with explicit dependencies.'),
                new InputOption('unused', null, InputOption::VALUE_NONE, 'Remove all packages which are locked but not required by any other package.'),
                new InputOption('ignore-platform-req', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore a specific platform requirement (php & ext- packages).'),
                new InputOption('ignore-platform-reqs', null, InputOption::VALUE_NONE, 'Ignore all platform requirements (php & ext- packages).'),
                new InputOption('optimize-autoloader', 'o', InputOption::VALUE_NONE, 'Optimize autoloader during autoloader dump'),
                new InputOption('classmap-authoritative', 'a', InputOption::VALUE_NONE, 'Autoload classes from the classmap only. Implicitly enables `--optimize-autoloader`.'),
                new InputOption('apcu-autoloader', null, InputOption::VALUE_NONE, 'Use APCu to cache found/not-found classes.'),
                new InputOption('apcu-autoloader-prefix', null, InputOption::VALUE_REQUIRED, 'Use a custom prefix for the APCu autoloader cache. Implicitly enables --apcu-autoloader'),
            ))
            ->setHelp(
                <<<EOT
The <info>remove</info> command removes a package from the current
list of installed packages

<info>php composer.phar remove</info>

Read more at https://getcomposer.org/doc/03-cli.md#remove
EOT
            )
        ;
    }

    /**
     * @return void
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('unused')) {
            $composer = $this->getComposer();
            $locker = $composer->getLocker();
            if (!$locker->isLocked()) {
                throw new \UnexpectedValueException('A valid composer.lock file is required to run this command with --unused');
            }

            $lockedPackages = $locker->getLockedRepository()->getPackages();

            $required = array();
            foreach (array_merge($composer->getPackage()->getRequires(), $composer->getPackage()->getDevRequires()) as $link) {
                $required[$link->getTarget()] = true;
            }

            do {
                $found = false;
                foreach ($lockedPackages as $index => $package) {
                    foreach ($package->getNames() as $name) {
                        if (isset($required[$name])) {
                            foreach ($package->getRequires() as $link) {
                                $required[$link->getTarget()] = true;
                            }
                            $found = true;
                            unset($lockedPackages[$index]);
                            break;
                        }
                    }
                }
            } while ($found);

            $unused = array();
            foreach ($lockedPackages as $package) {
                $unused[] = $package->getName();
            }
            $input->setArgument('packages', array_merge($input->getArgument('packages'), $unused));

            if (!$input->getArgument('packages')) {
                $this->getIO()->writeError('<info>No unused packages to remove</info>');
                $this->setCode(function () {
                    return 0;
                });
            }
        }
    }

    /**
     * @return int
     * @throws \Seld\JsonLint\ParsingException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packages = $input->getArgument('packages');
        $packages = array_map('strtolower', $packages);

        $file = Factory::getComposerFile();

        $jsonFile = new JsonFile($file);
        $composer = $jsonFile->read();
        $composerBackup = file_get_contents($jsonFile->getPath());

        $json = new JsonConfigSource($jsonFile);

        $type = $input->getOption('dev') ? 'require-dev' : 'require';
        $altType = !$input->getOption('dev') ? 'require-dev' : 'require';
        $io = $this->getIO();

        if ($input->getOption('update-with-dependencies')) {
            $io->writeError('<warning>You are using the deprecated option "update-with-dependencies". This is now default behaviour. The --no-update-with-dependencies option can be used to remove a package without its dependencies.</warning>');
        }

        // make sure name checks are done case insensitively
        foreach (array('require', 'require-dev') as $linkType) {
            if (isset($composer[$linkType])) {
                foreach ($composer[$linkType] as $name => $version) {
                    $composer[$linkType][strtolower($name)] = $name;
                }
            }
        }

        $dryRun = $input->getOption('dry-run');
        $toRemove = array();
        foreach ($packages as $package) {
            if (isset($composer[$type][$package])) {
                if ($dryRun) {
                    $toRemove[$type][] = $composer[$type][$package];
                } else {
                    $json->removeLink($type, $composer[$type][$package]);
                }
            } elseif (isset($composer[$altType][$package])) {
                $io->writeError('<warning>' . $composer[$altType][$package] . ' could not be found in ' . $type . ' but it is present in ' . $altType . '</warning>');
                if ($io->isInteractive()) {
                    if ($io->askConfirmation('Do you want to remove it from ' . $altType . ' [<comment>yes</comment>]? ')) {
                        if ($dryRun) {
                            $toRemove[$altType][] = $composer[$altType][$package];
                        } else {
                            $json->removeLink($altType, $composer[$altType][$package]);
                        }
                    }
                }
            } elseif (isset($composer[$type]) && $matches = preg_grep(BasePackage::packageNameToRegexp($package), array_keys($composer[$type]))) {
                foreach ($matches as $matchedPackage) {
                    if ($dryRun) {
                        $toRemove[$type][] = $matchedPackage;
                    } else {
                        $json->removeLink($type, $matchedPackage);
                    }
                }
            } elseif (isset($composer[$altType]) && $matches = preg_grep(BasePackage::packageNameToRegexp($package), array_keys($composer[$altType]))) {
                foreach ($matches as $matchedPackage) {
                    $io->writeError('<warning>' . $matchedPackage . ' could not be found in ' . $type . ' but it is present in ' . $altType . '</warning>');
                    if ($io->isInteractive()) {
                        if ($io->askConfirmation('Do you want to remove it from ' . $altType . ' [<comment>yes</comment>]? ')) {
                            if ($dryRun) {
                                $toRemove[$altType][] = $matchedPackage;
                            } else {
                                $json->removeLink($altType, $matchedPackage);
                            }
                        }
                    }
                }
            } else {
                $io->writeError('<warning>'.$package.' is not required in your composer.json and has not been removed</warning>');
            }
        }

        $io->writeError('<info>'.$file.' has been updated</info>');

        if ($input->getOption('no-update')) {
            return 0;
        }

        // Update packages
        $this->resetComposer();
        $composer = $this->getComposer(true, $input->getOption('no-plugins'));
        $composer->getEventDispatcher()->setRunScripts(!$input->getOption('no-scripts'));

        if ($dryRun) {
            $rootPackage = $composer->getPackage();
            $links = array(
                'require' => $rootPackage->getRequires(),
                'require-dev' => $rootPackage->getDevRequires(),
            );
            foreach ($toRemove as $type => $names) {
                foreach ($names as $name) {
                    unset($links[$type][$name]);
                }
            }
            $rootPackage->setRequires($links['require']);
            $rootPackage->setDevRequires($links['require-dev']);
        }

        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'remove', $input, $output);
        $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);

        $composer->getInstallationManager()->setOutputProgress(!$input->getOption('no-progress'));

        $install = Installer::create($io, $composer);

        $updateDevMode = !$input->getOption('update-no-dev');
        $optimize = $input->getOption('optimize-autoloader') || $composer->getConfig()->get('optimize-autoloader');
        $authoritative = $input->getOption('classmap-authoritative') || $composer->getConfig()->get('classmap-authoritative');
        $apcuPrefix = $input->getOption('apcu-autoloader-prefix');
        $apcu = $apcuPrefix !== null || $input->getOption('apcu-autoloader') || $composer->getConfig()->get('apcu-autoloader');

        $updateAllowTransitiveDependencies = Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS_NO_ROOT_REQUIRE;
        $flags = '';
        if ($input->getOption('update-with-all-dependencies') || $input->getOption('with-all-dependencies')) {
            $updateAllowTransitiveDependencies = Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS;
            $flags .= ' --with-all-dependencies';
        } elseif ($input->getOption('no-update-with-dependencies')) {
            $updateAllowTransitiveDependencies = Request::UPDATE_ONLY_LISTED;
            $flags .= ' --with-dependencies';
        }

        $io->writeError('<info>Running composer update '.implode(' ', $packages).$flags.'</info>');

        $ignorePlatformReqs = $input->getOption('ignore-platform-reqs') ?: ($input->getOption('ignore-platform-req') ?: false);

        $install
            ->setVerbose($input->getOption('verbose'))
            ->setDevMode($updateDevMode)
            ->setOptimizeAutoloader($optimize)
            ->setClassMapAuthoritative($authoritative)
            ->setApcuAutoloader($apcu, $apcuPrefix)
            ->setUpdate(true)
            ->setInstall(!$input->getOption('no-install'))
            ->setUpdateAllowTransitiveDependencies($updateAllowTransitiveDependencies)
            ->setIgnorePlatformRequirements($ignorePlatformReqs)
            ->setDryRun($dryRun)
        ;

        // if no lock is present, we do not do a partial update as
        // this is not supported by the Installer
        if ($composer->getLocker()->isLocked()) {
            $install->setUpdateAllowList($packages);
        }

        $status = $install->run();
        if ($status !== 0) {
            $io->writeError("\n".'<error>Removal failed, reverting '.$file.' to its original content.</error>');
            file_put_contents($jsonFile->getPath(), $composerBackup);
        }

        if (!$dryRun) {
            foreach ($packages as $package) {
                if ($composer->getRepositoryManager()->getLocalRepository()->findPackages($package)) {
                    $io->writeError('<error>Removal failed, '.$package.' is still present, it may be required by another package. See `composer why '.$package.'`.</error>');

                    return 2;
                }
            }
        }

        return $status;
    }
}
