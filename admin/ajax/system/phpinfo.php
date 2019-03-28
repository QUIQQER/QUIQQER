<?php

/**
 * Return the php info
 * Only for SuperUsers
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_phpinfo',
    function () {
        \ob_start();
        \phpinfo();

        $phpinfo = ['phpinfo' => []];

        if (preg_match_all(
            '#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s',
            \ob_get_clean(),
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                if (\strlen($match[1])) {
                    $phpinfo[$match[1]] = [];
                } else {
                    if (isset($match[3])) {
                        $keys = \array_keys($phpinfo);
                        $end  = \end($keys);

                        $phpinfo[$end][$match[2]] = isset($match[4]) ? [$match[3], $match[4]] : $match[3];
                    } else {
                        $keys = \array_keys($phpinfo);
                        $end  = \end($keys);

                        $phpinfo[$end][] = $match[2];
                    }
                }
            }
        }


        $str = '';

        foreach ($phpinfo as $name => $section) {
            $str .= "<h2>$name</h2>";
            $str .= "<table class=\"data-table php-info-table\">\n";

            $i = 0;

            foreach ($section as $key => $val) {
                $str .= '<tr class="'.($i % 2 ? 'odd' : 'even').'">';

                if (\is_array($val)) {
                    $str .= "<td>$key</td>";
                    $str .= "<td>$val[0]</td>";
                    $str .= "<td>$val[1]</td>";
                } elseif (is_string($key)) {
                    $str .= "<td>$key</td>";
                    $str .= "<td colspan=\"2\">$val</td>";
                } else {
                    $str .= "<td colspan=\"3\">$val</td>";
                }

                $str .= "</tr>";
                $i++;
            }

            $str .= "</table>\n";
        }

        return $str;
    },
    false,
    'Permission::checkSU'
);
