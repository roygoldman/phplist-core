<?php
declare(strict_types=1);

namespace PhpList\PhpList4\Composer;

/**
 * This class finds the Symfony bundles registered by phpList modules.
 *
 * @author Oliver Klee <oliver@phplist.com>
 */
class ModuleBundleFinder
{
    /**
     * @var string
     */
    const YAML_COMMENT = '# This file is autogenerated. Please do not edit.';

    /**
     * @var PackageRepository
     */
    private $packageRepository = null;

    /**
     * @param PackageRepository $repository
     *
     * @return void
     */
    public function injectPackageRepository(PackageRepository $repository)
    {
        $this->packageRepository = $repository;
    }

    /**
     * Finds the bundles class in all installed modules.
     *
     * @return string[][] class names of the bundles of all installed phpList modules:
     * ['module package name' => ['bundle class name 1', 'bundle class name 2']]
     *
     * @throws \InvalidArgumentException
     */
    public function findBundleClasses(): array
    {
        /** @var string[][] $bundleSets */
        $bundleSets = [];

        $modules = $this->packageRepository->findModules();
        foreach ($modules as $module) {
            $extra = $module->getExtra();
            $this->validateBundlesSectionInExtra($extra);
            if (empty($extra['phplist/phplist4-core']['bundles'])) {
                continue;
            }

            $bundleSets[$module->getName()] = $extra['phplist/phplist4-core']['bundles'];
        }

        return $bundleSets;
    }

    /**
     * Validates the bundles configuration in the "extra" section of the composer.json of a module.
     *
     * @param array $extra
     *
     * @return void
     *
     * @throws \InvalidArgumentException if $extra has an
     */
    private function validateBundlesSectionInExtra(array $extra)
    {
        if (!isset($extra['phplist/phplist4-core'])) {
            return;
        }
        if (!is_array($extra['phplist/phplist4-core'])) {
            throw new \InvalidArgumentException(
                'The extras.phplist/phplist4-core" section in the composer.json must be an array.',
                1505411436144
            );
        }

        if (!isset($extra['phplist/phplist4-core']['bundles'])) {
            return;
        }
        if (!is_array($extra['phplist/phplist4-core']['bundles'])) {
            throw new \InvalidArgumentException(
                'The extras.phplist/phplist4-core.bundles section in the composer.json must be an array.',
                1505411665146
            );
        }

        /** @var array $bundleExtras */
        $bundleExtras = $extra['phplist/phplist4-core']['bundles'];
        foreach ($bundleExtras as $key => $bundleName) {
            if (!is_string($bundleName)) {
                throw new \InvalidArgumentException(
                    'The extras.phplist/phplist4-core.bundles. ' . $key .
                    '" section in the composer.json must be a string.',
                    1505412184038
                );
            }
        }
    }

    /**
     * Builds the YAML configuration file contents for the registered bundles in all modules.
     *
     * @return string
     */
    public function createBundleConfigurationYaml(): string
    {
        // Set linefeed / newline character
        $lf = chr(10);
        $yaml = self::YAML_COMMENT . $lf;

        $bundleClassSets = $this->findBundleClasses();
        if (count($bundleClassSets) === 0) {
            return $yaml . '{  }';
        }

        /** @var string[][] $bundleClasses */
        foreach ($bundleClassSets as $packageName => $bundleClasses) {
            $yaml .= $packageName . ':' . $lf;
            foreach ($bundleClasses as $bundleClass) {
                $yaml .= '    - ' . $bundleClass . $lf;
            }
        }

        return $yaml;
    }
}