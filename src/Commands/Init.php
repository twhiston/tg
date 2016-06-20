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
use twhiston\tg\Tg;

/**
 * Class Init
 * @package twhiston\tg\Commands
 */
class Init extends Command
{

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('tg:init')
            ->setDescription('Make a file with commands in');
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
        $txClass = Tg::TGCLASS;
        $filename = getcwd() . '/' . $txClass . '.php';
        $output->writeln("<comment>  ~~~ Welcome to Tg! ~~~~ </comment>");
        $output->writeln("<comment>  " . $filename . " will be created in current dir </comment>");
        $output->writeln("<comment>  You can also include any file in your project as a tg file by including tg\RoboCommand in the command namespace </comment>");
        $output->writeln("<comment>  Add the file tgconf.yml and point to it from your TgCommands file to have argument options autoloaded </comment>");
        file_put_contents(
            $filename,
            '<?php'
            . "\n/**"
            . "\n * This is project's local console commands configuration for Tg Robo task runner."
            . "\n * Write this file the same as any Robo file"
            . "\n *"
            . "\n * @see http://robo.li/"
            . "\n */"
            . "\nclass " . $txClass . " extends \\Robo\\Tasks\n{"
            . "\n\n    const TGCONFIG = './tgconf.yml'; //Optional config file location"
            . "\n\n    // define public methods as commands"
            . "\n}"
        );
        $output->writeln("<comment>  Edit " . $filename . " to add your commands! </comment>");

    }

}