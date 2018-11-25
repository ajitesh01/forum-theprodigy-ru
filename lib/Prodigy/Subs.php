<?php

namespace Prodigy;

class Subs {
    private $router;
    private $app;
    private $service;
    private $request;
    private $response;
    public function __construct($router) {
        $this->router = $router;
        $this->app = $router->app();
        $this->service = $router->service();
        $this->request = $router->request();
        $this->response = $router->response();
    }
    
    public function validate($str, $err = null) {
        $str = trim($str);
        return $this->service->validate($str, $err);
    }
    
    public function validateParam($param, $err = null) {
        return $this->validate($this->request->param($param), $err);
    }
    
    // MD5 Encryption
    public function md5_hmac($data, $key) {
        if (strlen($key) > 64)
            $key = pack('H*', md5($key));
        $key  = str_pad($key, 64, chr(0x00));

        $k_ipad = $key ^ str_repeat(chr(0x36), 64);
        $k_opad = $key ^ str_repeat(chr(0x5c), 64);
        
        return md5($k_opad . pack('H*', md5($k_ipad . $data)));
    } // md5_hmac
    
    /**
     * Replace all vulgar word with respective proper words Substring or whole words
     * @param string $text  input string
     * @return string
     */
    public function CensorTxt($text) {
        if (is_null($this->app->conf->censor_list)) {
            // load censor list
            $db_prefix = $this->app->db->prefix;
            $request = $this->app->db->query("SELECT vulgar,proper FROM {$db_prefix}censor WHERE 1", false) or database_error(__FILE__, __LINE__, $this->app->db);
            if (!$request)
                $this->app->errors->abort('Error', "205 {$this->app->locale->txt[106]}: {$this->app->locale->txt[23]} censor");
            $this->app->conf->censor_list = array();
            while ($row = $request->fetch_row())
            $this->app->conf->censor_list[trim($row[0])] = trim($row[1]);
        }
        
        foreach ($this->app->conf->censor_list as $vulgar => $proper) {
            if ($this->app->conf->censorWholeWord == '0')
                $text = preg_replace("/$vulgar/i", $proper, $text);
            else
                $Text = preg_replace("/\b$vulgar\b/i", $proper, $Text);
        }
        return $text;
    }

    /**
     * Return supplied value or default value if empty
     * @param mixed $value   input value
     * @param mixed $default default value to return if input empty
     * @return mixed
     */
    public function getVal($value, $default = null) {
        if (empty($value)) {
            return $default;
        } else {
            return $value;
        }
    }
    
    public function timeformat($logTime, $short=false) {
        $time = $this->getVal($this->app->user->timeOffset, 0);
        $time = ($this->app->conf->timeoffset + $time) * 3600;
        $nowtime = $time + time();
        $time += $logTime;
        
        if ($this->app->conf->todayMod >= 1) {
            $t1 = getdate($time);
            $t2 = getdate($nowtime);
            $strtfmt = (($this->app->user->name == 'Guest' || $this->app->user->timeFormat == '') ? $this->app->conf->timeformatstring : $this->app->user->timeFormat);
            
            if ((strpos($strtfmt, '%H') === false) && (strpos($strtfmt, '%T') === false))
                $today_fmt = 'h:i:sa';
            else
                $today_fmt = 'H:i:s';
            
            if ($t1['yday'] == $t2['yday'] && $t1['year'] == $t2['year']){
                if ($short) {
                    $date_string = date($today_fmt, $time);
                    return '<span class="date" title="Сегодня в ' . $date_string .'"><b>&#8986;</b></span>';
                } else
                    return $this->app->locale->txt['yse10'] . date($today_fmt, $time);
            }
            if ((($t1['yday'] == $t2['yday'] - 1 && $t1['year'] == $t2['year']) || ($t2['yday'] == 0 && $t1['year'] == $t2['year'] - 1) && $t1['mon'] == 12 && $t1['mday'] == 31) && $this->app->conf->todayMod == '2')
                return $this->app->locale->txt['yse10b'] . date($today_fmt, $time);
        }
        
        if ($short) {
            $date_string = lang_strftime ($time);
            return '<span class="date" title="' . $date_string . '">&#8986;</span>';
        }
        else
            return $this->lang_strftime ($time);
    } // timeformat()
    
    public function lang_strftime($currtime) {
        if ($this->app->user->name == 'Guest' || $this->app->user->timeFormat == '')
            $str = stripslashes($this->app->conf->timeformatstring);
        else
            $str = stripslashes($this->app->user->timeFormat);
        
        if (setlocale(LC_TIME, $this->app->locale->locale)) {
            $str = preg_replace('/%a/', ucwords(strftime('%a', $currtime)), $str);
            $str = preg_replace('/%A/', ucwords(strftime('%A', $currtime)), $str);
            $str = preg_replace('/%b/', ucwords(strftime('%b', $currtime)), $str);
            $str = preg_replace('/%B/', ucwords(strftime('%B', $currtime)), $str);
        }
        else {
            $str = preg_replace('/%a/', $this->app->locale->days_short[(int)strftime('%w', $currtime)], $str);
            $str = preg_replace('/%A/', $this->app->locale->days[(int)strftime('%w', $currtime)], $str);
            $str = preg_replace('/%b/', $this->app->locale->months_short[(int)strftime('%m', $currtime) - 1], $str);
            $str = preg_replace('/%B/', $this->app->locale->months[(int)strftime('%m', $currtime) - 1], $str);
            $str = preg_replace('/%p/', ((int)strftime('%H', $currtime) < 12 ? "am" : "pm"), $str);
        }
        
        return strftime($str, $currtime);
    } // lang_strftime()
    
    public function url_parts()
    {
        $cookie_dom = '';
        $cookie_dir = '/';
        
        if ($this->app->conf->localCookies) {
            $url .= $boardurl . "/";
            $pos = strpos($url, '//');
            if ($pos > 0 && strncmp(strtolower($url), 'http:', $pos) == 0)	//Valid protocol
            {
                $urlpos = strpos($url, '/', $pos + 2);
                
                if ($urlpos > 0)
                {
                    $cookie_dom = substr($url, $pos + 2, $urlpos - $pos - 2);
                    $cookie_dir = substr($url, $urlpos);
                }
            }
        }
        return "$cookie_dom<yse_sep>$cookie_dir";
    } // url_parts()

    /**
     * HtmlSpecialChars wrapper
     * @param string $str       input string
     * @param string $charset   input encoding
     * @return string
     */
    public function htmlescape($str, $charset = null) {
        if (is_null($charset))
            $charset = $this->app->conf->charset;
        return $this->service->esc($str, $charset);
    } // htmlescape
    
    public function DoUBBC($message, $type) {
        return $this->service->doUBBC($message, $type); // FIXME
    }
    
    /**
     * Convert unicode chars to HTML entities.
     * @param string $string - input string in UTF-8 charset
     * @param string $charset - output charset, defaults to defined in config.
     * @returns string
     */
    public function unicodeentities ($string, $charset = null)
    {
        return $this->service->unicodeentities($string, $charset);
    }
    
    public function runtime_stats()
    {
        $time_end = microtime(true);
        $stats = array(
            'time_end' => $time_end,
            'runtime' => round($time_end - TIME_START, 3),
            'memory' => round(memory_get_usage() / 1024, 1),
            'memory_peak' => round(memory_get_peak_usage() / 1024, 1)
        );
        return $stats;
    }
    
    /**
     * Preparses a message, puts [url] tags around urls etc.
     * @param string $message  the message to parse the code in
     * @return string          the preparsed code
     */
    public function preparsecode($message)
    {
        // FIXME 
        return $message;
    }
    
    public function updateStats($type)
    {
        $db = $this->app->db;
        $db_prefix = $db->prefix;
        switch ($type)
        {
            case 'member' :
                $result = $db->query("SELECT memberName,realName FROM {$db_prefix}members WHERE posts > 0 ORDER BY dateRegistered DESC LIMIT 1");
                list($latestmember,$latestRealName) = $result->fetch_row();
                
                $result = $db->query("SELECT COUNT(*) FROM {$db_prefix}members;");
                list($memberCount) = $result->fetch_row();
                
                $latestmember = $db->escape_string($latestmember);
                $latestRealName = $db->escape_string($latestREalName);
                
                $request = $db->query("
                    REPLACE INTO {$db_prefix}settings (variable,value)
                    VALUES ('latestMember','$latestmember'),('latestRealName','$latestRealName'),('memberCount','$memberCount')");
                break;
            case 'message' :
                $result = $db->query("
                    SELECT COUNT(*) as totalMessages
                    FROM {$db_prefix}messages");
                $row = $result->fetch_assoc();
                $request = $db->query("
                    UPDATE {$db_prefix}settings SET value='{$row['totalMessages']}'
                    WHERE variable='totalMessages'");
                break;
            case 'topic' :
                $result = $db->query("
                    SELECT COUNT(*)  as totalTopics
                    FROM {$db_prefix}topics");
                $row = $result->fetch_assoc();
                $request = $db->query("
                    UPDATE {$db_prefix}settings SET value='{$row['totalTopics']}'
                    WHERE variable='totalTopics'");
                break;
        }
    } // updateStats()
    
    public function UpdateLastMessage($board)
    {
        $db = $this->app->db;
        $db_prefix = $db->prefix;
        
        $board = (int) $board;
        
        $result = $db->query("
            SELECT m.ID_TOPIC
            FROM {$db_prefix}messages AS m,{$db_prefix}topics as t
            WHERE m.ID_MSG=t.ID_LAST_MSG
                AND t.ID_BOARD=$board
            ORDER BY m.posterTime DESC
            LIMIT 1");
        if ($result->num_rows > 0)
        {
            list($lastTopicID) = $result->fetch_array();
            $request = $db->query("UPDATE {$db_prefix}boards SET ID_LAST_TOPIC=$lastTopicID WHERE ID_BOARD=$board");
        }
        elseif (strlen($db->error))
            return $this->app->errors->abort('', $db->error);
        else
            $request = $db->query("UPDATE {$db_prefix}boards SET ID_LAST_TOPIC=0 WHERE ID_BOARD=$board");
    } // UpdateLastMessage()
}

?>