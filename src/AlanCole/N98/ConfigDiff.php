<?php

namespace AlanCole\N98;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigDiff extends AbstractMagentoCommand
{
    protected function configure()
    {
          $this->setName('config:diff')
              ->setDescription('Will diff the config tables of two connections');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
     protected function execute(InputInterface $input, OutputInterface $output)
     {
       $this->detectMagento($output);
       if ($this->initMagento()) {
          $output->writeln("Show lots of cool stuff here.");
       }
     }
}
