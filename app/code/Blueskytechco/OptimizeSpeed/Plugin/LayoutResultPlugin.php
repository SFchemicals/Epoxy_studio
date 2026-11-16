<?php
declare(strict_types=1);

namespace Blueskytechco\OptimizeSpeed\Plugin;

use Blueskytechco\OptimizeSpeed\Observer\OptimizeSpeedHttp;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Layout;

/**
 * Runs the HTML rewrite while the page is rendered, before the full page cache
 * stores it. It used to run on controller_front_send_response_before, which also
 * fires for every FPC hit, so cached pages were re-parsed with regexes on each request.
 */
class LayoutResultPlugin
{
    public function __construct(private readonly OptimizeSpeedHttp $optimizer)
    {
    }

    public function afterRenderResult(Layout $subject, Layout $result, ResponseInterface $response): Layout
    {
        if ($response instanceof HttpResponse) {
            $this->optimizer->customResponse($response);
        }
        return $result;
    }
}
