# Todo

# General

Split commands into a separate library from the runner

#  Config File parser stuff

Make it!

Should read a yml file, which is keyed by the command name, and then read in a set of arguments or whatever that can be passed to the robo commands
This should allow to simply run the command in the folder and pick up the correct config for the project

Make a robo command that actually generates a skeleton of this from the commands you have installed

It should ADDITIONALLY read custom locations for storage of possible Tg commands, so you could include them via your projects vendor dir

# Commands

- make sure including custom robo tasks still work properly across included robo files
- proper phpunit runner stuff for watcher
- git push stuff
- release stuff
- composer autoloader generator
- drush wrapper
- drupal console wrapper
- symfony command detection
- conf file generator