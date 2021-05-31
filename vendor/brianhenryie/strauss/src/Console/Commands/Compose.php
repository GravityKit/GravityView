<?php

namespace BrianHenryIE\Strauss\Console\Commands;

use BrianHenryIE\Strauss\ChangeEnumerator;
use BrianHenryIE\Strauss\Autoload;
use BrianHenryIE\Strauss\Cleanup;
use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\ProjectComposerPackage;
use BrianHenryIE\Strauss\Copier;
use BrianHenryIE\Strauss\FileEnumerator;
use BrianHenryIE\Strauss\Licenser;
use BrianHenryIE\Strauss\Prefixer;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Compose extends Command
{
    /** @var string */
    protected string $workingDir;

    /** @var StraussConfig */
    protected StraussConfig $config;

    protected ProjectComposerPackage $projectComposerPackage;

    /** @var Copier */
    protected Copier $copier;

    /** @var Prefixer */
    protected Prefixer $replacer;
    /**
     * @var ChangeEnumerator
     */
    protected ChangeEnumerator $changeEnumerator;


    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('compose');
        $this->setDescription("Copy composer's `require` and prefix their namespace and classnames.");
        $this->setHelp('');
    }

    /**
     * @see Command::execute()
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDir = getcwd() . DIRECTORY_SEPARATOR;
        $this->workingDir = $workingDir;

        try {
            $this->loadProjectComposerPackage();

            $this->buildDependencyList();

            $this->enumerateFiles();

            $this->copyFiles();

            $this->determineChanges();

            $this->performReplacements();

            $this->addLicenses();

            $this->generateAutoloader();

            $this->cleanUp();
        } catch (Exception $e) {
            $output->write($e->getMessage());
            return 1;
        }

        // What should this be?!
        return 0;
    }


    /**
     * 1. Load the composer.json.
     *
     * @throws Exception
     */
    protected function loadProjectComposerPackage()
    {

        $this->projectComposerPackage = new ProjectComposerPackage($this->workingDir . 'composer.json');

        $config = $this->projectComposerPackage->getStraussConfig();

        $this->config = $config;
    }


    /** @var ComposerPackage[] */
    protected array $flatDependencyTree = [];

    /**
     * 2. Built flat list of packages and dependencies.
     *
     * 2.1 Initiate getting dependencies for the project composer.json.
     *
     * @see Compose::flatDependencyTree
     */
    protected function buildDependencyList()
    {

        $requiredPackageNames = $this->config->getPackages();

        // Unset PHP, ext-*.
        $removePhpExt = function ($element) {
            return !( 0 === strpos($element, 'ext') || 'php' === $element );
        };

        $requiredPackageNames = array_filter($requiredPackageNames, $removePhpExt);

        foreach ($requiredPackageNames as $requiredPackageName) {
            $packageDir = $this->workingDir . 'vendor' .DIRECTORY_SEPARATOR
                . $requiredPackageName . DIRECTORY_SEPARATOR;

            $overrideAutoload = isset($this->config->getOverrideAutoload()[$requiredPackageName])
                ? $this->config->getOverrideAutoload()[$requiredPackageName]
                : null;

            $requiredComposerPackage = new ComposerPackage($packageDir, $overrideAutoload);
            $this->flatDependencyTree[$requiredComposerPackage->getName()] = $requiredComposerPackage;
            $this->getAllDependencies($requiredComposerPackage);
        }
    }

    /**
     * 2.2 Recursive function to get dependencies.
     *
     * @param ComposerPackage $requiredDependency
     */
    protected function getAllDependencies(ComposerPackage $requiredDependency): void
    {
        $excludedPackagesNames = $this->config->getExcludePackagesFromPrefixing();

        // Unset PHP, ext-*.
        $removePhpExt = function ($element) {
            return !( 0 === strpos($element, 'ext') || 'php' === $element );
        };

        $required = array_filter($requiredDependency->getRequiresNames(), $removePhpExt);

        foreach ($required as $dependencyName) {
            if (in_array($dependencyName, $excludedPackagesNames)) {
                continue;
            }

            $overrideAutoload = isset($this->config->getOverrideAutoload()[$dependencyName])
                ? $this->config->getOverrideAutoload()[$dependencyName]
                : null;

            $dependencyComposerPackage = new ComposerPackage(
                $this->workingDir . 'vendor' . DIRECTORY_SEPARATOR
                . $dependencyName . DIRECTORY_SEPARATOR . 'composer.json',
                $overrideAutoload
            );

            $this->flatDependencyTree[$dependencyName] = $dependencyComposerPackage;
            $this->getAllDependencies($dependencyComposerPackage);
        }
    }

    protected FileEnumerator $fileEnumerator;

    protected function enumerateFiles()
    {

        $this->fileEnumerator = new FileEnumerator(
            $this->flatDependencyTree,
            $this->workingDir,
            $this->config
        );

        $this->fileEnumerator->compileFileList();
    }

    // 3. Copy autoloaded files for each
    protected function copyFiles()
    {

        $this->copier = new Copier(
            $this->fileEnumerator->getAllFilesAndDependencyList(),
            $this->workingDir,
            $this->config->getTargetDirectory()
        );

        $this->copier->prepareTarget();

        $this->copier->copy();
    }

    // 4. Determine namespace and classname changes
    protected function determineChanges()
    {

        $this->changeEnumerator = new ChangeEnumerator($this->config);

        $relativeTargetDir = $this->config->getTargetDirectory();
        $phpFiles = $this->fileEnumerator->getPhpFilesAndDependencyList();
        $this->changeEnumerator->findInFiles($relativeTargetDir, $phpFiles);
    }

    // 5. Update namespaces and class names.
    // Replace references to updated namespaces and classnames throughout the dependencies.
    protected function performReplacements()
    {
        $this->replacer = new Prefixer($this->config, $this->workingDir);

        $namespaces = $this->changeEnumerator->getDiscoveredNamespaceReplacements();
        $classes = $this->changeEnumerator->getDiscoveredClasses();
        
        $phpFiles = $this->fileEnumerator->getPhpFilesAndDependencyList();

        $this->replacer->replaceInFiles($namespaces, $classes, $phpFiles);
    }

    protected function addLicenses(): void
    {

        $author = $this->projectComposerPackage->getAuthor();

        $dependencies = $this->flatDependencyTree;

        $licenser = new Licenser($this->config, $this->workingDir, $dependencies, $author);

        $licenser->copyLicenses();

        $modifiedFiles = $this->replacer->getModifiedFiles();
        $licenser->addInformationToUpdatedFiles($modifiedFiles);
    }

    /**
     * 6. Generate autoloader.
     */
    protected function generateAutoloader()
    {

        $files = $this->fileEnumerator->getFilesAutoloaders();

        $classmap = new Autoload($this->config, $this->workingDir, $files);

        $classmap->generate();
    }


    /**
     * 7.
     * Delete source files if desired.
     * Delete empty directories in destination.
     */
    protected function cleanUp()
    {

        $cleanup = new Cleanup($this->config, $this->workingDir);

        $sourceFiles = array_keys($this->fileEnumerator->getAllFilesAndDependencyList());

        // This will check the config to check should it delete or not.
        $cleanup->cleanup($sourceFiles);
    }
}
