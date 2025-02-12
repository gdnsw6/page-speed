<?php declare(strict_types=1);

namespace GISL\PageSpeed\Service;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\SplFileInfo;

class SaveHtaccessFile
{

    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->cwd = $this->str_lreplace('/'.'public/',"/",getcwd()."/");
    }

    function str_lreplace($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);
        if($pos !== false)
        {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    public function save(): JsonResponse
    {

        $htaccess_path = getcwd()."/.htaccess";

        if(file_exists($htaccess_path)) {

            $htaccess_control = "";
                
            if($this->systemConfigService->get('GISLPageSpeed.config.gzip')) {
                $htaccess_control.= "gzip,";
            }
            if($this->systemConfigService->get('GISLPageSpeed.config.deflate')) {
                $htaccess_control.= "deflate,";
            }
            if($this->systemConfigService->get('GISLPageSpeed.config.alive')) {
                $htaccess_control.= "alive,";
            }
            if($this->systemConfigService->get('GISLPageSpeed.config.expires')) {
                $htaccess_control.= "expires,";
            }
            if($this->systemConfigService->get('GISLPageSpeed.config.control')) {
                $htaccess_control.= "control,";
            }

            $htaccess_control_active = $this->systemConfigService->get( 'GISLPageSpeed.config.changed_htaccess' );

            $return = true;

            $htaccess_control.= "active";

            if(!file_exists($htaccess_path.".original")) {
                $fs = new Filesystem();
                $fs->copy($htaccess_path, $htaccess_path.".original", true);
            }
            
            $return = $htaccess_path." - ".$htaccess_control_active."!=".$htaccess_control;
            if($htaccess_control_active!=$htaccess_control) {

                $fs = new Filesystem();
                $fs->copy($htaccess_path, $htaccess_path.".backup", true);

                $htaccess_content_file = new SplFileInfo($htaccess_path, '', '');
                $htaccess_content = $htaccess_content_file->getContents();
                $htaccess_content_start = explode("# BEGIN GISLPageSpeed",$htaccess_content);
                $htaccess_content_end = explode("# END GISLPageSpeed",$htaccess_content);
                $htaccess_content_end_final = "";
                if(isset($htaccess_content_end[1])) {
                    if(!empty($htaccess_content_end[1])) {
                        $htaccess_content_end_final = $htaccess_content_end[1];
                    }
                }
                $htaccess_content_new = $htaccess_content_start[0]."\n\n# BEGIN GISLPageSpeed\n\n";


                if($this->systemConfigService->get('GISLPageSpeed.config.gzip')) {
                    $htaccess_optimize_path = str_replace("Service","Resources/htaccess/.htaccess.gzip",realpath(dirname(__FILE__)));
                    $htaccess_optimize_content_file = new SplFileInfo($htaccess_optimize_path, '', '');
                    $htaccess_optimize_content = $htaccess_optimize_content_file->getContents();
                    $htaccess_content_new.= $htaccess_optimize_content;
                }
                if($this->systemConfigService->get('GISLPageSpeed.config.deflate')) {
                    $htaccess_optimize_path = str_replace("Service","Resources/htaccess/.htaccess.deflate",realpath(dirname(__FILE__)));
                    $htaccess_optimize_content_file = new SplFileInfo($htaccess_optimize_path, '', '');
                    $htaccess_optimize_content = $htaccess_optimize_content_file->getContents();
                    $htaccess_content_new.= $htaccess_optimize_content;
                }
                if($this->systemConfigService->get('GISLPageSpeed.config.alive')) {
                    $htaccess_optimize_path = str_replace("Service","Resources/htaccess/.htaccess.alive",realpath(dirname(__FILE__)));
                    $htaccess_optimize_content_file = new SplFileInfo($htaccess_optimize_path, '', '');
                    $htaccess_optimize_content = $htaccess_optimize_content_file->getContents();
                    $htaccess_content_new.= $htaccess_optimize_content;
                }
                if($this->systemConfigService->get('GISLPageSpeed.config.expires')) {
                    $htaccess_optimize_path = str_replace("Service","Resources/htaccess/.htaccess.expires",realpath(dirname(__FILE__)));
                    $htaccess_optimize_content_file = new SplFileInfo($htaccess_optimize_path, '', '');
                    $htaccess_optimize_content = $htaccess_optimize_content_file->getContents();
                    $htaccess_content_new.= $htaccess_optimize_content;
                }
                if($this->systemConfigService->get('GISLPageSpeed.config.control')) {
                    $htaccess_optimize_path = str_replace("Service","Resources/htaccess/.htaccess.control",realpath(dirname(__FILE__)));
                    $htaccess_optimize_content_file = new SplFileInfo($htaccess_optimize_path, '', '');
                    $htaccess_optimize_content = $htaccess_optimize_content_file->getContents();
                    $htaccess_content_new.= $htaccess_optimize_content;
                }
                $fs->dumpFile($htaccess_path, $htaccess_content_new."\n\n# END GISLPageSpeed".$htaccess_content_end_final);

            }

            $this->systemConfigService->set('GISLPageSpeed.config.changed_htaccess', $htaccess_control);

            return new JsonResponse(['success' => $return]);

        } else {

            return new JsonResponse(['error' => true]);

        }

            
    }

}
