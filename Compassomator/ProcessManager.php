<?php

namespace Asoc\CompassomatorBundle\Compassomator;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class ProcessManager
{
    /**
     * @var \Symfony\Component\Finder\Finder
     */
    private $finder;
    /**
     * @var string
     */
    private $runDir;

    public function __construct($runDir)
    {
        $this->runDir = $runDir;
        $this->finder = new Finder();

        if (!is_dir($this->runDir)) {
            mkdir($this->runDir, 0777, true);
        }
    }

    /**
     * @return string
     */
    public function getRunDir()
    {
        return $this->runDir;
    }

    public function run(Process $process, $name)
    {
        $cmd = $process->getCommandLine();
        $pid = $this->runCommand($cmd, $name);

        // the symfony process builder creates a commandline where every part is put into single quotes
        // we extract the first part here, which is usually the actual command or program that is executed
        // this should return something like: ruby or php
        preg_match("'\w+'", $cmd, $matches);
        $commandName = $matches[0];

        // create a file where the lines are as following
        // 1: PID
        // 2: process name (ie. the actual command)
        file_put_contents($this->getPidFile($name), sprintf("%d\n%s", $pid, $commandName));

        return $pid;
    }

    public function isRunning($name)
    {
        $pidFile = $this->getPidFile($name);

        return $this->isRunningByPidFile($pidFile);
    }

    /**
     * @param $name
     *
     * @return int
     */
    public function kill($name)
    {
        if (!$this->isRunning($name)) {
            return 0;
        }

        $pidFile = $this->getPidFile($name);
        $pid     = $this->killByPidFile($pidFile);

        return $pid;
    }

    /**
     * @param $pidFile
     *
     * @return int
     */
    public function killByPidFile($pidFile)
    {
        if (($pid = $this->isRunningByPidFile($pidFile)) === false) {
            return false;
        }

        $cmd = sprintf('kill -9 %d > /dev/null 2>&1', $pid);
        shell_exec($cmd);

        unlink($pidFile);

        return $pid;
    }

    /**
     * @return array
     */
    public function killForgotten()
    {
        $remainingPids = $this->finder
            ->files()
            ->in($this->runDir)
            ->name('*.pid');

        $pids = array();
        foreach ($remainingPids as $pidFile) {
            if (($pid = $this->killByPidFile($pidFile)) !== false) {
                $pids[] = $pid;
            }
        }

        return $pids;
    }

    /**
     * @param $name
     *
     * @return string
     */
    public function getLogFile($name)
    {
        return sprintf('%s/%s.log', $this->runDir, $name);
    }

    /**
     * @param $name
     *
     * @return string
     */
    public function getPidFile($name)
    {
        return sprintf('%s/%s.pid', $this->runDir, $name);
    }

    /**
     * @param string $cmd  Full commandline to be executed
     * @param string $name Name (will be used for eg. the log file)
     *
     * @return string output of the command
     */
    private function runCommand($cmd, $name)
    {
        // wrap commandline so we can extract the PID and redirect the output to the log file
        $cmd = sprintf('%s > %s & printf "%%u" $!', $cmd, $this->getLogFile($name));

        return shell_exec($cmd);
    }

    /**
     * We return the PID here so we don't have to read the file multiple times just for the PID
     *
     * @param string $pidFile Path to the PID file
     *
     * @return bool|int false if the process is not running/doesn't match, the PID otherwise
     */
    private function isRunningByPidFile($pidFile)
    {
        if (!file_exists($pidFile)) {
            return false;
        }

        $info        = file($pidFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $pid         = $info[0];
        $commandName = $info[1];

        // we could also check for exit code
        $result = trim(shell_exec(sprintf('ps -p %d -o comm=', $pid)));
        if (strlen($result) !== 0 && $result === $commandName) {
            return intval($pid);
        }

        return false;
    }
}