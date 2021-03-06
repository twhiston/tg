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
use twhiston\tg\Traits\CanClearCache;

/**
 * Class Init
 * @package twhiston\tg\Commands
 */
class CacheClear extends Command
{
    
    use CanClearCache;

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('tg:cache-clear')
            ->setDescription('Clear the local command caches, useful when adding a new library');
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
        $this->clearCache($output);
    }

}