<?php

/**
 * This file contains System_Cron_Cron
 */

/**
 * A cron to execute
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.cron
 */
class System_Cron_Cron extends \QUI\QDOM
{
    /**
     * constructor
     *
     * @param array $settings - cron settings
     */
    public function __construct($settings=array())
    {
        $this->setAttributes($settings);
        $this->setAttribute('params', json_decode($this->getAttribute('params'), true));
    }

    /**
     * Excute the Cron
     *
     * @return Bool
     */
    public function exec()
    {
        $Users = QUI::getUsers();
        $User  = $Users->getUserBySession();

        if (!$User->isSU() && $User->getType() != 'SystemUser') {
            throw new \QUI\Exception('Sie besitzen nicht die nötigen Rechte um Crons (Dienste) auszuführen', 403);
        }

        //$day   = (int)$this->getAttribute('day');
        //$month = (int)$this->getAttribute('month');
        //$min   = (int)$this->getAttribute('min');
        //$hour  = (int)$this->getAttribute('hour');

        $lastexec = 0;

        if ($this->getAttribute('lastexec')) {
            $lastexec = strtotime($this->getAttribute('lastexec'));
        }

        /**
         * Prüfen ob ausgeführt werden soll
         */

        $hour   = date('G');
        $minute = date('i');
        $month  = date('n');
        $day    = date('d');
        $year   = date('Y');


        if ($this->getAttribute('day') != '*') {
            $day = $this->_mktime($this->getAttribute('day'), $day, date('d', $lastexec));
        }

        if ($this->getAttribute('month') != '*') {
            $month = $this->_mktime($this->getAttribute('month'), $month, date('n', $lastexec));
        }

        if ($this->getAttribute('min') != '*') {
            $minute = $this->_mktime($this->getAttribute('min'), $minute, date('i', $lastexec));
        }

        if ($this->getAttribute('hour') != '*') {
            $hour = $this->_mktime($this->getAttribute('hour'), $hour, date('G', $lastexec));
        }

        $nextexec = mktime($hour, $minute, 0, $month, $day, $year);

        // Wenn nächstes ausführen noch nicht erreicht ist
        // Sprich das ausführen grösser ist als jetzt, dann nicht ausführen
        if ($nextexec > time()) {
            return false;
        }

        // Wenn letztes ausführen grösser gleich nächstes ausführen ist,
        // dann wurde es schon ausgeführt
        if ($lastexec >= $nextexec) {
            return false;
        }

        if (method_exists($this, 'execute'))
        {
            $params  = array();
            $_params = $this->getAttribute('params');

            if (is_array($_params))
            {
                foreach ($_params as $entry)
                {
                    if (!isset($entry['name'])) {
                        continue;
                    }

                    if (!isset($entry['value'])) {
                        continue;
                    }

                    $params[ $entry['name'] ] = $entry['value'];
                }
            }

            $this->execute($params);
        }

        // Last Exceute setzen
        QUI::getDB()->updateData(
            System_Cron_Manager::TABLE,
            array('lastexec' => date('Y-m-d H:i:s')),
            array('id'       => $this->getAttribute('id'))
        );

        return true;
    }

    /**
     * prepare the time parameters of the Crons
     *
     * @param String $time - time segments
     * @param String $now  - now
     * @param String $last - last execution
     *
     * @return String
     */
    protected function _mktime($time, $now, $last)
    {
        if (strpos($time, ',') === false &&
            strpos($time, '/') === false)
        {
            return $time;
        }

        if (strpos($time, ',') !== false)
        {
            $splits = explode(',', $time);

            foreach ($splits as $entry)
            {
                if ($entry >= $now && $last <= $entry) {
                    return $entry;
                }
            }
        }

        return $splits[0];
    }

    /**
     * Delete the crons
     */
    public function delete()
    {
        System_Cron_Manager::delete($this);
    }
}

?>