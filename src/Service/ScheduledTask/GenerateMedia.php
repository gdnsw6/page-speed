<?php declare(strict_types=1);

namespace GISL\PageSpeed\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class GenerateMedia extends ScheduledTask
{

    public static function getTaskName(): string
    {
        return 'gisl.pagespeed.generate.media';
    }

    public static function getDefaultInterval(): int
    {
        $pos = strrpos(getcwd()."/", '/'.'public/');
        if($pos !== false) {
            $cwd = substr_replace(getcwd()."/", "/", $pos, strlen('/'.'public/'));
        } else {
            $cwd = getcwd()."/";
        }
        $data = $cwd."custom/plugins/GISLPageSpeed/src/Data";
        $interval = 60;
        if(is_dir($cwd."custom/plugins/GISLPageSpeed")) {
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
        }
        return $interval*60;
    }
}