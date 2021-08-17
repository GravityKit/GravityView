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

use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Json\JsonValidationException;
use Composer\Package\BasePackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositorySet;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Silencer;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Helper\FormatterHelper;

/**
 * @author Justin Rainbow <justin.rainbow@gmail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class InitCommand extends BaseCommand
{
    /** @var CompositeRepository */
    protected $repos;

    /** @var array */
    private $gitConfig;

    /** @var RepositorySet[] */
    private $repositorySets;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Creates a basic composer.json file in current directory.')
            ->setDefinition(array(
                new InputOption('name', null, InputOption::VALUE_REQUIRED, 'Name of the package'),
                new InputOption('description', null, InputOption::VALUE_REQUIRED, 'Description of package'),
                new InputOption('author', null, InputOption::VALUE_REQUIRED, 'Author name of package'),
                // new InputOption('version', null, InputOption::VALUE_NONE, 'Version of package'),
                new InputOption('type', null, InputOption::VALUE_OPTIONAL, 'Type of package (e.g. library, project, metapackage, composer-plugin)'),
                new InputOption('homepage', null, InputOption::VALUE_REQUIRED, 'Homepage of package'),
                new InputOption('require', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Package to require with a version constraint, e.g. foo/bar:1.0.0 or foo/bar=1.0.0 or "foo/bar 1.0.0"'),
                new InputOption('require-dev', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Package to require for development with a version constraint, e.g. foo/bar:1.0.0 or foo/bar=1.0.0 or "foo/bar 1.0.0"'),
                new InputOption('stability', 's', InputOption::VALUE_REQUIRED, 'Minimum stability (empty or one of: '.implode(', ', array_keys(BasePackage::$stabilities)).')'),
                new InputOption('license', 'l', InputOption::VALUE_REQUIRED, 'License of package'),
                new InputOption('repository', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Add custom repositories, either by URL or using JSON arrays'),
                new InputOption('autoload', 'a', InputOption::VALUE_REQUIRED, 'Add PSR-4 autoload mapping. Maps your package\'s namespace to the provided directory. (Expects a relative path, e.g. src/)'),
            ))
            ->setHelp(
                <<<EOT
The <info>init</info> command creates a basic composer.json file
in the current directory.

<info>php composer.phar init</info>

Read more at https://getcomposer.org/doc/03-cli.md#init
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = $this->getIO();

        $allowlist = array('name', 'description', 'author', 'type', 'homepage', 'require', 'require-dev', 'stability', 'license', 'autoload');
        $options = array_filter(array_intersect_key($input->getOptions(), array_flip($allowlist)));

        if (isset($options['name']) && !preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}D', $options['name'])) {
            throw new \InvalidArgumentException(
                'The package name '.$options['name'].' is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name, matching: [a-z0-9_.-]+/[a-z0-9_.-]+'
            );
        }

        if (isset($options['author'])) {
            $options['authors'] = $this->formatAuthors($options['author']);
            unset($options['author']);
        }

        $repositories = $input->getOption('repository');
        if ($repositories) {
            $config = Factory::createConfig($io);
            foreach ($repositories as $repo) {
                $options['repositories'][] = RepositoryFactory::configFromString($io, $config, $repo, true);
            }
        }

        if (isset($options['stability'])) {
            $options['minimum-stability'] = $options['stability'];
            unset($options['stability']);
        }

        $options['require'] = isset($options['require']) ? $this->formatRequirements($options['require']) : new \stdClass;
        if (array() === $options['require']) {
            $options['require'] = new \stdClass;
        }

        if (isset($options['require-dev'])) {
            $options['require-dev'] = $this->formatRequirements($options['require-dev']);
            if (array() === $options['require-dev']) {
                $options['require-dev'] = new \stdClass;
            }
        }

        // --autoload - create autoload object
        $autoloadPath = null;
        if (isset($options['autoload'])) {
            $autoloadPath = $options['autoload'];
            $namespace = $this->namespaceFromPackageName($input->getOption('name'));
            $options['autoload'] = (object) array(
                'psr-4' => array(
                    $namespace . '\\' => $autoloadPath,
                ),
            );
        }

        $file = new JsonFile(Factory::getComposerFile());
        $json = JsonFile::encode($options);

        if ($input->isInteractive()) {
            $io->writeError(array('', $json, ''));
            if (!$io->askConfirmation('Do you confirm generation [<comment>yes</comment>]? ')) {
                $io->writeError('<error>Command aborted</error>');

                return 1;
            }
        } else {
            if (json_encode($options) === '{"require":{}}') {
                throw new \RuntimeException('You have to run this command in interactive mode, or specify at least some data using --name, --require, etc.');
            }

            $io->writeError('Writing '.$file->getPath());
        }

        $file->write($options);
        try {
            $file->validateSchema(JsonFile::LAX_SCHEMA);
        } catch (JsonValidationException $e) {
            $io->writeError('<error>Schema validation error, aborting</error>');
            $errors = ' - ' . implode(PHP_EOL . ' - ', $e->getErrors());
            $io->writeError($e->getMessage() . ':' . PHP_EOL . $errors);
            Silencer::call('unlink', $file->getPath());

            return 1;
        }

        // --autoload - Create src folder
        if ($autoloadPath) {
            $filesystem = new Filesystem();
            $filesystem->ensureDirectoryExists($autoloadPath);

            // dump-autoload only for projects without added dependencies.
            if (!$this->hasDependencies($options)) {
                $this->runDumpAutoloadCommand($output);
            }
        }

        if ($input->isInteractive() && is_dir('.git')) {
            $ignoreFile = realpath('.gitignore');

            if (false === $ignoreFile) {
                $ignoreFile = realpath('.') . '/.gitignore';
            }

            if (!$this->hasVendorIgnore($ignoreFile)) {
                $question = 'Would you like the <info>vendor</info> directory added to your <info>.gitignore</info> [<comment>yes</comment>]? ';

                if ($io->askConfirmation($question)) {
                    $this->addVendorIgnore($ignoreFile);
                }
            }
        }

        $question = 'Would you like to install dependencies now [<comment>yes</comment>]? ';
        if ($input->isInteractive() && $this->hasDependencies($options) && $io->askConfirmation($question)) {
            $this->updateDependencies($output);
        }

        // --autoload - Show post-install configuration info
        if ($autoloadPath) {
            $namespace = $this->namespaceFromPackageName($input->getOption('name'));

            $io->writeError('PSR-4 autoloading configured. Use "<comment>namespace '.$namespace.';</comment>" in '.$autoloadPath);
            $io->writeError('Include the Composer autoloader with: <comment>require \'vendor/autoload.php\';</comment>');
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $git = $this->getGitConfig();
        $io = $this->getIO();
        /** @var FormatterHelper $formatter */
        $formatter = $this->getHelperSet()->get('formatter');

        // initialize repos if configured
        $repositories = $input->getOption('repository');
        if ($repositories) {
            $config = Factory::createConfig($io);
            $repos = array(new PlatformRepository);
            $createDefaultPackagistRepo = true;
            foreach ($repositories as $repo) {
                $repoConfig = RepositoryFactory::configFromString($io, $config, $repo, true);
                if (
                    (isset($repoConfig['packagist']) && $repoConfig === array('packagist' => false))
                    || (isset($repoConfig['packagist.org']) && $repoConfig === array('packagist.org' => false))
                ) {
                    $createDefaultPackagistRepo = false;
                    continue;
                }
                $repos[] = RepositoryFactory::createRepo($io, $config, $repoConfig);
            }

            if ($createDefaultPackagistRepo) {
                $repos[] = RepositoryFactory::createRepo($io, $config, array(
                    'type' => 'composer',
                    'url' => 'https://repo.packagist.org',
                ));
            }

            $this->repos = new CompositeRepository($repos);
            unset($repos, $config, $repositories);
        }

        $io->writeError(array(
            '',
            $formatter->formatBlock('Welcome to the Composer config generator', 'bg=blue;fg=white', true),
            '',
        ));

        // namespace
        $io->writeError(array(
            '',
            'This command will guide you through creating your composer.json config.',
            '',
        ));

        $cwd = realpath(".");

        if (!$name = $input->getOption('name')) {
            $name = basename($cwd);
            $name = preg_replace('{(?:([a-z])([A-Z])|([A-Z])([A-Z][a-z]))}', '\\1\\3-\\2\\4', $name);
            $name = strtolower($name);
            if (!empty($_SERVER['COMPOSER_DEFAULT_VENDOR'])) {
                $name = $_SERVER['COMPOSER_DEFAULT_VENDOR'] . '/' . $name;
            } elseif (isset($git['github.user'])) {
                $name = $git['github.user'] . '/' . $name;
            } elseif (!empty($_SERVER['USERNAME'])) {
                $name = $_SERVER['USERNAME'] . '/' . $name;
            } elseif (!empty($_SERVER['USER'])) {
                $name = $_SERVER['USER'] . '/' . $name;
            } elseif (get_current_user()) {
                $name = get_current_user() . '/' . $name;
            } else {
                // package names must be in the format foo/bar
                $name .= '/' . $name;
            }
            $name = strtolower($name);
        }

        $name = $io->askAndValidate(
            'Package name (<vendor>/<name>) [<comment>'.$name.'</comment>]: ',
            function ($value) use ($name) {
                if (null === $value) {
                    return $name;
                }

                if (!preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}D', $value)) {
                    throw new \InvalidArgumentException(
                        'The package name '.$value.' is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name, matching: [a-z0-9_.-]+/[a-z0-9_.-]+'
                    );
                }

                return $value;
            },
            null,
            $name
        );
        $input->setOption('name', $name);

        $description = $input->getOption('description') ?: false;
        $description = $io->ask(
            'Description [<comment>'.$description.'</comment>]: ',
            $description
        );
        $input->setOption('description', $description);

        if (null === $author = $input->getOption('author')) {
            if (!empty($_SERVER['COMPOSER_DEFAULT_AUTHOR'])) {
                $author_name = $_SERVER['COMPOSER_DEFAULT_AUTHOR'];
            } elseif (isset($git['user.name'])) {
                $author_name = $git['user.name'];
            }

            if (!empty($_SERVER['COMPOSER_DEFAULT_EMAIL'])) {
                $author_email = $_SERVER['COMPOSER_DEFAULT_EMAIL'];
            } elseif (isset($git['user.email'])) {
                $author_email = $git['user.email'];
            }

            if (isset($author_name, $author_email)) {
                $author = sprintf('%s <%s>', $author_name, $author_email);
            }
        }

        $self = $this;
        $author = $io->askAndValidate(
            'Author [<comment>'.$author.'</comment>, n to skip]: ',
            function ($value) use ($self, $author) {
                if ($value === 'n' || $value === 'no') {
                    return;
                }
                $value = $value ?: $author;
                $author = $self->parseAuthorString($value);

                return sprintf('%s <%s>', $author['name'], $author['email']);
            },
            null,
            $author
        );
        $input->setOption('author', $author);

        $minimumStability = $input->getOption('stability') ?: null;
        $minimumStability = $io->askAndValidate(
            'Minimum Stability [<comment>'.$minimumStability.'</comment>]: ',
            function ($value) use ($minimumStability) {
                if (null === $value) {
                    return $minimumStability;
                }

                if (!isset(BasePackage::$stabilities[$value])) {
                    throw new \InvalidArgumentException(
                        'Invalid minimum stability "'.$value.'". Must be empty or one of: '.
                        implode(', ', array_keys(BasePackage::$stabilities))
                    );
                }

                return $value;
            },
            null,
            $minimumStability
        );
        $input->setOption('stability', $minimumStability);

        $type = $input->getOption('type') ?: false;
        $type = $io->ask(
            'Package Type (e.g. library, project, metapackage, composer-plugin) [<comment>'.$type.'</comment>]: ',
            $type
        );
        $input->setOption('type', $type);

        if (null === $license = $input->getOption('license')) {
            if (!empty($_SERVER['COMPOSER_DEFAULT_LICENSE'])) {
                $license = $_SERVER['COMPOSER_DEFAULT_LICENSE'];
            }
        }

        $license = $io->ask(
            'License [<comment>'.$license.'</comment>]: ',
            $license
        );
        $input->setOption('license', $license);

        $io->writeError(array('', 'Define your dependencies.', ''));

        // prepare to resolve dependencies
        $repos = $this->getRepos();
        $preferredStability = $minimumStability ?: 'stable';
        $platformRepo = null;
        if ($repos instanceof CompositeRepository) {
            foreach ($repos->getRepositories() as $candidateRepo) {
                if ($candidateRepo instanceof PlatformRepository) {
                    $platformRepo = $candidateRepo;
                    break;
                }
            }
        }

        $question = 'Would you like to define your dependencies (require) interactively [<comment>yes</comment>]? ';
        $require = $input->getOption('require');
        $requirements = array();
        if ($require || $io->askConfirmation($question)) {
            $requirements = $this->determineRequirements($input, $output, $require, $platformRepo, $preferredStability);
        }
        $input->setOption('require', $requirements);

        $question = 'Would you like to define your dev dependencies (require-dev) interactively [<comment>yes</comment>]? ';
        $requireDev = $input->getOption('require-dev');
        $devRequirements = array();
        if ($requireDev || $io->askConfirmation($question)) {
            $devRequirements = $this->determineRequirements($input, $output, $requireDev, $platformRepo, $preferredStability);
        }
        $input->setOption('require-dev', $devRequirements);

        // --autoload - input and validation
        $autoload = $input->getOption('autoload') ?: 'src/';
        $namespace = $this->namespaceFromPackageName($input->getOption('name'));
        $autoload = $io->askAndValidate(
            'Add PSR-4 autoload mapping? Maps namespace "'.$namespace.'" to the entered relative path. [<comment>'.$autoload.'</comment>, n to skip]: ',
            function ($value) use ($autoload) {
                if (null === $value) {
                    return $autoload;
                }

                if ($value === 'n' || $value === 'no') {
                    return;
                }

                $value = $value ?: $autoload;

                if (!preg_match('{^[^/][A-Za-z0-9\-_/]+/$}', $value)) {
                    throw new \InvalidArgumentException(sprintf(
                        'The src folder name "%s" is invalid. Please add a relative path with tailing forward slash. [A-Za-z0-9_-/]+/',
                        $value
                    ));
                }

                return $value;
            },
            null,
            $autoload
        );
        $input->setOption('autoload', $autoload);
    }

    /**
     * @private
     * @param  string $author
     * @return array
     */
    public function parseAuthorString($author)
    {
        if (preg_match('/^(?P<name>[- .,\p{L}\p{N}\p{Mn}\'’"()]+) <(?P<email>.+?)>$/u', $author, $match)) {
            if ($this->isValidEmail($match['email'])) {
                return array(
                    'name' => trim($match['name']),
                    'email' => $match['email'],
                );
            }
        }

        throw new \InvalidArgumentException(
            'Invalid author string.  Must be in the format: '.
            'John Smith <john@example.com>'
        );
    }

    protected function findPackages($name)
    {
        return $this->getRepos()->search($name);
    }

    protected function getRepos()
    {
        if (!$this->repos) {
            $this->repos = new CompositeRepository(array_merge(
                array(new PlatformRepository),
                RepositoryFactory::defaultRepos($this->getIO())
            ));
        }

        return $this->repos;
    }

    final protected function determineRequirements(InputInterface $input, OutputInterface $output, $requires = array(), PlatformRepository $platformRepo = null, $preferredStability = 'stable', $checkProvidedVersions = true, $fixed = false)
    {
        if ($requires) {
            $requires = $this->normalizeRequirements($requires);
            $result = array();
            $io = $this->getIO();

            foreach ($requires as $requirement) {
                if (!isset($requirement['version'])) {
                    // determine the best version automatically
                    list($name, $version) = $this->findBestVersionAndNameForPackage($input, $requirement['name'], $platformRepo, $preferredStability, null, null, $fixed);
                    $requirement['version'] = $version;

                    // replace package name from packagist.org
                    $requirement['name'] = $name;

                    $io->writeError(sprintf(
                        'Using version <info>%s</info> for <info>%s</info>',
                        $requirement['version'],
                        $requirement['name']
                    ));
                } else {
                    // check that the specified version/constraint exists before we proceed
                    list($name) = $this->findBestVersionAndNameForPackage($input, $requirement['name'], $platformRepo, $preferredStability, $checkProvidedVersions ? $requirement['version'] : null, 'dev', $fixed);

                    // replace package name from packagist.org
                    $requirement['name'] = $name;
                }

                $result[] = $requirement['name'] . ' ' . $requirement['version'];
            }

            return $result;
        }

        $versionParser = new VersionParser();

        // Collect existing packages
        $composer = $this->getComposer(false);
        $installedRepo = $composer ? $composer->getRepositoryManager()->getLocalRepository() : null;
        $existingPackages = array();
        if ($installedRepo) {
            foreach ($installedRepo->getPackages() as $package) {
                $existingPackages[] = $package->getName();
            }
        }
        unset($composer, $installedRepo);

        $io = $this->getIO();
        while (null !== $package = $io->ask('Search for a package: ')) {
            $matches = $this->findPackages($package);

            if (count($matches)) {
                // Remove existing packages from search results.
                foreach ($matches as $position => $foundPackage) {
                    if (in_array($foundPackage['name'], $existingPackages, true)) {
                        unset($matches[$position]);
                    }
                }
                $matches = array_values($matches);

                $exactMatch = null;
                $choices = array();
                foreach ($matches as $position => $foundPackage) {
                    $abandoned = '';
                    if (isset($foundPackage['abandoned'])) {
                        if (is_string($foundPackage['abandoned'])) {
                            $replacement = sprintf('Use %s instead', $foundPackage['abandoned']);
                        } else {
                            $replacement = 'No replacement was suggested';
                        }
                        $abandoned = sprintf('<warning>Abandoned. %s.</warning>', $replacement);
                    }

                    $choices[] = sprintf(' <info>%5s</info> %s %s', "[$position]", $foundPackage['name'], $abandoned);
                    if ($foundPackage['name'] === $package) {
                        $exactMatch = true;
                        break;
                    }
                }

                // no match, prompt which to pick
                if (!$exactMatch) {
                    $io->writeError(array(
                        '',
                        sprintf('Found <info>%s</info> packages matching <info>%s</info>', count($matches), $package),
                        '',
                    ));

                    $io->writeError($choices);
                    $io->writeError('');

                    $validator = function ($selection) use ($matches, $versionParser) {
                        if ('' === $selection) {
                            return false;
                        }

                        if (is_numeric($selection) && isset($matches[(int) $selection])) {
                            $package = $matches[(int) $selection];

                            return $package['name'];
                        }

                        if (preg_match('{^\s*(?P<name>[\S/]+)(?:\s+(?P<version>\S+))?\s*$}', $selection, $packageMatches)) {
                            if (isset($packageMatches['version'])) {
                                // parsing `acme/example ~2.3`

                                // validate version constraint
                                $versionParser->parseConstraints($packageMatches['version']);

                                return $packageMatches['name'].' '.$packageMatches['version'];
                            }

                            // parsing `acme/example`
                            return $packageMatches['name'];
                        }

                        throw new \Exception('Not a valid selection');
                    };

                    $package = $io->askAndValidate(
                        'Enter package # to add, or the complete package name if it is not listed: ',
                        $validator,
                        3,
                        false
                    );
                }

                // no constraint yet, determine the best version automatically
                if (false !== $package && false === strpos($package, ' ')) {
                    $validator = function ($input) {
                        $input = trim($input);

                        return $input ?: false;
                    };

                    $constraint = $io->askAndValidate(
                        'Enter the version constraint to require (or leave blank to use the latest version): ',
                        $validator,
                        3,
                        false
                    );

                    if (false === $constraint) {
                        list(, $constraint) = $this->findBestVersionAndNameForPackage($input, $package, $platformRepo, $preferredStability);

                        $io->writeError(sprintf(
                            'Using version <info>%s</info> for <info>%s</info>',
                            $constraint,
                            $package
                        ));
                    }

                    $package .= ' '.$constraint;
                }

                if (false !== $package) {
                    $requires[] = $package;
                    $existingPackages[] = substr($package, 0, strpos($package, ' '));
                }
            }
        }

        return $requires;
    }

    protected function formatAuthors($author)
    {
        return array($this->parseAuthorString($author));
    }

    /**
     * Extract namespace from package's vendor name.
     *
     * new_projects.acme-extra/package-name becomes "NewProjectsAcmeExtra\PackageName"
     *
     * @param string $packageName
     *
     * @return string|null
     */
    public function namespaceFromPackageName($packageName)
    {
        if (!$packageName || strpos($packageName, '/') === false) {
            return null;
        }

        $namespace = array_map(
            function ($part) {
                $part = preg_replace('/[^a-z0-9]/i', ' ', $part);
                $part = ucwords($part);

                return str_replace(' ', '', $part);
            },
            explode('/', $packageName)
        );

        return join('\\', $namespace);
    }

    protected function getGitConfig()
    {
        if (null !== $this->gitConfig) {
            return $this->gitConfig;
        }

        $finder = new ExecutableFinder();
        $gitBin = $finder->find('git');

        // TODO in v3 always call with an array
        if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
            $cmd = new Process(array($gitBin, 'config', '-l'));
        } else {
            $cmd = new Process(sprintf('%s config -l', ProcessExecutor::escape($gitBin)));
        }
        $cmd->run();

        if ($cmd->isSuccessful()) {
            $this->gitConfig = array();
            preg_match_all('{^([^=]+)=(.*)$}m', $cmd->getOutput(), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $this->gitConfig[$match[1]] = $match[2];
            }

            return $this->gitConfig;
        }

        return $this->gitConfig = array();
    }

    /**
     * Checks the local .gitignore file for the Composer vendor directory.
     *
     * Tested patterns include:
     *  "/$vendor"
     *  "$vendor"
     *  "$vendor/"
     *  "/$vendor/"
     *  "/$vendor/*"
     *  "$vendor/*"
     *
     * @param string $ignoreFile
     * @param string $vendor
     *
     * @return bool
     */
    protected function hasVendorIgnore($ignoreFile, $vendor = 'vendor')
    {
        if (!file_exists($ignoreFile)) {
            return false;
        }

        $pattern = sprintf('{^/?%s(/\*?)?$}', preg_quote($vendor));

        $lines = file($ignoreFile, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    protected function addVendorIgnore($ignoreFile, $vendor = '/vendor/')
    {
        $contents = "";
        if (file_exists($ignoreFile)) {
            $contents = file_get_contents($ignoreFile);

            if (strpos($contents, "\n") !== 0) {
                $contents .= "\n";
            }
        }

        file_put_contents($ignoreFile, $contents . $vendor. "\n");
    }

    protected function isValidEmail($email)
    {
        // assume it's valid if we can't validate it
        if (!function_exists('filter_var')) {
            return true;
        }

        // php <5.3.3 has a very broken email validator, so bypass checks
        if (PHP_VERSION_ID < 50303) {
            return true;
        }

        return false !== filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function getRepositorySet(InputInterface $input, $minimumStability = null)
    {
        $key = $minimumStability ?: 'default';

        if (!isset($this->repositorySets[$key])) {
            $this->repositorySets[$key] = $repositorySet = new RepositorySet($minimumStability ?: $this->getMinimumStability($input));
            $repositorySet->addRepository($this->getRepos());
        }

        return $this->repositorySets[$key];
    }

    private function getMinimumStability(InputInterface $input)
    {
        if ($input->hasOption('stability')) {
            return VersionParser::normalizeStability($input->getOption('stability') ?: 'stable');
        }

        $file = Factory::getComposerFile();
        if (is_file($file) && Filesystem::isReadable($file) && is_array($composer = json_decode(file_get_contents($file), true))) {
            if (!empty($composer['minimum-stability'])) {
                return VersionParser::normalizeStability($composer['minimum-stability']);
            }
        }

        return 'stable';
    }

    /**
     * Given a package name, this determines the best version to use in the require key.
     *
     * This returns a version with the ~ operator prefixed when possible.
     *
     * @param  InputInterface            $input
     * @param  string                    $name
     * @param  PlatformRepository|null   $platformRepo
     * @param  string                    $preferredStability
     * @param  string|null               $requiredVersion
     * @param  string                    $minimumStability
     * @param  bool                      $fixed
     * @throws \InvalidArgumentException
     * @return array                     name version
     */
    private function findBestVersionAndNameForPackage(InputInterface $input, $name, PlatformRepository $platformRepo = null, $preferredStability = 'stable', $requiredVersion = null, $minimumStability = null, $fixed = null)
    {
        // handle ignore-platform-reqs flag if present
        $ignorePlatformReqs = false;
        if ($input->hasOption('ignore-platform-reqs') && $input->hasOption('ignore-platform-req')) {
            $ignorePlatformReqs = $input->getOption('ignore-platform-reqs') ?: ($input->getOption('ignore-platform-req') ?: false);
        }

        // find the latest version allowed in this repo set
        $versionSelector = new VersionSelector($this->getRepositorySet($input, $minimumStability), $platformRepo);
        $effectiveMinimumStability = $minimumStability ?: $this->getMinimumStability($input);

        $package = $versionSelector->findBestCandidate($name, $requiredVersion, $preferredStability, $ignorePlatformReqs);

        if (!$package) {
            // platform packages can not be found in the pool in versions other than the local platform's has
            // so if platform reqs are ignored we just take the user's word for it
            if ((true === $ignorePlatformReqs || (is_array($ignorePlatformReqs) && in_array($name, $ignorePlatformReqs))) && PlatformRepository::isPlatformPackage($name)) {
                return array($name, $requiredVersion ?: '*');
            }

            // Check whether the PHP version was the problem
            if (true !== $ignorePlatformReqs && ($candidate = $versionSelector->findBestCandidate($name, $requiredVersion, $preferredStability, true))) {
                throw new \InvalidArgumentException(sprintf(
                    'Package %s%s has a PHP requirement incompatible with your PHP version, PHP extensions and Composer version' . $this->getPlatformExceptionDetails($candidate, $platformRepo),
                    $name,
                    $requiredVersion ? ' at version '.$requiredVersion : ''
                ));
            }
            // Check whether the minimum stability was the problem but the package exists
            if ($package = $versionSelector->findBestCandidate($name, $requiredVersion, $preferredStability, $ignorePlatformReqs, RepositorySet::ALLOW_UNACCEPTABLE_STABILITIES)) {
                // we must first verify if a valid package would be found in a lower priority repository
                if ($allReposPackage = $versionSelector->findBestCandidate($name, $requiredVersion, $preferredStability, $ignorePlatformReqs, RepositorySet::ALLOW_SHADOWED_REPOSITORIES)) {
                    throw new \InvalidArgumentException(
                        'Package '.$name.' exists in '.$allReposPackage->getRepository()->getRepoName().' and '.$package->getRepository()->getRepoName().' which has a higher repository priority. The packages with higher priority do not match your minimum-stability and are therefore not installable. See https://getcomposer.org/repoprio for details and assistance.'
                    );
                }

                throw new \InvalidArgumentException(sprintf(
                    'Could not find a version of package %s matching your minimum-stability (%s). Require it with an explicit version constraint allowing its desired stability.',
                    $name,
                    $effectiveMinimumStability
                ));
            }
            // Check whether the required version was the problem
            if ($requiredVersion && $package = $versionSelector->findBestCandidate($name, null, $preferredStability, $ignorePlatformReqs)) {
                // we must first verify if a valid package would be found in a lower priority repository
                if ($allReposPackage = $versionSelector->findBestCandidate($name, $requiredVersion, $preferredStability, false, RepositorySet::ALLOW_SHADOWED_REPOSITORIES)) {
                    throw new \InvalidArgumentException(
                        'Package '.$name.' exists in '.$allReposPackage->getRepository()->getRepoName().' and '.$package->getRepository()->getRepoName().' which has a higher repository priority. The packages with higher priority do not match your constraint and are therefore not installable. See https://getcomposer.org/repoprio for details and assistance.'
                    );
                }

                throw new \InvalidArgumentException(sprintf(
                    'Could not find package %s in a version matching "%s" and a stability matching "'.$effectiveMinimumStability.'".',
                    $name,
                    $requiredVersion
                ));
            }
            // Check whether the PHP version was the problem for all versions
            if (true !== $ignorePlatformReqs && ($candidate = $versionSelector->findBestCandidate($name, null, $preferredStability, true, RepositorySet::ALLOW_UNACCEPTABLE_STABILITIES))) {
                $additional = '';
                if (false === $versionSelector->findBestCandidate($name, null, $preferredStability, true)) {
                    $additional = PHP_EOL.PHP_EOL.'Additionally, the package was only found with a stability of "'.$candidate->getStability().'" while your minimum stability is "'.$effectiveMinimumStability.'".';
                }

                throw new \InvalidArgumentException(sprintf(
                    'Could not find package %s in any version matching your PHP version, PHP extensions and Composer version' . $this->getPlatformExceptionDetails($candidate, $platformRepo) . '%s',
                    $name,
                    $additional
                ));
            }

            // Check for similar names/typos
            $similar = $this->findSimilar($name);
            if ($similar) {
                if (in_array($name, $similar, true)) {
                    throw new \InvalidArgumentException(sprintf(
                        "Could not find package %s. It was however found via repository search, which indicates a consistency issue with the repository.",
                        $name
                    ));
                }

                throw new \InvalidArgumentException(sprintf(
                    "Could not find package %s.\n\nDid you mean " . (count($similar) > 1 ? 'one of these' : 'this') . "?\n    %s",
                    $name,
                    implode("\n    ", $similar)
                ));
            }

            throw new \InvalidArgumentException(sprintf(
                'Could not find a matching version of package %s. Check the package spelling, your version constraint and that the package is available in a stability which matches your minimum-stability (%s).',
                $name,
                $effectiveMinimumStability
            ));
        }

        return array(
            $package->getPrettyName(),
            $fixed ? $package->getPrettyVersion() : $versionSelector->findRecommendedRequireVersion($package),
        );
    }

    private function getPlatformExceptionDetails(PackageInterface $candidate, PlatformRepository $platformRepo = null)
    {
        $details = array();
        if (!$platformRepo) {
            return '';
        }

        foreach ($candidate->getRequires() as $link) {
            if (!PlatformRepository::isPlatformPackage($link->getTarget())) {
                continue;
            }
            $platformPkg = $platformRepo->findPackage($link->getTarget(), '*');
            if (!$platformPkg) {
                $details[] = $candidate->getPrettyName().' '.$candidate->getPrettyVersion().' requires '.$link->getTarget().' '.$link->getPrettyConstraint().' but it is not present.';
                continue;
            }
            if (!$link->getConstraint()->matches(new Constraint('==', $platformPkg->getVersion()))) {
                $platformPkgVersion = $platformPkg->getPrettyVersion();
                $platformExtra = $platformPkg->getExtra();
                if (isset($platformExtra['config.platform']) && $platformPkg instanceof CompletePackageInterface) {
                    $platformPkgVersion .= ' ('.$platformPkg->getDescription().')';
                }
                $details[] = $candidate->getPrettyName().' '.$candidate->getPrettyVersion().' requires '.$link->getTarget().' '.$link->getPrettyConstraint().' which does not match your installed version '.$platformPkgVersion.'.';
            }
        }

        if (!$details) {
            return '';
        }

        return ':'.PHP_EOL.'  - ' . implode(PHP_EOL.'  - ', $details);
    }

    private function findSimilar($package)
    {
        try {
            $results = $this->repos->search($package);
        } catch (\Exception $e) {
            // ignore search errors
            return array();
        }
        $similarPackages = array();

        $installedRepo = $this->getComposer()->getRepositoryManager()->getLocalRepository();

        foreach ($results as $result) {
            if ($installedRepo->findPackage($result['name'], '*')) {
                // Ignore installed package
                continue;
            }
            $similarPackages[$result['name']] = levenshtein($package, $result['name']);
        }
        asort($similarPackages);

        return array_keys(array_slice($similarPackages, 0, 5));
    }

    private function updateDependencies($output)
    {
        try {
            $updateCommand = $this->getApplication()->find('update');
            $this->getApplication()->resetComposer();
            $updateCommand->run(new ArrayInput(array()), $output);
        } catch (\Exception $e) {
            $this->getIO()->writeError('Could not update dependencies. Run `composer update` to see more information.');
        }
    }

    private function runDumpAutoloadCommand($output)
    {
        try {
            $command = $this->getApplication()->find('dump-autoload');
            $this->getApplication()->resetComposer();
            $command->run(new ArrayInput(array()), $output);
        } catch (\Exception $e) {
            $this->getIO()->writeError('Could not run dump-autoload.');
        }
    }

    private function hasDependencies($options)
    {
        $requires = (array) $options['require'];
        $devRequires = isset($options['require-dev']) ? (array) $options['require-dev'] : array();

        return !empty($requires) || !empty($devRequires);
    }
}
