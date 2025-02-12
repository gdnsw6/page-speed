<?php declare(strict_types=1);

namespace GISL\PageSpeed\Listener;

use Composer\Autoload\ClassLoader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use JSMin\JSMin;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ResponseListener
{
    private $javascriptPlaceholder = '##SCRIPTPOSITION##';
    private $spacePlaceholder = '##SPACE##';
    private $environment;
    private SystemConfigService $systemConfigService;

    public function __construct(
        string $environment, 
        SystemConfigService $systemConfigService
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->environment = $environment;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse) {
            return;
        }

        if ($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            return;
        }

        if (strpos($response->headers->get('Content-Type', ''), 'text/html') === false) {
            return;
        }

        $this->minify($response);
    }

    private function resetClassLoader(): void
    {
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

    private function minify(Response $response): void
    {
        $startTime = microtime(true);
        $content = $response->getContent();
        $lengthInitialContent = mb_strlen($content, 'utf8');

        if ($lengthInitialContent === 0) {
            return;
        }

        if($this->systemConfigService->get('GISLPageSpeed.config.minify')) {
            $this->minifyHtml($content);
        }

        $response->setContent($content);
    }

    private function minifyJavascript(string $content): string {
        $this->resetClassLoader();
        $jsMin = new JSMin($content);
        return $jsMin->min();
    }

    private function minifyHtml(string &$content): void {
        $search = [
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\s+\<label/', # keep whitespace before label tags
            '/span\>\s+/', # keep whitespace after span tags
            '/\s+\<span/', # keep whitespace before span tags
            '/button\>\s+/', # keep whitespace after button tags
            '/\s+\<button/', # keep whitespace before button tags
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/', # strip whitespaces between = "'
            '/' . $this->spacePlaceholder . '/', # replace the spacePlaceholder at the end
        ];

        $replace = [
            "\n",
            "\n",
            ' ',
            '',
            ' ',
            $this->spacePlaceholder . '<label',
            'span>' . $this->spacePlaceholder,
            $this->spacePlaceholder . '<span',
            'button>' . $this->spacePlaceholder,
            $this->spacePlaceholder . '<button',
            '><',
            '$1>',
            '=$1',
            ' ',
        ];

        $content = trim(preg_replace($search, $replace, $content));
    }

    private function extractCombinedInlineScripts(string &$content): string
    {
        $scriptContents = '';
        $index = 0;
        $placeholder = $this->javascriptPlaceholder;
        if (strpos($content, '</script>') !== false) {
            $content = preg_replace_callback('#<script>(.*?)<\/script>#s', function ($matches) use (&$scriptContents, &$index, $placeholder) {
                $index++;
                $content = trim($matches[1]);

                if (!$this->str_ends_with($content, ';')) {
                    $content .= ';';
                }

                $scriptContents .= $content . PHP_EOL;
                return $index === 1 ? $placeholder : '';
            }, $content);
        }

        return $this->minifyJavascript($scriptContents);
    }

    private function minifySourceTypes(&$content): void
    {
        $search = [
            '/ type=["\']text\/javascript["\']/',
            '/ type=["\']text\/css["\']/',
        ];
        $replace = '';
        $content = preg_replace($search, $replace, $content);
    }

    private function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}
