<?php

/**
 * This file contains \QUI\Mail\Template
 */

namespace QUI\Mail;

use Html2Text\Html2Text;
use QUI;
use QUI\Projects\Project;

use function file_exists;

/**
 * Mail Template
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Template extends QUI\QDOM
{
    public function __construct(array $params = [])
    {
        $this->setAttributes([
            'body' => '',
            'Project' => false,
            'TplMain' => 'mails/main.html',
            'TplMeta' => 'mails/meta.html',
            'TplHeader' => 'mails/header.html',
            'TplBody' => 'mails/body.html',
            'TplFooter' => 'mails/footer.html'
        ]);

        $this->setAttributes($params);
    }

    /**
     * Return the complete mail as text, without html
     */
    public function getText(): string
    {
        $Html2Text = new Html2Text($this->getHTML());

        return $Html2Text->getText();
    }

    /**
     * Return the complete mail template as HTML
     */
    public function getHTML(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign($this->getAttributes());
        $Engine->assign('mailBody', $this->getAttribute('body'));

        // get project logo
        $Project = $this->getProject();
        $Logo = null;

        if ($Project->getConfig('emailLogo')) {
            try {
                $Logo = QUI\Projects\Media\Utils::getImageByUrl(
                    $Project->getConfig('emailLogo')
                );
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        if (!$Logo) {
            $Logo = $Project->getMedia()->getLogoImage();
        }

        $Engine->assign([
            'Logo' => $Logo,
            'lang' => $Project->getLang()
        ]);

        $main = $Engine->fetch($this->getMainTemplate());
        $meta = $Engine->fetch($this->getMetaTemplate());
        $header = $Engine->fetch($this->getHeaderTemplate());
        $body = $Engine->fetch($this->getBodyTemplate());
        $footer = $Engine->fetch($this->getFooterTemplate());

        $mailBody = str_replace([
            '[[templateMeta]]',
            '[[templateHeader]]',
            '[[templateBody]]',
            '[[templateFooter]]'
        ], [
            $meta,
            $header,
            $body,
            $footer
        ], $main);

        return $mailBody;
    }

    public function getProject(): Project
    {
        if ($this->getAttribute('Project')) {
            return $this->getAttribute('Project');
        }

        return QUI::getProjectManager()->get();
    }

    /**
     * Return the meta template path
     */
    public function getMainTemplate(): string
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR . 'templates/mail/main.html';

        // exit project template?
        $template = $this->getAttribute('TplMain');
        $projectDir = USR_DIR . $Project->getName() . '/lib/';

        if (file_exists($projectDir . $template)) {
            return $projectDir . $template;
        }

        $tplPath = OPT_DIR . $Project->getAttribute('template') . '/';

        // exist template in opt?
        if (file_exists($tplPath . $template)) {
            return $tplPath . $template;
        }

        return $standardTpl;
    }

    /**
     * Return the meta template path
     */
    public function getMetaTemplate(): string
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR . 'templates/mail/meta.html';

        // exit project template?
        $template = $this->getAttribute('TplMeta');
        $projectDir = USR_DIR . $Project->getName() . '/lib/';

        if (file_exists($projectDir . $template)) {
            return $projectDir . $template;
        }

        $tplPath = OPT_DIR . $Project->getAttribute('template') . '/';

        // exist template in opt?
        if (file_exists($tplPath . $template)) {
            return $tplPath . $template;
        }

        return $standardTpl;
    }

    /**
     * Return the header template path
     */
    public function getHeaderTemplate(): string
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR . 'templates/mail/header.html';

        // exit project template?
        $template = $this->getAttribute('TplHeader');
        $projectDir = USR_DIR . $Project->getName() . '/lib/';

        if (file_exists($projectDir . $template)) {
            return $projectDir . $template;
        }

        $tplPath = OPT_DIR . $Project->getAttribute('template') . '/';

        // exist template in opt?
        if (file_exists($tplPath . $template)) {
            return $tplPath . $template;
        }

        return $standardTpl;
    }

    /**
     * Return the body template path
     */
    public function getBodyTemplate(): string
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR . 'templates/mail/body.html';

        // exit project template?
        $template = $this->getAttribute('TplBody');
        $projectDir = USR_DIR . $Project->getName() . '/lib/';

        if (file_exists($projectDir . $template)) {
            return $projectDir . $template;
        }

        $tplPath = OPT_DIR . $Project->getAttribute('template') . '/';

        // exist template in opt?
        if (file_exists($tplPath . $template)) {
            return $tplPath . $template;
        }

        return $standardTpl;
    }

    /**
     * Return the footer template path
     */
    public function getFooterTemplate(): string
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR . 'templates/mail/footer.html';

        // exit project template?
        $template = $this->getAttribute('TplFooter');
        $projectDir = USR_DIR . $Project->getName() . '/lib/';

        if (file_exists($projectDir . $template)) {
            return $projectDir . $template;
        }

        $tplPath = OPT_DIR . $Project->getAttribute('template') . '/';

        // exist template in opt?
        if (file_exists($tplPath . $template)) {
            return $tplPath . $template;
        }

        return $standardTpl;
    }

    public function setProject(Project $Project): void
    {
        $this->setAttribute('Project', $Project);
    }

    public function setMainTemplate(string $template): void
    {
        $this->setAttribute('TplMain', $template);
    }

    public function setMetaTemplate(string $template): void
    {
        $this->setAttribute('TplMeta', $template);
    }

    /**
     * Set the body html
     */
    public function setBody(string $html): void
    {
        $this->setAttribute('body', $html);
    }

    public function setHeaderTemplate(string $template): void
    {
        $this->setAttribute('TplHeader', $template);
    }

    public function setFooterTemplate(string $template): void
    {
        $this->setAttribute('TplFooter', $template);
    }
}
