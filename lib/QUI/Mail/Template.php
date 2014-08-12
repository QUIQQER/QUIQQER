<?php

/**
 * This file contains \QUI\Mail\Template
 */

namespace QUI\Mail;

/**
 * Mail Template
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Template extends \QUI\QDOM
{
    /**
     * Constructor
     *
     * @param Array $params
     */
    public function __construc($params=array())
    {
        $this->setAttributes(array(
            'Project'   => false,
            'TplHeader' => LIB_DIR .'templates/mail/header.html',
            'TplFooter' => LIB_DIR .'templates/mail/footer.html'
        ));
    }

    /**
     * Return the complete mail template as HTML
     *
     * @return String
     */
    public function get()
    {
        $Engine = \QUI::getTemplateManager()->getEngine();
    }

    /**
     * Set the project
     *
     * @param \QUI\Projects\Project $Project
     */
    public function setProject(\QUI\Projects\Project $Project)
    {
        $this->setAttribute( 'Project', $Project );
    }

    /**
     * Set the body html
     *
     * @param String $html
     */
    public function setBody($html)
    {

    }

    /**
     * Set the Header template
     *
     * @param unknown $template
     */
    public function setHeaderTemplate($template)
    {
        $this->setAttribute( 'TplHeader', $template );
    }

    /**
     * Set the Header template
     *
     * @param unknown $template
     */
    public function setFooterTemplate($template)
    {
        $this->setAttribute( 'TplFooter', $template );
    }

    /**
     * Return the Project
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        if ( $this->getAttribute( 'Project' ) ) {
            return $this->getAttribute( 'Project' );
        }

        return \QUI::getProjectManager()->get();
    }

    /**
     * Return the header template path
     *
     * @return String
     */
    public function getHeaderTemplate()
    {
        $Project     = $this->getProject();
        $standardTpl = LIB_DIR .'templates/mail/header.html';

        if ( !$Project ) {
            return $standardTpl;
        }

        // exit project template?
        $template   = $this->getAttribute( 'TplHeader' );
        $projectDir = USR_DIR . $Project->getName() .'/lib/';

        if ( file_exists( $projectDir . $template ) ) {
            return $projectDir . $template;
        }

        // exist template in opt?
        if ( file_exists( OPT_DIR . $template ) ) {
            return OPT_DIR . $template;
        }

        return $standardTpl;
    }

    /**
     * Return the footer template path
     *
     * @return String
     */
    public function getFooterTemplate()
    {
        $Project     = $this->getProject();
        $standardTpl = LIB_DIR .'templates/mail/footer.html';

        if ( !$Project ) {
            return $standardTpl;
        }

        // exit project template?
        $template   = $this->getAttribute( 'TplFooter' );
        $projectDir = USR_DIR . $Project->getName() .'/lib/';

        if ( file_exists( $projectDir . $template ) ) {
            return $projectDir . $template;
        }

        // exist template in opt?
        if ( file_exists( OPT_DIR . $template ) ) {
            return OPT_DIR . $template;
        }

        return $standardTpl;
    }


}