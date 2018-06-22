<?php

namespace LifeOnScreen\LaravelLogKeeper\Services;

use Exception;
use LifeOnScreen\LaravelLogKeeper\Repos\LocalLogsRepoInterface;
use LifeOnScreen\LaravelLogKeeper\Repos\LogsRepoInterface;
use Carbon\Carbon;
use LifeOnScreen\LaravelLogKeeper\Support\LogUtil;
use Psr\Log\LoggerInterface;

class LogKeeperService
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var LogsRepoInterface
     */
    private $localRepo;

    /**
     * @var LogsRepoInterface
     */
    private $remoteRepo;

    /**
     * @var int
     */
    private $localRetentionDays;

    /**
     * @var int
     */
    private $uploadToRemoteAfterDays;

    /**
     * @var int
     */
    private $remoteRetentionDays;

    /**
     * @var int
     */
    private $remoteRetentionDaysCalculated;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return LogKeeperService
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @var Carbon
     */
    private $today;

    public function __construct($config, LogsRepoInterface $localRepo, LogsRepoInterface $remoteRepo, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->localRepo = $localRepo;
        $this->remoteRepo = $remoteRepo;
        $this->today = Carbon::today();
        $this->localRetentionDays = (int)$this->config['local_retention_days'];
        $this->remoteRetentionDays = (int)$this->config['remote_retention_days'];
        $this->remoteRetentionDaysCalculated = (int)$this->config['remote_retention_days_calculated'];
        $this->uploadToRemoteAfterDays = (int)$this->config['upload_to_remote_after_days'];
        $this->logger = $logger;
    }

    public function work()
    {
        if (!$this->config['enabled']) {
            $this->logger->warning("Log Keeper can't work because it is disabled");

            return;
        }

        $this->logger->info("Starting Laravel Log Keeper");
        $this->logger->info("Local Retention: {$this->localRetentionDays} days");
        $this->logger->info("Uploading logs older then: {$this->uploadToRemoteAfterDays} days");
        $this->logger->info("Remote Retention: {$this->remoteRetentionDays} days");
        $this->logger->info("Calculated Retention: {$this->remoteRetentionDaysCalculated} days");

        $this->localWork();

        if ($this->config['enabled_remote']) {
            $this->remoteCleanUp();
        } else {
            $this->logger->warning("Laravel Log Keeper is not enabled for remote operations");
        }
    }

    private function handleLocalCleanUp()
    {
        if ($this->localRetentionDays == 0) {
            $this->logger->info("Removing local logs is disabled.");

            return;
        }
        $logs = $this->localRepo->getCompressed();

        foreach ($logs as $log) {
            $days = LogUtil::diffInDays($log, $this->today);

            $this->logger->info("{$log} is {$days} day(s) old");
            if ($days > $this->localRetentionDays) {
                $this->logger->info("Deleting $log locally");
                $this->localRepo->delete($log);
            } else {
                $this->logger->info("Keeping {$log}");
            }

        }
    }

    private function handleRemoteUpload(int $days, string $log)
    {
        if (($days > $this->uploadToRemoteAfterDays) && (
                $days <= $this->remoteRetentionDaysCalculated ||
                $this->remoteRetentionDays == 0
            )) {
            $compressedName = "{$log}.tar.bz2";

            $this->logger->info("Compressing {$log} into {$compressedName}");

            $this->localRepo->compress($log, $compressedName);
            $content = $this->localRepo->get($compressedName);

            $this->logger->info("Uploading {$compressedName}");
            $this->remoteRepo->put($compressedName, $content);

            if ($days > $this->localRetentionDays && $this->localRetentionDays != 0) {
                $this->logger->info("Deleting $log locally");
                $this->localRepo->delete($compressedName);
            }

            return;
        }

        if (($days > $this->localRetentionDays) && ($days > $this->remoteRetentionDaysCalculated)) {
            $this->logger->info("Deleting {$log} because it is to old to be kept either local or remotely");
            $this->localRepo->delete($log);

            return;
        }

        $this->logger->info("Keeping {$log}");
    }

    private function localWork()
    {
        if ($this->config['enabled_remote']) {
            $logs = $this->localRepo->getLogs();

            foreach ($logs as $log) {

                $this->logger->info("Analysing {$log}");

                $days = LogUtil::diffInDays($log, $this->today);

                $this->logger->info("{$log} is {$days} day(s) old");

                if ($this->config['enabled_remote']) {
                    $this->handleRemoteUpload($days, $log);
                }
            }
        }
        $this->handleLocalCleanUp();

    }

    private function remoteCleanUp()
    {
        if ($this->remoteRetentionDays == 0) {
            $this->logger->info("Removing remote logs is disabled.");

            return;
        }
        $this->logger->info("Starting remote clean up");

        $logs = $this->remoteRepo->getCompressed();

        foreach ($logs as $log) {
            $days = LogUtil::diffInDays($log, $this->today);

            $this->logger->info("{$log} is {$days} day(s) old");

            if ($days > $this->remoteRetentionDaysCalculated) {
                $this->logger->info("Deleting {$log}");
                $this->remoteRepo->delete($log);
            } else {
                $this->logger->info("Keeping {$log}");
            }
        }
    }
}
