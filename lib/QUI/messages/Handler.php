<?php

/**
 * This file contains QUI_Messages_Handler
 */

/**
 * Message Handler
 *
 * Handles all messages and send it to the JavaScript Message Handler
 * With the handler you can send messages from ajax to the UI
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.messages
 */

class QUI_Messages_Handler
{
    /**
     * message list
     * @var array
     */
    protected $_messages = array();

    /**
     * Return all messages, which were set
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * Return the messages list as pure array
     * @return array
     */
    public function getMessagesAsArray()
    {
        $result = array();

        foreach ($this->_messages as $Message) {
            $result[] = $Message->toArray();
        }

        return $result;
    }

    /**
     * Clear the message handler, all messages will be deletet
     */
    public function clear()
    {
        unset($this->_messages);

        $this->_messages = array();
    }

    /**
     * Add a message to the handler
     *
     * @param QUI_Messages_Message $Message
     */
    public function add($Message)
	{
        $this->_messages[] = $Message;
	}

	/**
	 * Add a exception to the messanger
	 * it converts the exception into an error message
	 *
	 * @param \QUI\Exception|Exception $Exception
	 */
	public function addException($Exception)
	{
        $this->add(
			new QUI_Messages_Error(array(
			    'code'    => $Exception->getCode(),
				'message' => $Exception->getMessage()
			))
		);
	}

	/**
	 * Add an attention to the handler
	 *
	 * @param String $str - attention message
	 */
	public function addAttention($str)
	{
		$this->add(
			new QUI_Messages_Attention(array(
				'message' => $str
			))
		);
	}

	/**
	 * Add an error to the handler
	 *
	 * @param String $str - error message
	 */
	public function addError($str)
	{
		$this->add(
			new QUI_Messages_Error(array(
				'message' => $str
			))
		);
	}

	/**
	 * add an information to the handler
	 *
	 * @param String $str - information message
	 */
	public function addInformation($str)
	{
		$this->add(
			new QUI_Messages_Information(array(
				'message' => $str
			))
		);
	}

	/**
	 * add a success to the handler
	 *
	 * @param String $str - success message
	 */
	public function addSuccess($str)
	{
		$this->add(
			new QUI_Messages_Success(array(
				'message' => $str
			))
		);
	}
}

?>