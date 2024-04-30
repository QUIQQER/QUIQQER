<?php

/**
 * This file contains the \QUI\Projects\RestProvider
 */

namespace QUI\Projects;

use Psr\Http\Message\ResponseInterface as ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use QUI;
use QUI\Exception;
use QUI\REST\Server;

use function json_encode;

/**
 * Class RestProvider
 */
class RestProvider implements QUI\REST\ProviderInterface
{
    /**
     * @param Server $Server
     * @throws Exception
     */
    public function register(Server $Server): void
    {
        $Slim = $Server->getSlim();

        $Slim->get(
            '/projects/{project}/{lang}/{id}',
            function (RequestInterface $Request, ResponseInterface $Response, $args) {
                $project = $Request->getAttribute('project');
                $lang = $Request->getAttribute('lang');
                $id = $Request->getAttribute('id');

                $Project = QUI::getProject($project, $lang);
                $Site = $Project->get($id);

                return $Response->withStatus(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->write(json_encode($Site->getAttributes()));
            }
        );
    }

    /**
     * Get file containing OpenApi definition for this API.
     *
     * @return string|false - Absolute file path or false if no definition exists
     */
    public function getOpenApiDefinitionFile(): bool|string
    {
        try {
            $dir = QUI::getPackage('quiqqer/quiqqer')->getDir();
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return false;
        }

        return $dir . 'lib/QUI/OpenApiDefinition.json';
    }

    /**
     * Get unique internal API name.
     *
     * This is required for requesting specific data about an API (i.e. OpenApi definition).
     *
     * @return string - Only letters; no other characters!
     */
    public function getName(): string
    {
        return 'QuiqqerProjects';
    }

    /**
     * Get title of this API.
     *
     * @param QUI\Locale|null $Locale (optional)
     * @return string
     */
    public function getTitle(QUI\Locale $Locale = null): string
    {
        if (empty($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'RestProvider.Projects.title');
    }
}
