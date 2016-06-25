<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:54
 */

namespace twhiston\tg\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use twhiston\tg\Tasks\TgTasks;

/**
 * Class Init
 * @package twhiston\tg\Commands
 */
class Conf extends Command
{

    /**
     *
     */
    const TAB = "    ";

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('tg:conf')
            ->setDescription('Make a configuration file');
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
        $txClass = TgTasks::TGCONFIG;
        $filename = getcwd() . '/' . $txClass . '.yml';
        $output->writeln("<comment>  ~~~ Welcome to Tg! ~~~~ </comment>");
        $output->writeln("<comment>  " . $filename . " will be created in current dir </comment>");
        $output->writeln("<comment>  Add arguments to this config file to have them automatically merge in when running a command </comment>");

        //Build the list of commands and boilerplate them
        xdebug_break();

        $commands = $this->getApplication()->all();

        $fileString = '';
        $namespace = '';
        foreach ($commands as $command) {
            $def = $command->getDefinition();
            $args = $def->getArguments();
            $opts = $def->getOptions();

            $tokens = explode(':', $command->getName());
            if ($tokens[0] !== $namespace) {
                $namespace = $tokens[0];
                $fileString .= $namespace . ":\n";
            }

            if (array_key_exists(1, $tokens)) {
                $fileString .= Conf::TAB . $tokens[1] . ":\n";
                $tabbing = Conf::TAB . Conf::TAB;
            } else {
                $tabbing = Conf::TAB;
            }
            $this->generateArray('args', $args, $fileString, $tabbing);
            $this->generateArray('options', $opts, $fileString, $tabbing);
            $fileString .= $tabbing . "pass: \n";
        }

        file_put_contents(
            $filename,
            $fileString
        );
        $output->writeln("<comment>  Edit " . $filename . " to add your config! </comment>");

    }

    /**
     * @param $id
     * @param $args
     * @param $fileString
     * @param $tabbing
     */
    protected function generateArray($id, $args, &$fileString, &$tabbing)
    {
        if (is_array($args)) {
            $oldtab = $tabbing;
            $fileString .= $tabbing . $id . ":\n";
            $tabbing = Conf::TAB . $tabbing;
            foreach ($args as $arg) {
                $fileString .= $tabbing . $arg->getName() . ":\n";
            }
            $tabbing = $oldtab;
        }
    }

}