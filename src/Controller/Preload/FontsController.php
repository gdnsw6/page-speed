<?php

namespace GISL\PageSpeed\Controller\Preload;

use OpenApi\Attributes as OA;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use \DOMDocument;

#[Route(defaults: ['_routeScope' => ['api']])]
class FontsController extends AbstractController
{

  private SystemConfigService $systemConfigService;

  /**
   * SaveController constructor.
   */
  public function __construct(
    SystemConfigService $systemConfigService
  )
  {
    $this->systemConfigService = $systemConfigService;
  }

  #[Route(path: '/api/_action/gisl/pagespeed/preload/fonts/check', methods: ['GET','POST'])]
  public function check(Request $request, Context $context): JsonResponse
  {

    $urls = [];
    $salesChannelRepository = $this->container->get('sales_channel.repository');
    $criteria = new Criteria();
    $criteria->addAssociation('domains');
    $salesChannelIds = $salesChannelRepository->search($criteria, $context);
    $salesChannelId = false;
    foreach($salesChannelIds->getEntities()->getElements() as $key => $salesChannel) {
      if($salesChannelId===false) {
        if($request->headers->get("Saleschannelid")==$key) {
          foreach($salesChannel->getDomains()->getElements() as $element) {
            array_push($urls, $element->getUrl());
            $salesChannelId = true;
          }
        }
      }
    }

    if($salesChannelId===false) {
      foreach($salesChannelIds->getEntities()->getElements() as $key => $salesChannel) {
        foreach($salesChannel->getDomains()->getElements() as $element) {
          array_push($urls, $element->getUrl());
        }
      }
    }

    $css = [];

    if(count($urls)>0) {
      foreach($urls as $url) {
        $html = $this->crawl($url);
        if($html!=false) {
          if(!empty($html)) {
            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            $doc->loadHTML($html);
            $links = $doc->getElementsByTagName('link');
            foreach ($links as $link) {
              if($link->hasAttribute('rel')) {
                if($link->getAttribute('rel')=="stylesheet") {
                  array_push($css, $link->getAttribute('href'));
                }
              }
            }
          }
        }
      }
    }

    $files = [];
    if(count($css)>0) {
      $blacklist = [
        'Inter-Italic.woff2',
        'Inter-SemiBoldItalic.woff2',
        'Inter-BoldItalic.woff2'
      ];
      foreach($css as $url) {
        $urlArr = explode("?",$url);
        $content = $this->crawl($url);
        if($content!=false) {
          if(!empty($content)) {
            preg_match_all('#url\([\'"]*([^\'"\)]+)[\'"]*\)#si', $content, $matches);
            foreach ($matches[1] as $file) {
              $fileArr = explode("?",$file);
              $extensionArr = explode(".",$fileArr[0]);
              $filename = basename($file);
              if(!in_array($filename, $blacklist)) {
                if(count($extensionArr)>1) {
                  if($extensionArr[count($extensionArr)-1]=="woff2") {
                    if(!isset($files["{$urlArr[0]}"])) {
                      $files["{$urlArr[0]}"] = [];
                    }
                    array_push($files["{$urlArr[0]}"], array($fileArr[0]));
                  }
                }
              }
            }
          }
        }
      }
    }

    return new JsonResponse([
      'files' => json_encode($files)
    ]);

  }

  #[Route(path: '/api/_action/gisl/pagespeed/preload/fonts/save', methods: ['GET','POST'])]
  public function save(Request $request): JsonResponse
  {

    $value = str_replace("gislLineBreak","\n",$request->headers->get("GISLpreloadvalue"));
    $this->systemConfigService->set( 'GISLPageSpeed.config.preload', $value );

    return new JsonResponse([
      'success' => true,
      'value' => $value
    ]);

  }

  private function crawl($url) 
  {

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,10);

    $content = curl_exec($ch);
    $curl = curl_getinfo($ch);

    curl_close($ch);

    return $content;

  }

}