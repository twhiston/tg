# tg

A command line runner for Robo

## Why tg?

Why not? in truth because t and g are well placed to type on lots of keyboard layouts,
but in particular on Workman, which you should check out http://www.workmanlayout.com/blog/

But lets pretend it stands for something cool like 'Task Go', or 'Throbbing Gristle'

## What does it do?

Robo is a super cool PHP task runner
http://robo.li

But... you cant easily create libraries of commands, that you might use between lots of projects.
If you are working on tooling for a development team who frequently work with the same technologies (for example Drupal)
then you are going to end up re-using a lot of commands. Even if you are not then you might still want a command that you can use in every project
to watch your files for changes and trigger your unit tests when they do.

This is where tg comes in, tg allows you to do 2 things

## Autoload Robo Commands

If you place a command in a Namespace that contains `tg\RoboCommand` it will be autodetected and made available for use.
This means you can create a library of common commands, require it via composer in your project, and then call them immediately from tg

Like Robo you can also create a TgCommands.php file in your project root and these project specific commands will also be made available

## TODO 

### Config Files

You might have commands that need config, folder names and arguments, but mostly these arguments dont change between projects and they are a pain to type.
Simply create a file in your project root called tg.yml and enter your arguments. They should be in the form of

```
phpunit:
    watch: ['arg','arg']
```
