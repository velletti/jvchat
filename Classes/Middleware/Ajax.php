<?php

namespace JV\Jvchat\Middleware;

use JVE\JvEvents\Utility\AjaxUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Class Ajax
 * @package JV\Jvchat\Middleware
 */
class Ajax implements MiddlewareInterface
{
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $handler
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidExtensionNameException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $_gp = $request->getQueryParams();
        // examples:

        if( is_array($_gp) && key_exists("eIDMW" ,$_gp ) && $_gp['eIDMW'] == 'tx_jvchat_pi1' ) {
            $GLOBALS['TSFE']->set_no_cache();


            // Initialize FE user object:
            /** @var FrontendUserAuthentication $feUserObj */

            /** @var \JV\Jvchat\Eid\Chat $chat */
            $chat = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('JV\\Jvchat\\Eid\\Chat');
            $chat->init( $GLOBALS['TSFE']->fe_user, 'utf-8' , false);
            $result  = $chat->perform();
            $status = 200 ;
            if( $result ) {
                $status = 404 ;
            }
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   // always modified
            header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
            //$result = json_encode( $output['data']) ;
            $body = new Stream('php://temp', 'rw');
            $body->write($result);
            return (new Response())
                ->withHeader('Expires',  'Mon, 26 Jul 1997 05:00:00 GMT') // Date in the past
                ->withHeader('Last-Modified',  gmdate("D, d M Y H:i:s") . " GMT")
                ->withHeader('content-type',  'text/plain; charset=utf-8')
                ->withHeader('Cache-Control',  'no-store, no-cache, must-revalidate')
                ->withBody($body)
                ->withStatus($status);
        }
        return $handler->handle($request);
    }




}
