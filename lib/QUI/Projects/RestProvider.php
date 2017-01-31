<?php

/**
 * This file contains the \QUI\Projects\RestProvider
 */

namespace QUI\Projects;

use QUI;
use QUI\REST\Server;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Http\Message\ResponseInterface as ResponseInterface;

/**
 * Class RestProvider
 *
 * @package QUI\Projects
 */
class RestProvider implements QUI\REST\ProviderInterface
{
    /**
     * @param Server $Server
     */
    public function register(Server $Server)
    {
        $Slim = $Server->getSlim();

        $Slim->get(
            '/projects/{project}/{lang}/{id}',
            function (RequestInterface $Request, ResponseInterface $Response, $args) {
                $project = $Request->getAttribute('project');
                $lang    = $Request->getAttribute('lang');
                $id      = $Request->getAttribute('id');

                $Project = QUI::getProject($project, $lang);
                $Site    = $Project->get($id);

                return $Response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($Site->getAttributes()));
            }
        );
    }
}
