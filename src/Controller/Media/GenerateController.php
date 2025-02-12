<?php

namespace GISL\PageSpeed\Controller\Media;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use GISL\PageSpeed\Service\GenerateMediaFiles;

#[Route(defaults: ['_routeScope' => ['api']])]
class GenerateController extends AbstractController
{

  private $systemConfigService;
  private $generateMediaFiles;
  private $cwd;

  /**
   * GenerateController constructor.
   */
  public function __construct(
    SystemConfigService $systemConfigService,
    GenerateMediaFiles $generateMediaFiles
  )
  {
    $this->systemConfigService = $systemConfigService;
    $this->generateMediaFiles = $generateMediaFiles;
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

  #[Route(path: '/api/_action/gisl/pagespeed/media/generate/run', methods: ['GET','POST'])]
  public function check(Context $context): JsonResponse
  {
   
    return $this->run($context);
     
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/generate/all', methods: ['GET','POST'])]
  public function all(Context $context): JsonResponse
  {
   
    return $this->run($context);
     
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/generate/scan', methods: ['GET','POST'])]
  public function scan(Context $context): JsonResponse
  {
   
    $run = $this->run($context,true);
    $images_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/files.json";
    $images = json_decode(file_get_contents($images_file));
    $images_count = count($images);
    $thumbnail_sizes_file = $this->cwd."custom/plugins/GISLPageSpeed/src/Data/thumbs.json";
    if(file_exists($thumbnail_sizes_file)) {
      $thumbnail_sizes = json_decode(file_get_contents($thumbnail_sizes_file));
      $images_count = ($images_count*count($thumbnail_sizes))+$images_count;
    }
    if($images_count!=0) {
      $this->systemConfigService->set('GISLPageSpeed.config.webpFinish', 0);
    }
    return new JsonResponse([
      'images' => $images_count
    ]);
     
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/generate/reload', methods: ['GET','POST'])]
  public function reload(Request $request, Context $context): JsonResponse
  {
    $imageURL = $request->headers->get("Imageurl");
    if(!empty($imageURL)) {
      $image = $this->generateMediaFiles->createThumbnails($imageURL);
      return new JsonResponse([
        'status' => $image
      ]);
    } else {
      return new JsonResponse([
        'error' => true
      ]);
    }
     
  }

  public function run($context,$scan=false) {
    if(!extension_loaded('gd')) {
      return new JsonResponse(['error' => true]);
    } else {
      $this->systemConfigService->set('GISLPageSpeed.config.webpCount', 0);
      $images = $this->generateMediaFiles->createThumbnails($scan);
      if($images===true||$scan===true) {
        return new JsonResponse(['success' => true]);
      } elseif($images===false) {
        return new JsonResponse(['error' => true]);
      } else {
        if(is_array($images)) {
          if($images["images"]!=$images["webpInt"]&&$scan==false) {
            $this->systemConfigService->set('GISLPageSpeed.config.webpFinish', 1);
          }
          return new JsonResponse([
            'success' => $images["type"],
            'images' => $images["images"],
            'typeValue' => $images["typeValue"],
            "webpInt" => $images["webpInt"],
            'error' => $images["error"]
          ]);
        } else {
          return new JsonResponse([
            'success' => $images
          ]);
        }
      }
    }
  }

}