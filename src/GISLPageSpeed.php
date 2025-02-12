<?php declare(strict_types=1);

namespace GISL\PageSpeed;

use Composer\Autoload\ClassLoader;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use GISL\PageSpeed\Service\DeleteMediaFiles;
use GISL\PageSpeed\Controller\Htaccess\ResetController;
use Doctrine\DBAL\Connection;

class GISLPageSpeed extends Plugin
{
    public const PLUGIN_NAME = 'GISLPageSpeed';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $file = __DIR__.'/../vendor/autoload.php';

        if (!is_file($file)) {
            return;
        }

        $classLoader = require_once $file;

        if ($classLoader instanceof ClassLoader) {
            $classLoader->unregister();
            $classLoader->register(false);
        }
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        $resetController = new ResetController();

        $resetHtaccess = $resetController->reset();

        if ($context->keepUserData()) {
            return;
        }
        
        $systemConfigService = $this->container->get(SystemConfigService::class);
        $deleteController = new DeleteMediaFiles($systemConfigService);

        $deleteMedia = $deleteController->delete();

        $connection = $this->container->get(Connection::class);

        $sql = "DELETE FROM `system_config` WHERE `configuration_key` LIKE '%GISLPageSpeed.config%'";
        $results = $connection->prepare($sql)->executeStatement();
        
    }

    public function deactivate(DeactivateContext $context): void
    {

        $resetController = new ResetController();
        $resetHtaccess = $resetController->reset();

    }

    public function update(UpdateContext $context): void
    {

        $systemConfigService = $this->container->get(SystemConfigService::class);
        $connection = $this->container->get(Connection::class);

        $sql = "DELETE FROM `system_config` WHERE `configuration_key` LIKE '%GISLPageSpeed.config.get%'";
        $results = $connection->prepare($sql)->executeStatement();

        $sql = "DELETE FROM `system_config` WHERE `configuration_key` = 'GISLPageSpeed.config.mediaFiles'";
        $results = $connection->prepare($sql)->executeStatement();

        $sql = "DELETE FROM `system_config` WHERE `configuration_key` = 'GISLPageSpeed.config.thumbnailSizes'";
        $results = $connection->prepare($sql)->executeStatement();

        $sql = "DELETE FROM `scheduled_task` WHERE `name` = 'pagespeed_generate_media'";
        $results = $connection->prepare($sql)->executeStatement();

        $systemConfigService->set('GISLPageSpeed.config.upgrade',0);

        $systemConfigService->set('GISLPageSpeed.config.cronjob',0);

        $systemConfigService->set('GISLPageSpeed.config.cronjobInt',60);
        
        $systemConfigService->set('GISLPageSpeed.config.webpFinish', 0);
        
        $systemConfigService->set('GISLPageSpeed.config.preload', "");

        if(empty($systemConfigService->get('GISLPageSpeed.config.email'))) {
            $systemConfigService->set('GISLPageSpeed.config.email','support@gisl.de');
        }

    }

}
