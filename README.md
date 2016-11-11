# tg

v 0.1.6

------
A command line runner for Robo

## Master
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/twhiston/tg/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/twhiston/tg/?branch=master)

## Install

Download a [phar](https://bitbucket.org/twhiston/tg/downloads/tg.phar)

or clone the source and run 
```
php ./src/tg phar:build
```

then
```
sudo chmod 700 tg.phar
//If you want to install it globally run
mv tg.phar /usr/local/bin/tg
```

**Tg requires the ability to write to your computer** you may need to set `phar.readonly = Off` in your local php.ini

## Update
`tg tg:self-update`

## Why tg?

Why not? in truth because t and g are well placed to type on lots of keyboard layouts,
but in particular on Workman, which you should check out http://www.workmanlayout.com/blog/

But lets pretend it stands for something cool like 'Task Go', 'Terminator Genisys', 'Torture Garden' or 'Throbbing Gristle'

## What does it do?

Robo is a super cool PHP task runner
http://robo.li

But... you cant easily create libraries of commands, that you might use between lots of projects.
If you are working on tooling for a development team who frequently work with the same technologies (for example Drupal)
then you are going to end up re-using a lot of commands. Even if you are not then you might still want a command that you can use in every project
to watch your files for changes and trigger your unit tests when they do.

This is where tg comes in, tg allows you to do 2 additional things

## Autoload Robo Commands

Tg lets you autoload libraries of commands that you can run. These can be added on a per project basis and should be required in your projects composer.json file, usually with --dev flag
then when you run tg in your project folder those commands will be available.
Run `tg list` to see available commands from the cwd.

### Creating an autoloading command

If you place a class that extends Robo\Tasks in a Namespace that contains `tg\RoboCommand` inside a folder called `RoboCommand` it can be autodetected and made available for use.
If you place a class that extends Symfony\Component\Console\Command\Command in a Namespace that contains `tg\Command` inside a folder called `Command` it can be autodetected and made available for use.
This means you can create a library of common commands, require it via composer in your project, and then call them immediately from tg

### Project specific command file

Like Robo you can also create a TgCommands.php file in your project root and these project specific commands will also be made available
You can generate the boilerplate file for these commands by running `tg tg:init`

## Config Files

Quite often you have commands that are very similar between projects, or that require a lot of arguments to run, which are a pain to type. Tg can help you!
If you run `tg tg:conf` a file in your project root called tg.yml will be created with the skeleton for all available commands
```
phpunit:
    watch:
        args:
            path:  ./tests/
            unitArgs:
        options:
        pass: "--configuration=phpunit.xml.dist --coverage=clover"
```
Command line parameters are merged with the config file before execution and always over-ride config file parameters

## Pass Through Arguments

You can pass through arguments to commands invoked by tg, just like robo simply add -- before your pass through arguments

ie
If your command has the method signature `public function watch($path,$unitArgs)` where `$unitArgs` are the arguments to pass through to phpunit you would call the command like this
`tg watch /path/to/watch unitArgs -- --configuration=phpunit.xml.dist -- coverage=clover`

## Command Caching
When tg is run in a folder where tg commands reside in a local vendor dir it will create an info folder ./.tg
This folder and the cache file it holds are created to speed up execution of tg so the vendor autodetection does not need to be done on every run, however this also means that if you require new tg libraries you will need to delete these files
You can do so by running `tg tg:cache-clear`

## LibDev mode
If you are writing a library for tg its pretty useful to have it autodetect your cwd src folder and not cache anything.
Turn it on with: `tg dev:libdev` or `tg dev:libdev` and off with `tg dev:libdev false`
Libdev mode is on a cwd basis, and the value is stored in the .tg folder
you can check the cwd's libdev state with `tg:libdev-state`