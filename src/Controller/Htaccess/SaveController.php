<?php

namespace GISL\PageSpeed\Controller\Htaccess;

use OpenApi\Attributes as OA;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Routing\Annotation\Route;
use GISL\PageSpeed\Service\SaveHtaccessFile;

#[Route(defaults: ['_routeScope' => ['api']])]
class SaveController extends AbstractController
{

  private SystemConfigService $systemConfigService;
  private SaveHtaccessFile $saveHtaccessFile;

  /**
   * SaveController constructor.
   */
  public function __construct(
    SystemConfigService $systemConfigService,
    SaveHtaccessFile $saveHtaccessFile
  )
  {
    $this->systemConfigService = $systemConfigService;
    $this->saveHtaccessFile = $saveHtaccessFile;
  }

  #[Route(path: '/api/_action/gisl/pagespeed/htaccess/run', methods: ['GET','POST'])]
  public function check(): JsonResponse
  {
    
    $this->systemConfigService->set( 'GISLPageSpeed.config.changed_htaccess', "reset" );
    return $this->saveHtaccessFile->save();
     
  }

}