<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SampleData\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\SampleData\Model\Dependency;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Composer\Console\Application;
use Composer\Console\ApplicationFactory;

/**
 * Command for remove Sample Data packages
 */
class SampleDataRemoveCommand extends Command
{
    private const OPTION_NO_UPDATE = 'no-update';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Dependency
     */
    private $sampleDataDependency;

    /**
     * @var ArrayInputFactory
     * @deprecated 100.1.0
     * @see \Symfony\Component\Console\Input\ArrayInput
     */
    private $arrayInputFactory;

    /**
     * @var ApplicationFactory
     */
    private $applicationFactory;

    /**
     * @param Filesystem $filesystem
     * @param Dependency $sampleDataDependency
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     */
    public function __construct(
        Filesystem $filesystem,
        Dependency $sampleDataDependency,
        ArrayInputFactory $arrayInputFactory,
        ApplicationFactory $applicationFactory
    ) {
        $this->filesystem = $filesystem;
        $this->sampleDataDependency = $sampleDataDependency;
        $this->arrayInputFactory = $arrayInputFactory;
        $this->applicationFactory = $applicationFactory;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('sampledata:remove')
            ->setDescription('Remove all sample data packages from composer.json');
        $this->addOption(
            self::OPTION_NO_UPDATE,
            null,
            InputOption::VALUE_NONE,
            'Update composer.json without executing composer update'
        );
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sampleDataPackages = $this->sampleDataDependency->getSampleDataPackages();
        if (!empty($sampleDataPackages)) {
            $baseDir = $this->filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath();
            $commonArgs = ['--working-dir' => $baseDir, '--no-interaction' => 1, '--no-progress' => 1];
            if ($input->getOption(self::OPTION_NO_UPDATE)) {
                $commonArgs['--no-update'] = 1;
            }
            $packages = array_keys($sampleDataPackages);
            $arguments = array_merge(['command' => 'remove', 'packages' => $packages], $commonArgs);
            $commandInput = new ArrayInput($arguments);

            /** @var Application $application */
            $application = $this->applicationFactory->create();
            $application->setAutoExit(false);
            $result = $application->run($commandInput, $output);
            if ($result !== 0) {
                $output->writeln('<info>' . 'There is an error during remove sample data.' . '</info>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln('<info>' . 'There is no sample data for current set of modules.' . '</info>');
        }
        return Command::SUCCESS;
    }
}
