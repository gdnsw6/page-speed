<?php

namespace GISL\PageSpeed\Controller\Htaccess;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class ResetController extends AbstractController
{

  function str_lreplace($search, $replace, $subject)
  {
    $pos = strrpos($subject, $search);
    if($pos !== false)
    {
      $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
  }

  public function reset()
  {

    $cwd = $this->str_lreplace('/'.'public/',"/",getcwd()."/");
    
    $htaccess_path = $cwd."public/.htaccess";

    if(file_exists($htaccess_path)) {

      if(file_exists($htaccess_path.".original")) {

        $fs = new Filesystem();
        $fs->copy($htaccess_path.".original", $htaccess_path, true);

      }

    }
     
  }

}