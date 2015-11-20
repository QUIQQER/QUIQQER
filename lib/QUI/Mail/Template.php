<?php

/**
 * This file contains \QUI\Mail\Template
 */

namespace QUI\Mail;

use QUI;
use Html2Text\Html2Text;

/**
 * Mail Template
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Template extends QUI\QDOM
{
    /**
     * Constructor
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        $this->setAttributes(array(
            'body'      => '',
            'Project'   => false,
            'TplHeader' => 'mails/header.html',
            'TplFooter' => 'mails/footer.html'
        ));

        $this->setAttributes($params);
    }

    /**
     * Return the complete mail template as HTML
     *
     * @return string
     */
    public function getHTML()
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign($this->getAttributes());

        $header = $Engine->fetch($this->getHeaderTemplate());
        $footer = $Engine->fetch($this->getFooterTemplate());
        $body = $this->getAttribute('body');

        return $header.$body.$footer;
    }

    /**
     * Return the complete mail as text, without html
     *
     * @return string
     */
    public function getText()
    {
        $Html2Text = new Html2Text($this->getHTML());

        return $Html2Text->get_text();
    }

    /**
     * Set the project
     *
     * @param \QUI\Projects\Project $Project
     */
    public function setProject(QUI\Projects\Project $Project)
    {
        $this->setAttribute('Project', $Project);
    }

    /**
     * Set the body html
     *
     * @param string $html
     */
    public function setBody($html)
    {
        $this->setAttribute('body', $html);
    }

    /**
     * Set the Header template
     *
     * @param string $template
     */
    public function setHeaderTemplate($template)
    {
        $this->setAttribute('TplHeader', $template);
    }

    /**
     * Set the Header template
     *
     * @param string $template
     */
    public function setFooterTemplate($template)
    {
        $this->setAttribute('TplFooter', $template);
    }

    /**
     * Return the Project
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        if ($this->getAttribute('Project')) {
            return $this->getAttribute('Project');
        }

        return \QUI::getProjectManager()->get();
    }

    /**
     * Return the header template path
     *
     * @return string
     */
    public function getHeaderTemplate()
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR.'templates/mail/header.html';

        if (!$Project) {
            return $standardTpl;
        }

        // exit project template?
        $template = $this->getAttribute('TplHeader');
        $projectDir = USR_DIR.$Project->getName().'/lib/';

        if (file_exists($projectDir.$template)) {
            return $projectDir.$template;
        }

        // exist template in opt?
        if (file_exists(OPT_DIR.$template)) {
            return OPT_DIR.$template;
        }

        return $standardTpl;
    }

    /**
     * Return the footer template path
     *
     * @return string
     */
    public function getFooterTemplate()
    {
        $Project = $this->getProject();
        $standardTpl = LIB_DIR.'templates/mail/footer.html';

        if (!$Project) {
            return $standardTpl;
        }

        // exit project template?
        $template = $this->getAttribute('TplFooter');
        $projectDir = USR_DIR.$Project->getName().'/lib/';

        if (file_exists($projectDir.$template)) {
            return $projectDir.$template;
        }

        // exist template in opt?
        if (file_exists(OPT_DIR.$template)) {
            return OPT_DIR.$template;
        }

        return $standardTpl;
    }
}
