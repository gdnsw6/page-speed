<?php

namespace GISL\PageSpeed\Controller\Media;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use GISL\PageSpeed\Service\GenerateMediaFiles;
use GISL\PageSpeed\Service\DeleteMediaFiles;

#[Route(defaults: ['_routeScope' => ['api']])]
class UpgradeController extends AbstractController
{

  private $systemConfigService;
  private $generateMediaFiles;
  private $deleteMediaFiles;
  private $cwd;
  private $imageCount;

  /**
   * UpgradeController constructor.
   */
  public function __construct(
    SystemConfigService $systemConfigService,
    GenerateMediaFiles $generateMediaFiles,
    DeleteMediaFiles $deleteMediaFiles
  )
  {
    $this->systemConfigService = $systemConfigService;
    $this->generateMediaFiles = $generateMediaFiles;
    $this->deleteMediaFiles = $deleteMediaFiles;
    $this->imageCount = 0;
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

  #[Route(path: '/api/_action/gisl/pagespeed/media/upgrade/init', methods: ['GET','POST'])]
  public function init(Context $context): JsonResponse
  {
    $getCountWebP = $this->getCountWebP();
    if($this->imageCount==0) {
      $this->systemConfigService->set('GISLPageSpeed.config.upgradeStart',1);
      $this->systemConfigService->set('GISLPageSpeed.config.upgrade', 1);
    };
    return new JsonResponse([
      'success' => true,
      'images' => $this->imageCount
    ]);
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/upgrade/check', methods: ['GET','POST'])]
  public function check(Context $context): JsonResponse
  {
    return $this->run($context);
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/upgrade/delete', methods: ['GET','POST'])]
  public function delete(Context $context): JsonResponse
  {
    $this->systemConfigService->set('GISLPageSpeed.config.upgrade', 0);
    $delete = $this->deleteMediaFiles->delete();
    $this->systemConfigService->set('GISLPageSpeed.config.upgradeStart',1);
    $this->systemConfigService->set('GISLPageSpeed.config.upgrade', 1);
    return $delete;
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/upgrade/upgrade', methods: ['GET','POST'])]
  public function upgrade(Context $context): JsonResponse
  {
    $this->systemConfigService->set('GISLPageSpeed.config.upgradeStart',1);
    $this->systemConfigService->set('GISLPageSpeed.config.upgrade', 1);
    if(file_exists($this->cwd."custom/plugins/GISLPageSpeed/src/Data/images.json")) {
      $images = $this->run($context);
    } else {
      $images = $this->run($context,true);
    }
    $getCountWebP = $this->getCountWebP();
    return new JsonResponse([
      'success' => true,
      'images' => $this->imageCount
    ]);
  }

  public function getCountWebP() {
    $this->imageCount = 0;
    $this->countWebP($this->cwd."public/media/gisl_pagespeed/");
    $this->countWebP($this->cwd."public/thumbnail/gisl_pagespeed/");
  }

  public function countWebP($dir) { 
    if (is_dir($dir)) { 
      $objects = scandir($dir);
      foreach ($objects as $object) { 
        if ($object != "." && $object != "..") { 
          if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object)) {
            $this->countWebP($dir. DIRECTORY_SEPARATOR .$object);
          } else {
            $this->imageCount++;
          }
        } 
      }
    } 
  }

  public function run($context,$scan=false) {
    if(!extension_loaded('gd')) {
      return new JsonResponse([
        'error' => true,
        'type' => 'gd'
      ]);
    } else {
      if($scan===true) {
        $images = $this->generateMediaFiles->createThumbnails(true);
      } else {
        $this->systemConfigService->set('GISLPageSpeed.config.webpCount', 0);
        $images = $this->generateMediaFiles->createThumbnails();
      }
      if($images===true) {
        return new JsonResponse(['success' => true]);
      } elseif($images===false) {
        return new JsonResponse(['error' => true]);
      } else {
        if($scan!==true) {
          if(is_array($images)) {
            return new JsonResponse([
              "type" => $images["type"],
              "images" => $images["images"],
              "typeValue" => $images["typeValue"],
              "webpInt" => $images["webpInt"],
              "error" => $images["error"]
            ]);
          } else {
            return new JsonResponse(['success' => $images]);
          }
        } else {
          return new JsonResponse(['success' => $images]);
        }
      }
    }
  }

}