<?php

/**
 * This file contains the Utils_Timer
 */

/**
 * A timer
 * measures the length of time between measurement points
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class Utils_Timer
{
    /**
     * All time milstones
     * @var array
     */
	protected $_milestones;

	/**
	 * Time calc
	 *
	 * @return Float
	 */
	protected function _time()
	{
    	list($utime, $time) = explode(" ", microtime());

    	return ((float)$utime + (float)$time);
  	}

	/**
	 * Set measurement point
	 *
	 * @param String $name - name of the point
	 */
  	public function milestone($name)
  	{
	    $this->_milestones[] = array($name, $this->_time());
  	}

  	/**
  	 * Returns the time measurement result as an array
  	 *
  	 * @return Array
  	 */
  	public function result()
  	{
  		$this->milestone('finish');

  		return $this->_milestones;
	}

	/**
	 * Returns the time measurement results as HTML
	 *
	 * @return String
	 */
	public function resultStr()
	{
		$result = $this->result();

		$output = '<table border="1">'."\n".
			'<tr>'.
				'<th>Messpunkt</th>'.
				'<th>Diff</th>'.
				'<th>Cumulative</th>'.
			'</tr>'."\n";

		foreach ($result as $key => $data)
		{
			$output .= '<tr><td>'.$data[0].'</td>'.
				'<td>'.round(($key ? $data[1] - $result[$key - 1][1]: '0'), 5).'</td>'.
				'<td>'.round(($data[1] - $result[0][1]), 5).'</td></tr>'."\n";
		}

		$output .= '</table>';

		return $output;
	}

	/**
	 * Returns the time measurement result for the bash / console
	 *
	 * @return Array
	 */
	public function resultConsole()
	{
		$result = $this->result();

		foreach ($result as $key => $data)
		{
			$data[2] = round(($key ? $data[1] - $result[$key - 1][1]: '0'), 5); // Diff
			$data[3] = round(($data[1] - $result[0][1]), 5); // Cumulative

			$result[$key] = $data;
		}

		return $result;
	}
}

?>