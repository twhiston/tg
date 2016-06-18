<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 22:58
 */

namespace twhiston\tg\RoboCommand;


class Dev extends \Robo\Tasks
{

    /**
     * Add a change to the changelog for the current version number
     */
    public function change()
    {
        $version = $this->getVersion();
        $this->doChange($version);
    }

    protected function getVersion()
    {
        $path = __DIR__ . '/.semver';
        return $this->taskSemVer($path)->__toString();
    }

    protected function doChange($version)
    {

        $this->taskChangelog()
            ->version($version)
            ->askForChanges()
            ->run();
    }

    /**
     * bump the version number and add changes to the log
     * @throws \Robo\Exception\TaskException
     */
    public function bump()
    {
        $path = __DIR__ . '/.semver';
        if (!file_exists($path)) {
            $this->taskWriteToFile($path)->lines([
                "---",
                ":major: 0",
                ":minor: 1",
                ":patch: 0",
                ":special: ''",
                ":metadata: ''"
            ])->run();
        }
        $level = $this->askDefault("Update Type: PATCH/minor/major", 'patch');
        $result = $this->taskSemVer($path)
            ->increment($level)
            ->run();
        $ver = $result->getMessage();
        $this->doChange($ver);
    }

}