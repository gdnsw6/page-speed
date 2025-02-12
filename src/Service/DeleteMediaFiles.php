<?php declare(strict_types=1);

namespace GISL\PageSpeed\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class DeleteMediaFiles
{

    private SystemConfigService $systemConfigService;
    
    private ?int $imageCount;
    private ?int $dirCount;
    private $cwd;

    public function __construct(
        SystemConfigService $systemConfigService
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->imageCount = 0;
        $this->dirCount = 0;
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

    public function delete(): JsonResponse
    {
        $images_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";
        $thumbnail_sizes_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/thumbs.json";
        $error_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/error.json";
        if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
            if (is_dir(getcwd()."/media/gisl_pagespeed/")||is_dir(getcwd()."/thumbnail/gisl_pagespeed/")) { 
                $this->rrmdir(getcwd()."/media/gisl_pagespeed/");
                $this->rrmdir(getcwd()."/thumbnail/gisl_pagespeed/");
                unlink($images_file);
                unlink($thumbnail_sizes_file);
                if(file_exists($error_file)) {
                    unlink($error_file);
                }
                return new JsonResponse(['success' => $this->imageCount]);
            } else {
                return new JsonResponse(['success' => "empty"]);
            }
        } else {
            if(file_exists($images_file)) {
                $images = json_decode(file_get_contents($images_file));
                if(file_exists($thumbnail_sizes_file)) {
                $thumbnail_sizes = json_decode(file_get_contents($thumbnail_sizes_file));
                if(count($images)>0) {
                    foreach($images as $image) {
                        $filename_split = explode("/",$image);
                        $filename_final = $filename_split[count($filename_split)-1];
                        $filename_final.= "gisl";
                        $filetypes = ["jpeg","jpg","png","JPG","JPEG","PNG"];
                        foreach($filetypes as $ft) {
                            $filename_final = str_replace(".".$ft."gisl","gisl",$filename_final);
                        }
                        $filename_final = $this->slugify($filename_final);
                        $filename_final = str_replace("gisl",".webp",$filename_final);
                        $directory_path = $filename_split[0]."/";
                        for($i=1;$i<count($filename_split)-1;$i++) {
                            $directory_path.= $filename_split[$i]."/";
                        }
                        $extensionArray = explode(".",$image);
                        $extension = strtolower($extensionArray[count($extensionArray)-1]);
                        foreach($thumbnail_sizes as $size) {
                            if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
                                $filename_thumbnail_final = str_replace(".webp","_".$size.".webp",$filename_final);
                                if(file_exists($this->cwd."public/thumbnail/".$directory_path.$filename_thumbnail_final)) {
                                    $this->imageCount++;
                                    unlink($this->cwd."public/thumbnail/".$directory_path.$filename_thumbnail_final);
                                }
                            }
                        }
                        if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
                            if(file_exists($this->cwd."public/media/".$directory_path.$filename_final)) {
                                $this->imageCount++;
                                unlink($this->cwd."public/media/".$directory_path.$filename_final);
                            }
                        } 
                    }
                    unlink($images_file);
                    unlink($thumbnail_sizes_file);
                    if(file_exists($error_file)) {
                        unlink($error_file);
                    }
                    return new JsonResponse(['success' => $this->imageCount]);
                }
                }
            }
            return new JsonResponse(['success' => "empty"]);
        }
        
    }

    public static function slugify($text, string $divider = '-') {
        $text_backup = $text;
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        if(!is_bool($text)) {
            $text = preg_replace('~[^-\w]+~', '', $text);
            $text = trim($text, $divider);
            $text = preg_replace('~-+~', $divider, $text);
            $text = strtolower($text);
            if (empty($text)) {
                return 'n-a-'.md5($text);
            }
            return $text;
        } else {
            return $text_backup;
        }        
    }

    public function rrmdir($dir) { 
        if (is_dir($dir)) { 
            $objects = scandir($dir);
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
                    $this->rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                    $this->dirCount++;
                } else {
                    unlink($dir. DIRECTORY_SEPARATOR .$object); 
                    $this->imageCount++;
                }
                } 
            }
            rmdir($dir); 
        } 
    }
    
}
