<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:54
 */

namespace twhiston\tx\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Init
 * @package twhiston\tx\Commands
 */
class Init extends Command
{

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('tx:init')
            ->setDescription('Make a file with commands in')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Who do you want to greet?'
            )
            ->addOption(
                'yell',
                null,
                InputOption::VALUE_NONE,
                'If set, the task will yell in uppercase letters'
            );
    }


    /**
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return null
     * Run the command and create a new TxCommands.php file in the project root
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $txClass = 'TxCommands';
        $filename = $txClass.'.php';
        $output->writeln("<comment>  ~~~ Welcome to Tx! ~~~~ </comment>");
        $output->writeln("<comment>  ".$filename." will be created in current dir </comment>");
        $output->writeln("<comment>  You can also include any file in your project as a tx file by adding in the namespace \\tx\\Commands </comment>");
        file_put_contents(
            $filename,
            '<?php'
            . "\n/**"
            . "\n * This is project's console commands configuration for Tx task runner."
            . "\n * You can use Robo commands in your command configuration"
            . "\n *"
            . "\n * @see http://robo.li/"
            . "\n */"
            . "\nclass " . $txClass . " extends \\Robo\\Tasks\n{\n    // define public methods as commands\n}"
        );
        $output->writeln("<comment>  Edit ".$filename."to add your commands! </comment>");

    }

}