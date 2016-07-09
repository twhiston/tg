<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:54
 */

namespace twhiston\tg\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use twhiston\tg\Tg;

/**
 * Class Init
 * @package twhiston\tg\Commands
 */
class SymfonyTest extends Command
{

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('ctest:test')
            ->setDescription('Make a test');
    }


    /**
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return null
     *
     * Run the command and create a new TxCommands.php file in the project root
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Testing");
    }

}