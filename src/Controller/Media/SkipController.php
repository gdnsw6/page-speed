<?php

namespace GISL\PageSpeed\Controller\Media;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use GISL\PageSpeed\Service\GenerateMediaFiles;

#[Route(defaults: ['_routeScope' => ['api']])]
class SkipController extends AbstractController
{
  
  /**
   * SkipController constructor.
   */
  public function __construct(
    GenerateMediaFiles $generateMediaFiles
  )
  {
    $this->generateMediaFiles = $generateMediaFiles;
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/skip/run', methods: ['GET','POST'])]
  public function check(): JsonResponse
  {
    $skip = $this->generateMediaFiles->createThumbnails(false,true);
    return new JsonResponse([
      'status' => $skip
    ]);
  }

}