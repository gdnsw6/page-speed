<?php declare(strict_types=1);

namespace GISL\PageSpeed\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use GISL\PageSpeed\Service\GenerateMediaFiles;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: GenerateMedia::class)]
class GenerateMediaHandler extends ScheduledTaskHandler
{

    private SystemConfigService $systemConfigService;

    private GenerateMediaFiles $generateMediaFiles;

    private $connection;

    public function __construct(
        EntityRepository $entityRepository,
        SystemConfigService $systemConfigService,
        GenerateMediaFiles $generateMediaFiles,
        Connection $connection
    )
    {
        parent::__construct($entityRepository);
        $this->systemConfigService = $systemConfigService;
        $this->generateMediaFiles = $generateMediaFiles;
        $this->connection = $connection;
    }

    public static function getHandledMessages(): iterable
    {
        return [ GenerateMedia::class ];
    }

    public function run(): void
    {

        if($this->systemConfigService->get('GISLPageSpeed.config.cronjob')) {
            $cwd = $this->str_lreplace('/'.'public/',"/",getcwd()."/");
            $data = $cwd."custom/plugins/GISLPageSpeed/src/Data";
            if(!is_dir($data)) {
                try {
                    mkdir($data);
                }
                catch(\Exception $e) {
                    
                }
            }
            if(is_dir($data)) {
                if(!file_exists($data."/scheduled.task.int")) {
                    $fsh = new Filesystem();
                    $fsh->dumpFile($data."/scheduled.task.int", "60");
                }
            }
            $interval = file_get_contents($data."/scheduled.task.int")*1;
            if($interval!=$this->systemConfigService->get('GISLPageSpeed.config.cronjobInt')) {
                $fsh = new Filesystem();
                $fsh->dumpFile($data."/scheduled.task.int", $this->systemConfigService->get('GISLPageSpeed.config.cronjobInt'));
                $sql = "UPDATE `scheduled_task` SET `run_interval` = :interval WHERE `name` = 'gisl.pagespeed.generate.media'";
                $data = [
                    'interval' => $this->systemConfigService->get('GISLPageSpeed.config.cronjobInt')*60
                ];
                $query = new RetryableQuery($this->connection, $this->connection->prepare($sql));
                $query->execute($data);
            }
            $createThumbnails = $this->generateMediaFiles->createThumbnails();
        }

    }

    private function str_lreplace($search, $replace, $subject) {
        $pos = strrpos($subject, $search);
        if($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
}