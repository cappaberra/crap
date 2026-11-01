<?php
#-------------------------------------------------------
# C.R.A.P v2.0
# Charter Reliability Automation Project
# Muddleware PHP Class to AJAX framework
#
# @author Noah Pritikin / Jacob Farkas
# Created on: 6/30/06
#
# @version $Revision: 1.14 $
# @author Last Modified by: $Author: noah $
# @since $Date: 2006/09/22 01:00:37 $
#-------------------------------------------------------

require_once __DIR__ . '/config.php';

class crap
{
    private $id;
    private $debug;
    private $queryVars;
    private $pcthosts;
    private $uppct;
    private $db;
    private $filename;
    private $status_tbl;
    private $downtime_tbl;
#    private $hosts;
    private $logging;
    private $downsecs;
    private $totaldown;
    private $status;
    private $lastdown;
    private $lastdownID;
    private $maxDownID;
    private $since;
    private $totaltime;
    private $tdown;
    private $lasthosts;
    private $secinyear;
    private $admin;
    
    /**
     *
     * Constructor for the CRAP class
     *
    */
    public function __construct($queryVars)
    {
        $this->queryVars = $queryVars;
        $this->uppct = (float) env('EXPECTED_UPTIME', '0.9995');
        if ($this->uppct <= 0 || $this->uppct >= 1) throw new RuntimeException('EXPECTED_UPTIME must be between 0 and 1');
        $this->pcthosts = .335;
        $this->db = db();
        $this->filename = "/var/log/crap2.log";
        $this->logging = 1;
        $this->admin = !empty($_SESSION['admin']);
        $this->id = (int) ($queryVars['id'] ?? -1);
        $this->debug = 0;

        $this->debug ? ($this->status_tbl = 'teststatus') : ($this->status_tbl = 'status');
        $this->debug ? ($this->downtime_tbl = 'testdowntime') : ($this->downtime_tbl = 'downtime');

        $this->secinyear=31536000;
        $this->downsecs=$this->secinyear*(1-$this->uppct);
        $this->totaldown=0;

        $query="SELECT sum(total) FROM $this->downtime_tbl";
        $result=mysqli_query($this->db, $query) or die("query failed: $query");
        $row=mysqli_fetch_array($result);
        mysqli_free_result($result);
        $this->totaldown=(int) ($row[0] ?? 0);

        $query="SELECT MAX(ID) FROM $this->downtime_tbl";
        $result=mysqli_query($this->db, $query) or die("query failed: $query");
        $row = mysqli_fetch_array($result);
        mysqli_free_result($result);
        $this->maxDownID = $row[0];

        $query="SELECT * FROM $this->status_tbl WHERE `ID` = 1";
        $result=mysqli_query($this->db, $query) or die("query failed");
        $stat=mysqli_fetch_array($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        $this->status=$stat["status"];
        $this->lastdownID=$stat["lastdown"];
        $this->since=$stat["since"];

        $this->totaltime=max(1, time()-$this->since);

        $query="SELECT * FROM $this->downtime_tbl WHERE ID=$this->lastdownID";
        $result=mysqli_query($this->db, $query) or die("query failed: $query");
        $downtime=mysqli_fetch_array($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        $this->lastdown=(int) ($downtime["down"] ?? 0);
        $this->tdown=$downtime["total"] ?? null;
        $this->lasthosts=floor(($downtime["hostsup"] ?? 0)*100);

        if($this->tdown === null && $this->lastdown){
            $this->totaldown+=(time()-$this->lastdown);
        }
    }
    
    /**
     * Method to return the status of the AJAX transaction.
     * This method is truely just a wrapper for all the different
     * types of transactions.
     *
     * @return  string A string of raw HTML fetched from the Server
     */
    public function return_response()
    {
        if ($this->id > 0) {
            return $this->delDownRow($this->id);
        }
        else {
            $resp = array();
            $resp['id_overlay'] = $this->overlay();
            $resp['id_uptime'] = $this->uptime();
            $resp['id_totaldown'] = $this->totaldown();
            $resp['id_totalup'] = $this->totalup();
            $resp['id_status'] = $this->status();
            $resp['id_lastdown'] = $this->lastdown();
            $resp['id_downstat'] = $this->downstat();
            $resp['id_upstat'] = $this->upstat();
            $resp['id_startrecording'] = $this->startrecording();
            $resp['id_totalrecording'] = $this->totalrecording();
            $resp['id_acceptdown'] = $this->acceptdown();
            $resp['id_yearlyup'] = $this->yearlyup();
            $resp['id_hist'] = $this->hist();
            $resp['id_curtime'] = $this->curtime();
             
            return $resp;
        }
    }
    
    public function is_authorized()
    {
        return true;
    }
    
    /******************************
     * START OF MUDDLEWARE FUN :-D
     ******************************
     */

    private function overlay () {
        $downpct=($this->totaldown/($this->totaltime * (1 - $this->uppct)));
        if($downpct>1)
            $downpct=1;
        $width=$downpct*255;
        
        return "<img src=\"trans.png\" width=\"$width\" height=\"71\">";
    }

    private function uptime () {
        $downpct=($this->totaldown/$this->totaltime);
        
        $r=255;
        $g=255-(255*($downpct*2));
        $b=255-(255*($downpct*2));
        if($b<0)
            $b=0;
        if($g<0)
            $g=0;
        $up = floor((1-$downpct)*100000)/1000;
        
        return "<h1><font color=\"".sprintf("%02X%02X%02X",(int)$r,(int)$g,(int)$b)."\">$up%</font></h1> uptime";
        
    }

    private function totaldown () {
        return $this->convertTime($this->totaldown);
    }

    private function totalup () {
        return $this->convertTime((time()-$this->since)-$this->totaldown);
    }

    private function yearlyup () {
        $perct = floor((1 - $this->totaldown/$this->totaltime)*100000000)/1000000;
        
        return "$perct%</b> uptime over the recording period.";
    }

    private function status () {
        if(!$this->status)
            return "<font color=\"red\">down</font>";
        else
            return "<font color=\"white\">up</font>";
    }

    private function lastdown () {
        $retval = "<b>";

        if($this->lastdown){
            if($this->status)
                $retval .= date("D d M h:i:s A", $this->lastdown)." - <font size=-1> (".$this->convertTime(time()-$this->lastdown)." ago)</font>";
            else
                $retval .= "<font color=red>right now!</font>";
            $retval .= "<br></b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-\n";
            if($this->status)
                    $retval .= "It was down for ";
            else {
                    $this->tdown=time()-$this->lastdown;
                    $retval .= "It has been down for ";
                }
                $retval .= $this->convertTime($this->tdown);
                $retval .= " with ".$this->lasthosts."% of hosts responding";
        } else {
            $retval .= "{the connection has not yet gone down.}";
        }
        
        return $retval."</b>";
    }

    private function downstat () {
        $retval = "";

        if($this->lastdown){
            $query="SELECT MAX(total) FROM $this->downtime_tbl";
            $result=mysqli_query($this->db, $query) or die("Query failed: $query");
            $row=mysqli_fetch_array($result);
            mysqli_free_result($result);
            $maxdown=(int) ($row[0] ?? 0);
            $retval .= "<br><br>The longest downtime recorded: <b>";
            if(!$this->tdown)
                $this->tdown=time()-$this->lastdown;
            if($maxdown>$this->tdown || $this->status){
                $retval .= $this->convertTime($maxdown);
                $query="SELECT down FROM $this->downtime_tbl WHERE total=$maxdown";
                $result=mysqli_query($this->db, $query) or die("Query failed: $query");
                $row=mysqli_fetch_array($result);
                mysqli_free_result($result);
                $maxdowndate=$row[0];
                $retval .= "</b> on ".date("D M dS, Y", $maxdowndate).".";
            } else {
                $retval .= "<font color=\"red\">right now!</font></b>";
            }
        }

        return $retval;
    }

    private function upstat () {
        if($this->lastdown){
            $longestup=0;
            if($this->status)
                $longestup=time()-($this->lastdown+$this->tdown);
            $query="SELECT `down`,`total` FROM $this->downtime_tbl WHERE 1 ORDER BY `down` ASC";
            $result=mysqli_query($this->db, $query) or die("query failed: $query");
            $row=mysqli_fetch_array($result, MYSQLI_BOTH);
            if($row["down"]-$this->since > $longestup)
                $longestup=$row["down"]-$this->since;
            $last=$row["down"]+$row["total"];
            for(;$row=mysqli_fetch_array($result, MYSQLI_BOTH);){
                if(($row["down"]-$last)>$longestup)
                    $longestup=$row["down"]-$last;
                $last=$row["down"]+$row["total"];
            }
            mysqli_free_result($result);
            return "The longest uptime recorded: <b>".$this->convertTime($longestup)."</b>";
        } else {
            return "The connection has been up for <b>".$this->convertTime(time()-$this->since)."</b>";
        }
    }

    private function startrecording () {
        return date("m/d/y h\:i\ A", $this->since);
    }

    private function totalrecording () {
        return $this->convertTime(time()-$this->since)." of total recording.";
    }

    private function acceptdown () {
        $perct = $this->uppct*100;
        return "$perct% uptime)";
    }

    private function hist () {
        $ret = '<table cellpadding=0 cellspacing=0 width=50% border=0 style="border:thin solid black">
                <tr>
                    <td align=center class="statstd" bgcolor="#AAAAAA"><font color="black"><b>Date</b></font></td>
                    <td align=center class="statstd" bgcolor="#AAAAAA"><font color="black"><b>Time</b></font></td>
                    <td align=center class="statstd" bgcolor="#AAAAAA"><font color="black"><b>Total Downtime</b></font></td>
                    <td align=center class="statstd" bgcolor="#AAAAAA"><font color="black"><b>Hosts Responding</b></font></td>';
        if ($this->admin == 1) {
            $ret .= '<td align=center class="statstd" bgcolor="#AAAAAA"><font color="black"><b>del?</b></font></td>';
        }
        
        $ret .= '</tr>';
        
        $query="SELECT * FROM $this->downtime_tbl WHERE 1 ORDER BY down desc LIMIT 0, 10";
        $result=mysqli_query($this->db, $query) or die("query failed: $query");
        for(;$row=mysqli_fetch_array($result, MYSQLI_BOTH);){
            $ret .= "<tr>";
            $ret .= "<td class=\"statstd\" align=center>".date("D M dS, Y", $row["down"])."</td>";
            $ret .= "<td class=\"statstd\" align=center>".date("h\:i\:s A", $row["down"])."</td>";
            $ret .= "<td class=\"statstd\" align=center>";
            if($row["total"] === null){
                $ret .= "<font color=red>currently down!</font>";
            } else {
                $ret .= $this->convertTime($row["total"]);
            }
            $ret .= "</td>";
            $ret .= "<td class=\"statstd\" align=center>".round($row["hostsup"] * 100)."%</td>";

            if ($this->admin == 1) {
                $ret .= "<td class=\"statstd\" align=center><a href=\"#\" onclick=\"delHist(".$row["ID"].", 1);\">".$row["ID"]."</a></td>";
            }

            $ret .= "</tr>";
        }
        mysqli_free_result($result);

        return $ret . "</table>";
    }

    private function curtime () {
        return date("F j, Y, g:i:s a");
    }

    /*****************************
     * END OF MUDDLEWARE FUN :'-(
     *****************************
     */

    private function delDownRow ($i) {
        if (!$this->admin) { throw new RuntimeException('Not authorized'); }
        $this->db->begin_transaction();
        try {
            $stat = $this->db->query('SELECT * FROM status WHERE ID=1 FOR UPDATE')->fetch_assoc();
            if (!(int)$stat['status'] && (int)$stat['lastdown'] === $i) {
                throw new RuntimeException('Cannot delete an ongoing outage');
            }
            $stmt = $this->db->prepare('DELETE FROM downtime WHERE ID=?');
            $stmt->bind_param('i', $i);
            $stmt->execute();
            $this->db->query('UPDATE status SET lastdown=COALESCE((SELECT MAX(ID) FROM downtime),0) WHERE ID=1');
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        return '1';
    }

    private function convertTime($time) {
        $timestring="";

        $days=intval($time/86400);
        $time-=86400*$days;

        $hours=intval($time/3600);
        $time-=3600*$hours;

        $minutes=intval($time/60);
        $time-=60*$minutes;

        if($days>0) {
        $timestring.="$days day";
            if($days>1)
                    $timestring.="s";
            $timestring.=", ";
            }
        if($hours>0){
                $timestring.="$hours hour";
                    if($hours>1)
                            $timestring.="s";
                    $timestring.=", ";
        }
        if($minutes>0){
                $timestring.="$minutes minute";
                    if($minutes>1)
                            $timestring.="s";
                    if($hours>0)
                $timestring.=", and ";
            else
                $timestring.=" and ";
        }
        if($time>0) {
        $timestring.="$time second";
                    if($time>1)
                            $timestring.="s";
            } else {
                    $timestring.="0 seconds";
            }

            return $timestring;
    }
}

?>
