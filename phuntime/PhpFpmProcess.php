<?php

class PhpFpmProcess
{
    /**
     * On which port the process is listening
     * @var int
     */
    public const LISTEN_PORT = 9901;

    /**
     * Where is php-fpm executable located?
     */
    private string $fpmExecutablePath;

    private string $configPath;

    /**
     * @var resource|null
     */
    private $process;

    /**
     * @var resource[]
     */
    private array $pipes = [];


    public function __construct(
        string $fpmExecutablePath,
        string $configPath
    )
    {
        $this->fpmExecutablePath = $fpmExecutablePath;
        $this->configPath = $configPath;
    }

    public function start(): void
    {

        if(!file_exists($this->fpmExecutablePath)) {
            throw new RuntimeException(sprintf('Could not find PHP FPM executable! (tried: %s)', $this->fpmExecutablePath));
        }

        /**
         * no need to run process twice if running
         */
        if($this->process !== null) {
            return;
        }

        $this->log('Starting up PHP-FPM.');

        /**
         * pipes used to share logs from FPM to phuntime
         */
        $descriptors = [
            0 => ['file', 'php://stdin', 'r'],
            1 => ['file', 'php://stdout', 'w'],
            2 => ['pipe', 'w'],
        ];

        /**
         * Creates a handler FPM Process
         */
        $this->process = proc_open(
            sprintf(
                '%s  --nodaemonize --fpm-config %s',
                $this->fpmExecutablePath,
                $this->configPath
            ),
            $descriptors,
            $this->pipes
        );

        $this->log('Setting fpm process pipes non-blocking.');
        /**
         * Do not wait for logs from FPM, just continue if nothing passed
         * It must be before the while loop otherwise it would be hard to determine that process is running.
         */
        stream_set_blocking($this->pipes[2], false);


        /**
         * proc_open() only can tell if process is running, but this does not mean it can handle connections
         * so we need to look for phrase in stdout to ensure fpm is fully loaded and ready (or not) for adventure
         */
        $isReady = false;
        $readyPhraseToCatch = 'ready to handle connections';
        $failedPhraseToCatch = 'FPM initialization failed';
        $this->log('Waiting for fpm to be ready.');

        while ($isReady === false) {
            $logs = (string)$this->popFpmLogs();
            $this->logFpmLogs($logs);
            $isReady = stripos($logs, $readyPhraseToCatch) !== false;
            $initFailed = is_int(stripos($logs, $failedPhraseToCatch));

            if($initFailed) {
                throw new RuntimeException('Unable to run php-fpm!');
            }
        }

        $this->log('FPM is ready for handling requests.');
    }


    /**
     * Returns output from FPM
     * @return string|null
     */
    protected function popFpmLogs(): ?string
    {
        return stream_get_contents($this->pipes[2]);
    }

    /**
     * checks with each tick that fpm is still working.
     */
    protected function checkProcessStatus():void
    {
        if($this->process === null) {
            throw new RuntimeException('Unable to check process status as there is no process');
        }

        $status = proc_get_status($this->process);
        if(!$status['running']) {
            $this->log('php-fpm stopped running for some reason!');
            //@TODO maybe throw an exception here?
        }
    }

    public function tick(): void
    {
        $this->checkProcessStatus();
        $logs = $this->popFpmLogs();

        if($logs !== null) {
            $this->logFpmLogs($logs);
        }
    }

    protected function logFpmLogs(string $logs): void
    {
        if($logs === '') {
            return;
        }

        $this->log(sprintf('FPM stdout: %s', $logs));
    }


    private function log(string $message): void
    {
        error_log(sprintf('[phuntime]: %s', $message));
    }
}