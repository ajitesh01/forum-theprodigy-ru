<?php
namespace Prodigy\Respond;

class Calendar extends Respond
{
    public function getCurrentTime()
    {
        $timeUserOff = (isset($this->app->user->timeOffset) ? $this->app->user->timeOffset : 0);
        return time() + (($this->app->conf->timeoffset + $timeUserOff) * 3600);
    }
    
    public function createHolidayArray($monthp1, $year, $day = NULL)
    {
        // Holidays. Note that with the holidays the month in the table is NOT zero based like it is with
        // events. I did this because this table is more likely to get interaction directly from an admin
        // and having to remember to subtract one from the months is a pain.
        $db_prefix = $this->app->db->prefix;
        $holidays = array();
        $strSql = "SELECT day,year,title FROM {$db_prefix}calendar_holiday WHERE month=? AND ((year IS NULL) or year=?)";
        $qparams = array($monthp1, $year);
        
        if ($day != NULL)
        {
            $strSql .= " AND day=?";
            $qparams[] = $day;
        }
        
        $dbst = $this->app->db->prepare($strSql);
        $dbst->execute($qparams);
        
        while ($row = $dbst->fetch())
        {
            // The convention in the table is for holidays that always happen on the same date to have NULL for
            // the year value. If a row in the holiday table does have a year value then only print that holiday
            // for the specific year. Holidays that float can be added ahead of time with the year values given 
            // to make them show up on the right days.
            if ($row['year'] == NULL || $row['year'] == $year)
            {
                // I'm setting the colors on holidays as they print rather than in the string here. The reason
                // being I have holidays all one color and if multiple are on the same day it becomes akward
                // to try and set the <font> here -- or redundant if it happens on each one for each day.
                if (!isset($holidays[$row['day']]))
                    $holidays[$row['day']] = "{$this->app->locale->calendar5} {$row['title']}";
                else
                    $holidays[$row['day']] .= ", {$row['title']}";
            }
        }
        $dbst = null;
        return $holidays;
    }
    
    // $day has been added as an optional parameter to restrict the results to a particular day.
    // This is used by the board index to only find members of the current day.
    public function createBirthdayArray($monthp1,$year,$day=NULL)
    {
        $db_prefix = $this->app->db->prefix;
        // Collect all of the birthdays for this month and precreate the strings to use for display.
        $bday = array();
        $strSql = "SELECT dayofmonth(birthdate) as dom, membername, realname, year(birthdate) as year FROM {$db_prefix}members WHERE month(birthdate) = ?";
        $qparams = array($monthp1);
        
        if ($day != NULL) {
            $strSql .= " AND dayofmonth(birthdate) = ?";
            $qparams[] = $day;
        }
        
        $dbst = $this->app->db->prepare($strSql);
        $dbst->execute($qparams);
        while ($row = $dbst->fetch())
        {
            $euser=urlencode($row['membername']);
            if ($row['year'] > 0 && $row['year'] <= $year)
            {
                $ageNum = $year - $row['year'];
                $age = " ($ageNum)";
            }
            else
                $age = '';
            if (!isset($bday[$row['dom']]))
                $bday[$row['dom']] = '<font class="calendar">' . $this->app->locale->calendar3 . '</font> <a href="' . SITE_ROOT . '/people/' . $euser . '/">' . $this->service->esc($row['realname']) . $age . '</a>';
            else
                $bday[$row['dom']] .= ', <a href="' . SITE_ROOT . '/people/' . $euser . '/">' . $this->service->esc($row['realname']) . '' . $age . '</a>';
        }
        $dbst = null;
        return $bday;
    }

    public function createEventArray($bPowerUser, $cats, $month, $year, $day = NULL)
    {
        $db_prefix = $this->app->db->prefix;
        
        $events = array();
        $strSql = "SELECT cal.day,cal.title,cal.id_board,b.ID_CAT,cal.id_topic,cal.id_member,cal.id ";
        $strSql .= "FROM {$db_prefix}calendar as cal, {$db_prefix}boards as b ";
        $strSql .= "WHERE cal.month=? AND cal.year=? AND cal.id_board = b.ID_BOARD";
        
        $qparams = array($month, $year);
        
        if ($day != NULL)
        {
            $strSql .= " AND cal.day = ?";
            $qparams[] = $day;
        }
        $dbst = $this->app->db->prepare($strSql);
        $dbst->execute($qparams);
        while ($row = $dbst->fetch())
        {
            if ($bPowerUser || $row['id_member'] == $this->app->user->id)
                $strOwner = '<a href="' . SITE_ROOT . '/calendar/ee/' . $row[id] . '/"><font color="#FF0000">*</font></a>';
            else
                $strOwner = '';
            
            // See if the user has access to the board this event was posted in.
            if ($cats[$row['ID_CAT']][0] == '' || $bPowerUser || in_array($this->app->user->group, $cats[$row['ID_CAT']]))
            {
                if (!isset($events[$row['day']]))
                    $events[$row['day']] = '<font color="#' . $this->app->conf->cal_eventcolor . '">' . $this->locale->calendar4 . '</font> ' . $strOwner . '<a href="' . SITE_ROOT . '/b' . $row['id_board'] . '/t' . $row['id_topic'] . '/">' . $this->service->esc($row['title']) . '</a>';
                else
                    $events[$row['day']] .= ', ' . $strOwner . '<a href="' . SITE_ROOT . '/b' . $row['id_board'] . '/t' . $row['id_topic'] . '/">' . $this->service->esc($row['title']) . '</a>';
            }
        }
        $dbst = null;
        return $events;
    }
    
    // Called from BoardIndex.php to display the current day's events on the board index.
    public function getEvents()
    {
        if (!$this->app->conf->cal_enabled)
            return null;

        // Make sure at least one of the options is checked.
        if (!$this->app->conf->cal_showeventsonindex && !$this->app->conf->cal_showbdaysonindex && !$this->app->conf->cal_showholidaysonindex)
            return null;
        
        $today = localtime($this->getCurrentTime());
        $month = $today[4];
        $year = $today[5] + 1900;
        $day = $today[3];
        
        $calendar = array('day' => $day);
        
        $bechoedHeader = false;
        
        if ($this->app->conf->cal_showholidaysonindex)
        {
            $calendar['holidays'] = $this->createHolidayArray($month + 1, $year, $day);
        }

        if ($this->app->conf->cal_showbdaysonindex)
        {
            $calendar['bday'] = $this->createBirthdayArray($month + 1, $year, $day);
        }

        if ($this->app->conf->cal_showeventsonindex)
        {
            $bPowerUser = ($this->app->user->isStaff() > 2);
            $cats = array();
            $db_prefix = $this->app->db->prefix;
            $rs = $this->app->db->query("SELECT ID_CAT,membergroups FROM {$db_prefix}categories");
            while ($row = $rs->fetch())
                $cats[$row['ID_CAT']] = explode(',', $row['membergroups']);
            
            $calendar['events'] = $this->createEventArray($bPowerUser, $cats, $month, $year, $day);
        }
        return $calendar;
    } // getEvents()
    
    // Called from the posting routine to make sure all of the calendar elements exist
    // and are valid.
    public function ValidatePost($request)
    {
        // Passed in from form.
        global $month, $year, $day, $evtitle, $deleteevent, $span;
        $POST = $request->paramsPost();
        
        if (!$this->CanPost())
            return $this->error($this->app->locale->calendar6);
        
        if (empty($POST->month))
            return $this->error($this->app->locale->calendar7);
        
        if (empty($POST->year))
            return $this->error($this->app->locale->calendar8);
        
        if ($POST->month < 0 || $POST0->month > 11)
            return $this->error($this->app->locale->calendar1);
        
        if ($POST->year < $this->app->conf-cal_minyear || $POST->year > $this->app->conf->cal_maxyear)
            return $this->error($this->app->locale->calendar2);
        
        if (!empty($POST->span))
        {
            // Make sure it's turned on and not some fool trying to trick it.
            if ($this->app->conf->cal_allowspan != 1)
                return $this->error($this->app->locale->calendar55);
            if ($POST->span < 1 || $POST->span > $this->app->conf->cal_maxspan)
                return $this->error($this->app->locale->calendar56);
        }
        
        // Started using this function in some other places. There is no need to validate
        // the following values if we are just deleting the event.
        if (empty($POST->deleteevent))
        {
            if (empty($POST->day))
                return $this->error($this->locale->calendar14);
            if (empty($POST->evtitle))
                return $this->error($this->app->locale->calendar15);
            
            if (!checkdate($POST->month + 1, $POST->day, $POST->year))
                return $this->error($this->app->locale->calendar16);
            
            if (trim($POST->evtitle) == "")
                return $this->error($this->app->locale->calendar17);
            if (strlen($POST->evtitle) > 30) 
                $POST->evtitle = substr($POST->evtitle, 0, 30);
            $POST->evtitle = str_replace(';', '', $POST->evtitle);
	}
    } // validatePost()
    
    public function canPost() {
        if ($this->app->user->isStaff())
            return true;
        
        if (!empty($this->app->conf->cal_postgroups))
        {
            $calPostGroups = explode(',', $this->app->conf->cal_postgroups);
            if (in_array($this->app->user->group, $calPostGroups))
                return true;
        }

        if (!empty($this->app->conf->cal_postmembers))
        {
            $calPostMems = explode(',', $this->app->conf->cal_postmembers);
            if (in_array($this->app->user->name, $calPostMems))
                return true;
        }
        
        if ($this->app->conf->cal_memberscanpost == 1 && $this->app->user->id > 0)
            return true;
        
        return false;
    } // canPost()
    
    public function show($request, $response, $service, $app)
    {
        if ($app->user->guest && !$app->conf->show_calendar_to_guest)
            return $this->error($app->locale->txt[1]);
        
        $GET = $request->paramsGet();
        $data = array();
        
        $today = localtime($this->GetCurrentTime());
        // If the month and year are not passed in, using today's date as a starting point.
        if (!isset($GET->month) || !isset($GET->year) || !is_numeric($GET->month) || !is_numeric($GET->year))
        {
            $curMonth = $today;
            $month = $curMonth[4];
            $year = $curMonth[5] + 1900;
            $URI = parse_url($request->uri());
            $newURI = "{$URI['path']}?year=$year&month=$month";
            return $this->redirect($newURI);
        }
        else
        {
            if ($GET->month < 0 || $GET->month > 11)
                return $this->error($app->locale->calendar1);
            if ($GET->year < $app->conf->cal_minyear || $GET->year > $app->conf->cal_maxyear)
                return $this->error($app->locale->calendar2);
            
            $curMonth = localtime(mktime(0, 0, 0, $GET->month + 1, 1, $GET->year));
        }
        
        // So I don't have to keep adding one to it all over.
        $monthp1 = $GET->month + 1;
        
        // Creating a date based on the first day of the current month. This is needed to figure out what day on
        // the first line of the calendar is used to start printing.
        $first = localtime(mktime(0, 0, 0, $monthp1, 1, $GET->year));

        // Find the last day of the month.
        $nLastDay = 31;
        while (!checkdate($monthp1,$nLastDay,$GET->year) && $nLastDay > 0)
            $nLastDay--;
        
        // Just because I don't want to keep typing the array index. This is the number of days the first row
        // is shifted to the right for the starting day.
        $nShift = $first[6];
        
        if ($app->conf->cal_startmonday == 1)
            $nShift = ($nShift < 1 ? 6 : $nShift - 1);
        
        // Number of rows required to fit the month.
        $nRows = floor(($nLastDay+$nShift)/7);
        if (($nLastDay + $nShift) % 7)
            $nRows++;
        
        // No point in doing this if over and over for access checks.
        if ($app->user->isStaff())
            $bPowerUser = TRUE;
        else
            $bPowerUser = FALSE;
        
        $db_prefix = $app->db->prefix;
        
        // Creating an array of all the board categories with an array of all the member groups allowed for each category.
        // Using this to break it apart one time and use the ids from calendar table to look up the values.
        $cats = array();
        $dbst = $app->db->query("SELECT ID_CAT,membergroups FROM {$db_prefix}categories");
        while ($row = $dbst->fetch())
            $cats[$row['ID_CAT']] = explode(',', $row['membergroups']);
        $dbst = null;
        
        $bday = $this->CreateBirthdayArray($monthp1,$GET->year);
        $events = $this->CreateEventArray($bPowerUser,$cats,$GET->month,$GET->year);
        $holidays = $this->CreateHolidayArray($monthp1,$GET->year);
        
        // Save some processing time by only doing this once. Particularly if the "Days as Link" option is
        // turned on.
        $bCanPost = $this->CanPost();
        
        $bStartMonday = ($app->conf->cal_startmonday == 1);
        $bShowWeek = ($app->conf->cal_showweeknum == 1);
        
        $data['days'] = array();
        for ($i = 0; $i < 7; $i++)
        {
            if ($bStartMonday)
                $nDayIndex = ($i == 6 ? 0 : $i + 1);
            else
                $nDayIndex = $i;
            
            $data['days'][] = $app->locale->days[$nDayIndex];
        }
        
        // And adjustment value to apply to all calculated week numbers.
        if ($bShowWeek)
        {
            // Need to know what day the first of the year was on.
            $foy = localtime(mktime(0, 0, 0, 1, 1, $GET->year));
            
            // If the first day of the year is on the start day of a week, then there is no adjustment
            // to be made. However, if the first day of the year is not a start day, then there is a partial
            // week at the start of the year that needs to be accounted for.
            if ($bStartMonday)
                $nWeekAdjust = ($foy[6] == 1 ? 0 : 1);
            else
                $nWeekAdjest = ($foy[6] == 0 ? 0 : 1);
        }
        
        $data['rows'] = array();
        for ($nRow = 0; $nRow < $nRows; $nRow++)
        {
            // Months rows
            
            $cols = array();
            for ($nCol = 0; $nCol < 7; $nCol++)
            {
                // months cols
                $col = array();
                $nDay = ($nRow * 7) + $nCol - $nShift + 1;
                $col['nday'] = false;
                if ($nDay >= 1 && $nDay <= $nLastDay)
                {
                    $col['nday'] = true;
                    // Showing week numbers?
                    if (!$bShowWeek)
                        $strWeek = '';
                    else
                    {
                        // Is this the beginning of a week? Only displaying the week number on the first day of the week.
                        if ((!$bStartMonday && ($first[6] + $nDay - 1) % 7 == 0) || ($bStartMonday && ($first[6] + $nDay - 1) % 7 == 1))
                        {
                            $nWeekNum = floor(($first[7] + $nDay - 1) / 7) + 1 + $nWeekAdjust;
                            
                            // I was looking at a calendar on the web and the last days of december if they do not fall
                            // within 52 weeks are apparently called week 1; which sorta makes sense I suppose.
                            if ($nWeekNum == 53)
                                $nWeekNum = 1;
                                
                                $strWeek = $app->locale->calendar51 . ' ' . $nWeekNum;
                        }
                        else
                            $strWeek = '';
                    }
                    
                    $col['nDay'] = $nDay;
                    $col['month'] = $GET->month;
                    $col['year'] = $GET->year;
                    $col['week'] = $strWeek;
                    
                    $col['isLink'] = false;
                    if ($app->conf->cal_daysaslink == 1 && $bCanPost)
                        $col['isLink'] = true;
                    
                    $col['currentday'] = false;
                    if ($today[5] == $curMonth[5] && $today[4] == $curMonth[4] && $nDay == $today[3])
                        $col['currentday'] = true;
                    
                    if (isset($holidays[$nDay]))
                        $col['holiday'] = $holidays[$nDay];
                    
                    if (isset($bday[$nDay]))
                    {
                        // If there was a holiday, make a space.
                        $col['bday'] = $bday[$nDay];
                    }
                    
                    if (isset($events[$nDay]))
                    {
                        $col['event'] = $events[$nDay];
                    }
                }
                $cols[] = $col;
            } # 287
            $data['rows'][] = $cols;
        } // #285
        
        // preparing prev month link data
        if ($curMonth[4] > 0 || ($curMonth[4] == 0 && $curMonth[5]+1900 > $app->conf->cal_minyear))
        {
            if ($curMonth[4] == 0)
            {
                $nPrevMonth = 11;
                $nPrevYear = $curMonth[5]-1+1900;
            }
            else
            {
                $nPrevMonth = $curMonth[4]-1;
                $nPrevYear = $curMonth[5]+1900;
            }
            $data['prev'] = array(
                'year' => $nPrevYear,
                'month' => $nPrevMonth,
                'name' => "&#160;&#171; {$app->locale->months_short[$nPrevMonth]} $nPrevYear"
            );
        }
        
        $calPostGroups = explode(',', $app->conf->cal_postgroups);
        
        // Preparing month selector
        $data['months'] = array();
        $nMonth = 0;
        foreach ($app->locale->monthy as $strMonth)
        {
            $selected = '';
            if ($nMonth == $curMonth[4])
                $selected = 'selected="selected"';
            $data['months'][] = array($nMonth, $strMonth, $selected);
            $nMonth++;
        }
        
        // Preparing year selector
        $data['years'] = array();
        for ($i = $app->conf->cal_minyear; $i <= $app->conf->cal_maxyear; $i++)
        { 
            $selected = '';
            if ($i == $curMonth[5] + 1900)
                $selected = 'selected="selected"';
            $data['years'][] = array($i, $selected);
        }
        
        // Preparing next month link
        if ($curMonth[4] < 11 || ($curMonth[4] == 11 && $curMonth[5] + 1900 < $app->conf->cal_maxyear))
        {
            if ($curMonth[4] == 11)
            {
                $nNextMonth = 0;
                $nNextYear = $curMonth[5] + 1 + 1900;
            }
            else
            {
                $nNextMonth = $curMonth[4] + 1;
                $nNextYear = $curMonth[5] + 1900;
            }
            $data['next'] = array(
                'year' => $nNextYear,
                'month' => $nNextMonth,
                'name' => "{$app->locale->months_short[$nNextMonth]} $nNextYear &#187;&#160;"
            );
        }
        
        $data['title'] = "{$app->conf->mbname} : {$app->locale->calendar24}";
        $data['monthy'] = $app->locale->monthy[$GET->month];
        $data['month'] = $GET->month;
        $data['year'] = $GET->year;
        $data['canpost'] = $bCanPost;
        
        return $this->render('templates/calendar/show.template.php', $data);
    } // show()
}
