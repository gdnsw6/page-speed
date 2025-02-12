<?php declare(strict_types=1);

namespace GISL\PageSpeed\Service;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\SplFileInfo;
use WebPConvert\WebPConvert;
use \Exception;

class GenerateMediaFiles
{

    private RequestStack $requestStack;

    private SystemConfigService $systemConfigService;

    private $maxImageSize;

    private ?string $baseUrl;

    private ?string $fallbackBaseUrl = null;

    private $connection;

    private $mediaFiles;

    private $imagesCount;

    private $cwd;

    private $webpInt;

    private $error;

    private $error_file;

    private $classesLoaded;

    public function __construct(
        RequestStack $requestStack,
        SystemConfigService $systemConfigService,
        Connection $connection,
        ?string $baseUrl = null
    )
    {
        $this->requestStack = $requestStack;
        $this->systemConfigService = $systemConfigService;
        $this->connection = $connection;
        $this->maxImageSize = $this->setMaxImageSize();
        $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
        $this->mediaFiles = array();
        $this->imagesCount = 0;
        $this->webpInt = 0;
        $this->error = 0;
        $this->error_file = false;
        $this->classesLoaded = false;
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

    private function resetClassLoader(): void
    {
        if($this->classesLoaded===false) {

            $this->classesLoaded = true;

            $file = __DIR__.'/../../vendor/autoload.php';
            if (!is_file($file)) {
                return;
            }

            $classLoader = require_once $file;

            if ($classLoader instanceof ClassLoader) {
                $classLoader->unregister();
                $classLoader->register(false);
            }

        }
    }

    public function isIterable( $value ) {
		return ! empty( $value ) && is_iterable( $value );
	}

    public function getThumbnailSizes($scan=false) {
        if(!is_dir($this->cwd."custom/plugins/GISLPageSpeed/src/Data")) {
            try {
                mkdir($this->cwd."custom/plugins/GISLPageSpeed/src/Data");
            }
            catch(\Exception $e) {
                
            }
        }
        $json_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/thumbs.json";
        $lastCheck = $this->systemConfigService->get('GISLPageSpeed.config.thumbnailSizes');
        if(empty($lastCheck)||($lastCheck+86400)<time()||!file_exists($json_file)||$scan===true) {
            $sql = "SELECT * FROM media_thumbnail_size";
            $data = [];
            $results = $this->connection->fetchAllAssociative(
                $sql,
                $data
            );
            $json = array();
            foreach($results as $row) {
                $json[] = $row["width"];
            }
            $return = json_encode($json);
            $fsh = new Filesystem();
            if(file_exists($json_file)) {
                $fsh->remove($json_file);
            }
            $fsh->dumpFile($json_file, $return);
            $this->systemConfigService->set('GISLPageSpeed.config.thumbnailSizes',time());
        }
    }

    public function getMediaFiles($scan=false) {
        $json_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";
        if(file_exists($this->cwd."custom/plugins/GISLPageSpeed/src/Data/error.json")) {
            $this->error_file = json_decode(file_get_contents($this->cwd."custom/plugins/GISLPageSpeed/src/Data/error.json"));
        }
        $lastCheck = $this->systemConfigService->get('GISLPageSpeed.config.mediaFiles');
        if(empty($lastCheck)||($lastCheck+86400)<time()||!file_exists($json_file)||$scan===true) {
            $path = $this->cwd."public/media";
            if(is_dir($path)) {
                $this->systemConfigService->set('GISLPageSpeed.config.mediaFiles',time());
                $this->readDirs($path);
                $images = array();
                foreach($this->mediaFiles as $image) {
                    if($this->errorCheck($image)===false) {
                        $images[] = $image;
                    }
                }
                $return = json_encode($images);
                $fsh = new Filesystem();
                if(file_exists($json_file)) {
                    $fsh->remove($json_file);
                }
                $fsh->dumpFile($json_file, $return);
            }
        }
    }

    public function errorCheck($image) {
        if($this->error_file!==false) {
            foreach($this->error_file as $error) {
                if($error==$image) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function readDirs($path) {
        $dirHandle = opendir($path);
        if(!is_bool($dirHandle)) {
            while($item = readdir($dirHandle)) {
                $newPath = $path."/".$item;
                if(is_dir($newPath) && $item != '.' && $item != '..') {
                    if(strlen($item)==2||strlen($item)==10) {
                        $this->readDirs($newPath);
                    }
                } else if($item != '.' && $item != '..') {
                    $newPathArr = explode('/'.'public/',$newPath);
                    if(isset($newPathArr[1])) {
                        $checkImage = false;
                        $imageName = strtolower($newPathArr[1])."gisl";
                        if($this->systemConfigService->get('GISLPageSpeed.config.jpg')==1) {
                            $checkJPG = explode('.jpg',$imageName);
                            if(isset($checkJPG[1])) {
                                if($checkJPG[1]=="gisl") {
                                    $checkImage = true;
                                }
                            } else {
                                $checkJPG = explode('.jpeg',$imageName);
                                if(isset($checkJPG[1])) {
                                    if($checkJPG[1]=="gisl") {
                                        $checkImage = true;
                                    }
                                }
                            }
                        } 
                        if($this->systemConfigService->get('GISLPageSpeed.config.png')==1) {
                            $checkPNG = explode('.png',$imageName);
                            if(isset($checkPNG[1])) {
                                if($checkPNG[1]=="gisl") {
                                    $checkImage = true;
                                }
                            }
                        }
                        if($checkImage===true) {
                            $image = str_replace("media/","",$newPathArr[1]);
                            $this->mediaFiles["{$image}"] = $image;
                        }
                    }
                }
            }
        }
    }

    public function createThumbnails($scan=false,$skip=false,$startFrom=0) {

        $this->getThumbnailSizes($scan);
        $this->getMediaFiles($scan);

        if($scan===true) {
            $debug_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/debug.json";
            if(file_exists($debug_file)) {
                unlink($debug_file);
            }
            $images_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";
            if(file_exists($images_file)) {
                $images = json_decode(file_get_contents($images_file));
                return $images;
            }
            return 0;
        }

        $this->systemConfigService->set('GISLPageSpeed.config.webpCount', 0);

        $max_execution_time = ini_get('max_execution_time')-5;
        $start_time = microtime(true);
        
        $allowed_memory = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $allowed_memory, $matches)) {
            if($matches[2] == 'G') {
                $allowed_memory = $matches[1] * 1024 * 1024 * 1024; 
            } else if($matches[2] == 'M') {
                $allowed_memory = $matches[1] * 1024 * 1024; 
            } else if($matches[2] == 'K') {
                $allowed_memory = $matches[1] * 1024;
            }
        }

        $allowed_memory = $allowed_memory-100000;
        
        $images_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";

        $currentI = 0;
        
        if(file_exists($images_file)) {

            if($scan===false||$scan===true) {
                $reloadFile = false;
                $images = json_decode(file_get_contents($images_file));
            } else {
                $reloadFile = true;
                $images = array($scan);
            }
            
            $thumbnail_sizes_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/thumbs.json";

            if(file_exists($thumbnail_sizes_file)) {

                $thumbnail_sizes = json_decode(file_get_contents($thumbnail_sizes_file));

                $this->webpInt = (int)$this->systemConfigService->get('GISLPageSpeed.config.webpInt');

                if(count($images)>0) {

                    $debug_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/debug.json";
                    if(file_exists($debug_file)) {
                        $debug_info = json_decode(file_get_contents($debug_file));
                        $startFrom = $debug_info->start*1;
                    }

                    for($i=$startFrom;$i<count($images);$i++) {

                        $image = $images[$i];

                        if($skip===true&&$this->error>0) {
                            $this->webpInt = 0;
                        }

                        if($this->imagesCount<$this->webpInt) {

                            $now_time = microtime(true);

                            if($now_time-$start_time<$max_execution_time||$max_execution_time==-1||$max_execution_time==0) {

                                if(memory_get_usage()<$allowed_memory||$allowed_memory==-1||$allowed_memory==0) {

                                    $currentI = $i;

                                    $image = $this->getBaseUrl()."media/".$image;

                                    $image_name_array = explode("/",$image);
                                    $image_name_array_2 = explode(".",$image_name_array[count($image_name_array)-1]);
                                    $image_name_end = $image_name_array_2[0];
                                    if(count($image_name_array_2)>2) {
                                        $image_name_counter = 0;
                                        foreach($image_name_array_2 as $image_name_part) {
                                            if($image_name_counter > 0 && $image_name_counter < count($image_name_array_2)-1) {
                                                $image_name_end.= ".".$image_name_part;
                                            }
                                            $image_name_counter++;
                                        }
                                    }

                                    $extensionArray = explode(".",$image);
                                    $extension = strtolower($extensionArray[count($extensionArray)-1]);

                                    if ($extension !== 'svg') {
                                        $filename = $image;
                                        foreach($thumbnail_sizes as $size) {
                                            if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
                                                if($this->systemConfigService->get('GISLPageSpeed.config.png')&&$extension === 'png') {
                                                    if($this->imagesCount<$this->webpInt) {
                                                        $createThumb = $this->getThumbnail($filename,$size,$image_name_end,$extension,"thumbnail",false,$reloadFile,$skip);
                                                    }
                                                }
                                                if($this->systemConfigService->get('GISLPageSpeed.config.jpg')&&($extension === 'jpg' || $extension === 'jpeg')) {
                                                    if($this->imagesCount<$this->webpInt) {
                                                        $createThumb = $this->getThumbnail($filename,$size,$image_name_end,$extension,"thumbnail",false,$reloadFile,$skip);
                                                    }
                                                }
                                            }
                                        }
                                        if ($extension === 'png' || $extension === 'jpg' || $extension === 'jpeg') {
                                            if($this->systemConfigService->get('GISLPageSpeed.config.png')&&$extension === 'png') {
                                                if($this->imagesCount<$this->webpInt) {
                                                    $createThumb = $this->getThumbnail($filename,$this->maxImageSize,$image_name_end,$extension,"media",false,$reloadFile,$skip);
                                                }
                                            }
                                            if($this->systemConfigService->get('GISLPageSpeed.config.jpg')&&($extension === 'jpg' || $extension === 'jpeg')) {
                                                if($this->imagesCount<$this->webpInt) {
                                                    $createThumb = $this->getThumbnail($filename,$this->maxImageSize,$image_name_end,$extension,"media",false,$reloadFile,$skip);
                                                }
                                            }
                                        } 
                                    }

                                } else {
                                    $images = [];
                                    $fsh = new Filesystem();
                                    if(file_exists($debug_file)) {
                                        $fsh->remove($debug_file);
                                    }
                                    $fsh->dumpFile($debug_file, json_encode(array(
                                        "start" => $currentI,
                                        "image" => $image
                                    )));
                                    return array(
                                        "type" => "memory",
                                        "images" => $this->imagesCount,
                                        "webpInt" => $this->webpInt,
                                        "typeValue" => ini_get('memory_limit'),
                                        "error" => $this->error
                                    );
                                }

                            } else {
                                $images = [];
                                $fsh = new Filesystem();
                                if(file_exists($debug_file)) {
                                    $fsh->remove($debug_file);
                                }
                                $fsh->dumpFile($debug_file, json_encode(array(
                                    "start" => $currentI,
                                    "image" => $image
                                )));
                                return array(
                                    "type" => "time",
                                    "images" => $this->imagesCount,
                                    "webpInt" => $this->webpInt,
                                    "typeValue" => ini_get('max_execution_time'),
                                    "error" => $this->error
                                );
                            }

                        }

                    }
                    
                }
                
                if(isset($image)) {
                    $fsh = new Filesystem();
                    if(file_exists($debug_file)) {
                        $fsh->remove($debug_file);
                    }
                    $fsh->dumpFile($debug_file, json_encode(array(
                        "start" => $currentI,
                        "image" => $image
                    )));
                }
                return array(
                    "type" => "run",
                    "images" => $this->imagesCount,
                    "typeValue" => false,
                    "webpInt" => $this->webpInt,
                    "error" => $this->error
                );
            } else {
                return false;
            }
        } else {
            return false;
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

    public function filename($image,$width,$type) {
        $realnameArr = explode("_",$image);
        $realname = $realnameArr[0];
        if(count($realnameArr)>1) {
            for($i=1;$i<count($realnameArr);$i++) {
                $realname.= "_".$realnameArr[$i];
            }
        }

        $realname.= "gisl";
        $filetypes = ["jpeg","jpg","png","JPEG","JPG","PNG"];
        foreach($filetypes as $ft) {
            $realname = str_replace(".".$ft."gisl","gisl",$realname);
        }
        $realname = $this->slugify($realname);
        $realname = str_replace("gisl",".webp",$realname);
        if($type=="thumbnail") {
            $realname = str_replace(".webp","_".$width.".webp",$realname);
        }
        return $realname;
    }

    public function directory($image,$type) {
        $directory_split = explode($type."/",$image);
        if(count($directory_split)<2) {
            $directory_split = explode("media/",$image);
        }
        $directory = explode("/",$directory_split[count($directory_split)-1]);
        $directory_path = $directory[0]."/";
        for($i=1;$i<count($directory)-1;$i++) {
            $directory_path.= $directory[$i]."/";
        }
        return $directory_path;
    }

    public function getThumbnail($filename,$width,$realname,$filetype,$type,$twig=false,$reload=false,$skip=false) {

        $filename_system = $this->slugify($realname).".webp";

        if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
            if(!is_dir(($this->cwd."public/".$type."/"."gisl_pagespeed/"))) {
                if(is_dir($this->cwd."public/".$type)) {
                    try {
                        mkdir($this->cwd."public/".$type."/"."gisl_pagespeed/");
                        mkdir($this->cwd."public/".$type."/"."gisl_pagespeed/".$this->maxImageSize."/");
                    }
                    catch(\Exception $e) {
                        
                    }
                }
            }
            if(!is_dir(($this->cwd."public/".$type."/"."gisl_pagespeed/".$width))) {
                if(is_dir($this->cwd."public/".$type)) {
                    try {
                        mkdir($this->cwd."public/".$type."/"."gisl_pagespeed/".$width);
                    }
                    catch(\Exception $e) {
                        
                    }
                }
            }
            $image_file = $this->cwd."public/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
        } else {
            $webp_image = $this->filename($realname,$width,$type);
            $directory_path = $this->directory($filename,$type);
            $image_file = $this->cwd."public/".$type."/".$directory_path.$webp_image;
        }
        
        if(!file_exists($image_file)||$reload===true) {
            if($twig===true) {
                return $filename;
            }
            $this->webpInt = (int)$this->systemConfigService->get('GISLPageSpeed.config.webpInt');
            $webpCount = (int)$this->systemConfigService->get('GISLPageSpeed.config.webpCount');
            if($webpCount>$this->webpInt) {
                return $filename;
            } else {
                if($width!=$this->maxImageSize&&$type!="thumbnail") {
                    if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                        $image_file = $this->cwd."public/".$type."/"."gisl_pagespeed/".$this->maxImageSize."/".$realname;
                    } else {
                        $webp_image_max = $this->filename($realname,$this->maxImageSize);
                        $image_file = $this->cwd."public/".$type."/".$directory_path.$webp_image_max;
                    }
                    if(!file_exists($image_file)||$reload===true) {
                        $file_3000 = $this->createThumbnail($filename,$filename_system,$this->maxImageSize,$filetype,$type,$skip);
                    }
                }
                $return = $this->createThumbnail($filename,$filename_system,$width,$filetype,$type,$skip);
                if($return===false) {
                    if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                        $image_file = $this->getBaseUrl()."/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
                    } else {
                        $image_file = $this->getBaseUrl()."/".$type."/".$directory_path.$webp_image;
                    }
                } else {
                    return $return;
                }
            }
        } else {
            if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                $image_file = $this->getBaseUrl()."/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
            } else {
                $image_file = $this->getBaseUrl()."/".$type."/".$directory_path.$webp_image;
            }
        }
        if(isset($image_file)) {
            return $image_file;
        } else {
            return $filename;
        }
    }

    public function getImageType($file) {
        $info   = getimagesize($file);
        if(!is_bool($info)) {
            $type   = $info["mime"]."gisl";
            $checkJPG = explode('jpg',$type);
            if(isset($checkJPG[1])) {
                return "jpg";
            } else {
                $checkJPG = explode('jpeg',$type);
                if(isset($checkJPG[1])) {
                    return "jpg";
                }
            }
            $checkPNG = explode('png',$type);
            if(isset($checkPNG[1])) {
                return "png";
            }
        }
        return false;
    }

    public function createThumbnail($filename,$filename_system,$width,$filetype,$type,$skip) {
        $file_array = explode("?",getcwd().str_replace($this->getBaseUrl(),"/",$filename));
        $image_file = str_replace("//","/",$file_array[0]);
        if($type=="thumbnail") {
            $image_file = str_replace("/media"."/","/thumbnail"."/",$image_file)."gisl";
            $filetypes = ["jpeg","jpg","png","JPEG","JPG","PNG"];
            foreach($filetypes as $ft) {
                $image_file = str_replace(".".$ft."gisl","_".$width."x".$width.".".$ft,$image_file);
            }
        }
        if(!file_exists($image_file)) {
            $image_file = str_replace("//","/",$file_array[0]);
        }
        if(file_exists($image_file)) {
            if($type=="thumbnail") {
                $quality = $this->systemConfigService->get('GISLPageSpeed.config.webpQualityThumbnails');
            } else {
                $quality = $this->systemConfigService->get('GISLPageSpeed.config.webpQuality');
            }
            if (filter_var($quality, FILTER_VALIDATE_INT) === false ) {
                if($type=="thumbnail") {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQualityThumbnails',60);
                    $quality = 60;
                } else {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQuality',80);
                    $quality = 80;
                }
            }
            if($quality<0) {
                $quality = 0;
                if($type=="thumbnail") {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQualityThumbnails',0);
                } else {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQuality',0);
                }
            } else if($quality>100) {
                $quality = 100;
                if($type=="thumbnail") {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQualityThumbnails',100);
                } else {
                    $this->systemConfigService->set('GISLPageSpeed.config.webpQuality',100);
                }
            }
            
            $image_type = $this->getImageType($image_file);

            if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                $image_webp = $this->cwd."public/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
            } else {
                $filename_split = explode("/",$filename);
                $filename_final = $filename_split[count($filename_split)-1];
                $filename_final.= "gisl";
                $filetypes = ["jpeg","jpg","png","JPEG","JPG","PNG"];
                foreach($filetypes as $ft) {
                    $filename_final = str_replace(".".$ft."gisl","gisl",$filename_final);
                }
                $filename_final = $this->slugify($filename_final);
                $filename_final = str_replace("gisl",".webp",$filename_final);
                if($type=="thumbnail") {
                    $filename_final = str_replace(".webp","_".$width.".webp",$filename_final);
                }
                
                $directory_split = explode($type."/",$filename);
                if(count($directory_split)<2) {
                    $directory_split = explode("media/",$filename);
                }
                $directory = explode("/",$directory_split[count($directory_split)-1]);
                
                $directory_path = "";
                for($i=0;$i<count($directory)-1;$i++) {
                    $directory_path.= $directory[$i]."/";
                    if(!is_dir($this->cwd."public/".$type."/".$directory_path)) {
                        try {
                            mkdir($this->cwd."public/".$type."/".$directory_path);
                        }
                        catch(\Exception $e) {
                            
                        }
                    }
                }
                
                $image_webp = $this->cwd."public/".$type."/".$directory_path.$filename_final;
            }
            $webp_saved = false;

            $move_old_file = false;
            if($this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                $old_image_file = $this->cwd."public/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
                if(file_exists($old_image_file)) {
                    $move_old_file = true;
                }
            }

            if($skip===false) {
                if($move_old_file===false) {
                    if($image_type=="png") {
                        try {
                            $image = imagecreatefrompng($image_file);
                        } catch (\Exception $e) {
                            
                        }
                    } elseif($image_type=="jpg"||$image_type=="jpeg") {
                        try {
                            $image = imagecreatefromjpeg($image_file);
                        } catch (\Exception $e) {
                            
                        }
                    }
                    if(isset($image)) {
                        if(!is_bool($image)) {
                            $original_width = imagesx($image);
                            $original_height = imagesy($image);
                            $image_width = (int)$width;
                            if($width<$original_width) {
                                $height = ($width/$original_width)*$original_height;
                                $height_array = explode(".",(string)$height);
                                $height = (int)$height_array[0];
                            } else {
                                $image_width = $original_width;
                                $height = $original_height;
                            }
                            if(!$this->systemConfigService->get('GISLPageSpeed.config.exec')) {
                                if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')||$this->systemConfigService->get('GISLPageSpeed.config.old')===true) {
                                    $image = imagescale($image,$image_width,$height);
                                    if($image_type=="png") {
                                        if (!imageistruecolor($image)) {
                                            imagepalettetotruecolor($image);
                                        }
                                        if($original_height>1&&$original_width>1) {
                                            if ($this->pngAlpha($image,$original_width-1,$original_height-1)) {
                                                imagealphablending($image,false);
                                                imagesavealpha($image,true);
                                            }
                                        }
                                    }
                                    imagewebp($image, $image_webp, $quality);
                                } else {
                                    $this->resetClassLoader();
                                    $WebPConvert = new WebPConvert();
                                    $WebPConvert->convert($image_file, $image_webp, [
                                        'converters' => ['gd'],
                                        'convert' => [
                                            'quality' => $quality
                                        ],
                                    ]);
                                }
                            } else {
                                if(function_exists('exec')) {
                                    if(@exec('echo EXEC') == 'EXEC'){
                                        try {
                                            exec("cwebp -q ".$quality." -resize ".$image_width." ".$height." ".$image_file." -o ".$image_webp, $output, $returnCode);
                                        } catch (Exception $e) {
                                            $this->systemConfigService->set('GISLPageSpeed.config.exec',0);
                                        }
                                    } else {
                                        $this->systemConfigService->set('GISLPageSpeed.config.exec',0);
                                    }
                                } else {
                                    $this->systemConfigService->set('GISLPageSpeed.config.exec',0);
                                }
                            }
                            if(file_exists($image_webp)) {
                                $webp_saved = true;
                            }
                            imagedestroy($image);
                            $this->imagesCount++;
                            $webpCount = $this->systemConfigService->get('GISLPageSpeed.config.webpCount');
                            $this->systemConfigService->set('GISLPageSpeed.config.webpCount',$webpCount+1);
                        }
                    }
                } else {
                    if(!file_exists($image_webp)) {
                        rename($old_image_file,$image_webp);
                    } else {
                        unlink($old_image_file);
                    }
                    $webp_saved = true;
                    $this->imagesCount++;
                    $webpCount = $this->systemConfigService->get('GISLPageSpeed.config.webpCount');
                    $this->systemConfigService->set('GISLPageSpeed.config.webpCount',$webpCount+1);
                }
            }
            
            if($webp_saved===false) {
                if($this->systemConfigService->get('GISLPageSpeed.config.exec')) {
                    $this->systemConfigService->set('GISLPageSpeed.config.exec',0);
                } else {
                    $image_file_arr = explode($type."/",$image_file);
                    if(count($image_file_arr)<2) {
                        $image_file_arr = explode("media/",$image_file);
                    }
                    if(count($image_file_arr)>1) {
                        $image_file_end = $image_file_arr[1];
                        $file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";
                        if(file_exists($file)) {
                            $content = file_get_contents($file);
                            $content = str_replace(',"'.str_replace("/","\\/",$image_file_end).'"','',$content);
                            $content = str_replace('"'.str_replace("/","\\/",$image_file_end).'",','',$content);
                            $fsh = new Filesystem();
                            $fsh->remove($file);
                            $fsh->dumpFile($file, $content);
                        }
                        $error = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/error.json";
                        $errorUpdate = [];
                        if(file_exists($error)) {
                            $errors = json_decode(file_get_contents($error));
                            foreach($errors as $err) {
                                $errorUpdate["{$err}"] = $err;
                            };
                        }
                        $errorUpdate["{$image_file_end}"] = $image_file_end;
                        $errorUpdateFinal = [];
                        foreach($errorUpdate as $errU) {
                            $errorUpdateFinal[] = $errU;
                        }
                        $errorUpdateJSON = json_encode($errorUpdateFinal);
                        $fsh = new Filesystem();
                        $fsh->dumpFile($error, $errorUpdateJSON);
                    }
                    $this->error++;
                }
            }

            if(!$this->systemConfigService->get('GISLPageSpeed.config.upgrade')) {
                return $this->getBaseUrl()."/".$type."/"."gisl_pagespeed/".$width."/".$filename_system;
            } else {
                $filename_split = explode("/",$filename);
                $filename_final = $filename_split[count($filename_split)-1];
                $filename_final.= "gisl";
                $filetypes = ["jpeg","jpg","png","JPEG","JPG","PNG"];
                foreach($filetypes as $ft) {
                    $filename_final = str_replace(".".$ft."gisl","gisl",$filename_final);
                }
                $filename_final = $this->slugify($filename_final);
                $filename_final = str_replace("gisl",".webp",$filename_final);
                if($type=="thumbnail") {
                    $filename_final = str_replace(".webp","_".$width.".webp",$filename_final);
                };
                
                $directory_split = explode($type."/",$filename);
                if(count($directory_split)<2) {
                    $directory_split = explode("media/",$filename);
                }
                $directory = explode("/",$directory_split[count($directory_split)-1]);
                $directory_path = $directory[0]."/";
                for($i=1;$i<count($directory)-1;$i++) {
                    $directory_path.= $directory[$i]."/";
                }

                return $this->getBaseUrl()."/".$type."/".$directory_path.$filename_final;
            }
        } else {
            return false;
        }
    }

    public function pngAlpha($image,$width,$height) {
        if (imagecolortransparent($image)>=0) {
            return true;
        }
        for ($y=0;$y<$height;$y++) {
            for ($x=0;$x<$width;$x++) {
                try {
                    imagecolorat($image, $x, $y);
                } catch (Exception $e) {
                    return true;
                }
                $color = imagecolorat($image, $x, $y);
                $rgb   = imagecolorsforindex($image, $color);
                if ($rgb['alpha']>0) {
                    return true;
                }
            }
        }
        return false;
    }    

    public function setMaxImageSize()
    {
        if($this->systemConfigService->get('GISLPageSpeed.config.maxImageSize')) {
            if(is_int($this->systemConfigService->get('GISLPageSpeed.config.maxImageSize'))) {
                if(is_null($this->systemConfigService->get('GISLPageSpeed.config.maxImageSize'))) {
                    return 2000;
                } else {
                    return $this->systemConfigService->get('GISLPageSpeed.config.maxImageSize');
                }
            } else {
                return 2000;
            }
        } else {
            return 2000;
        }
    }

    private function createFallbackUrl(): string
    {
        $request = $this->requestStack->getMainRequest();
        if ($request) {
            $basePath = $request->getSchemeAndHttpHost() . $request->getBasePath();
            return rtrim($basePath, '/');
        }
        return (string) EnvironmentHelper::getVariable('APP_URL');
    }

    private function normalizeBaseUrl(?string $baseUrl): ?string
    {
        if (!$baseUrl) {
            return null;
        }
        return rtrim($baseUrl, '/');
    }

    private function getBaseUrl(): string
    {
        if (!$this->baseUrl) {
            return $this->fallbackBaseUrl ?? $this->fallbackBaseUrl = $this->createFallbackUrl();
        }
        return $this->baseUrl;
    }
    
}
