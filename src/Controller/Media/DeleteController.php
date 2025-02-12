<?php

namespace GISL\PageSpeed\Controller\Media;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use GISL\PageSpeed\Service\DeleteMediaFiles;

#[Route(defaults: ['_routeScope' => ['api']])]
class DeleteController extends AbstractController
{

  private $deleteMediaFiles;

  

  /**
   * DeleteController constructor.
   */
  public function __construct(
    deleteMediaFiles $deleteMediaFiles
  )
  {
    $this->deleteMediaFiles = $deleteMediaFiles;
  }

  #[Route(path: '/api/_action/gisl/pagespeed/media/delete/run', methods: ['GET','POST'])]
  public function check(): JsonResponse
  {
    return $this->deleteMediaFiles->delete();
  }

}