<?php
require_once __DIR__ . "/session.php";

#-------------------------------------------------------
# C.R.A.P v2.0
# Charter Reliability Automation Project
# Frontend to AJAX framework
#
# @author Noah Pritikin
# Created on: 6/30/06
#
# @version $Revision: 1.18 $
# @author Last Modified by: $Author: noah $
# @since $Date: 2008/10/07 21:30:25 $
#-------------------------------------------------------

function utime (){
    $time = explode( " ", microtime());
    $usec = (double)$time[0];
    $sec = (double)$time[1];
    return $sec + $usec;
}

$start = utime();

$titlec=array("Caustic ", "Craptastic ", "Crap ", "Connection ", "Connectivity ", "Cool ");
$titler=array("Relative ", "Reliability ", "Realistic ", "Relativistic ", "Reporting ", "Report ", "Recording ");
$titlea=array("Annoyance ", "Accountability ", "Apathy ", "Average ", "Activity ");
$titlep=array("Program", "Product", "Percentage", "Project");

$isp_img = "quantum-fiber.svg";
$isp_img_w = "255";
$isp_img_h = "71";
$isp_url = "https://www.quantumfiber.com/";
$a = 0;

if(!empty($_GET['who'])){
    echo base64_decode("PGh0bWw+CjxoZWFkPgo8L2hlYWQ+Cjxib2R5IGJnY29sb3I9d2hpdGU+CjxjZW50ZXI+Cjx0YWJsZSBjZWxscGFkZGluZz0xIGJvcmRlcj0xPgo8dHI+Cjx0aD5DUkFQIHYyLjA8L3RoPgo8dGg+Q1JBUCB2MS4wPC90aD4KPC90cj4KPHRyPgo8dGQ+CjxoMT48Zm9udCBjb2xvcj1yZWQ+QXV0aG9yOiBOb2FoIFByaXRpa2luPGJyPkRhdGU6IDYvMzAvMjAwNjwvZm9udD48L2gxPgo8L3RkPgo8dGQ+CjxoMT48Zm9udCBjb2xvcj1ibHVlPkF1dGhvcjogSmFjb2IgRmFya2FzPGJyPkRhdGU6IDIvMjAvMjAwMzwvZm9udD48L2gxPgo8L3RkPgo8L3RhYmxlPgo8aW1nIHNyYz0id3RmLmpwZyI+PC9jZW50ZXI+CjwvYm9keT4KPC9odG1sPg==");
} else if(!empty($_GET['time'])) {
    echo ("<html><body>".time()."</body></time>");
} else {

$a = !empty($_SESSION['admin']) ? 1 : 0;

?>

<HTML>
<HEAD>
<TITLE>CRAP!! v2.0</TITLE>
<link type="text/css" rel="stylesheet" href="style.css">

<script>

const csrf = <?php echo json_encode($_SESSION['csrf']); ?>;
async function request(id = -1) {
    const response = await fetch('mybic_server.php', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'crap', id, csrf})
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Request failed');
    return data;
}
async function loadContent() {
    try {
        const data = await request();
        for (const [id, value] of Object.entries(data)) {
            const element = document.getElementById(id);
            if (element) element.innerHTML = value;
        }
    } catch (e) { document.getElementById('id_status').textContent = e.message; }
    setTimeout(loadContent, 1000);
}
async function delHist(id) {
    if (!confirm('Delete this completed outage?')) return;
    try { await request(id); } catch (e) { alert(e.message); }
}

</script>
</HEAD>
<body link="#FFFFF" alink="#FFFFF" vlink="#FFFFF" onload="loadContent(<?php echo "$a"; ?>);">
<p style="text-align:right"><a href="admin.php">History administration</a></p>
<div class="header">
<center><h1>C&nbsp;&nbsp;&nbsp;R&nbsp;&nbsp;&nbsp;A&nbsp;&nbsp;&nbsp;P&nbsp;&nbsp;&nbsp;v2.0</h1><br>
<font size=-1>
<?php
    $x=rand(0,(count($titlec)-1));
    echo $titlec[$x];
    $x=rand(0,(count($titler)-1));
    echo $titler[$x];
    $x=rand(0,(count($titlea)-1));
    echo $titlea[$x];
    $x=rand(0,(count($titlep)-1));
    echo $titlep[$x];
?></font>!</center>
</div>
<div class=main>
<table width=100% border=0 cellpadding=3>
    <tr>
        <td width=20px></td><td class="infotd">
            <center>
                <b>Current crap level:</b>
                <div style="position: relative; width: <?php echo($isp_img_w); ?>px; height: <?php echo($isp_img_h); ?>px;">
                    <div class=poo>
                    <a href="<?php echo($isp_url); ?>"><img src="<?php echo($isp_img); ?>" width="<?php echo($isp_img_w); ?>" height="<?php echo($isp_img_h); ?>" border="0" alt="Quantum Fiber"></a>
                    </div>
                    <span class=overlay id=id_overlay></span>
                </div>
                <font size=-2>(Red represents total amount of downtime out of an acceptable limit of <span id=id_acceptdown></span></font>
                <br>
                <span id=id_uptime></span>
            </center>
            The connection has been <b>down</b> for a total of:<br>&nbsp;&nbsp;&nbsp;&nbsp;<b>
            <span id=id_totaldown></span>
            </b><br>
            The connection has been <b>up</b> for a total of:<br>&nbsp;&nbsp;&nbsp;&nbsp;<b>
            <span id=id_totalup></span>
            </b><br>
            That translates to approximately <b><span id=id_yearlyup></span>
        </td>
        <td width=30px></td>
        <td class="infotd">
            Current connection status is: <b><span id=id_status></span></b>
            <br>
            Current time is: <b><span id=id_curtime></span></b>
            <br>
            Connection last down:
            <span id=id_lastdown></span>
            <span id=id_downstat></span>
            <br>
            <span id=id_upstat></span>
        </td>
        <td width=35px></td>
    </tr>
    <tr>
        <td colspan=4><br><br>
            <center>Last 10 downtimes:
                <span id=id_hist></span>
            </table>
        </td>
    </tr>
</table>
    <center><br>
    Recording started <span id=id_startrecording></span><br>
    That's <span id=id_totalrecording></span><br>
    <font size=-1">Page generated in <?php echo substr((utime()-$start), 0, 5); ?> seconds</font>
    <br>
    <br><font size="-1">Copyright 2003 Jacob Farkas and 2006 Noah Pritikin</font>
    </center>
</div>
</body>
</HTML>

<?php
}

?>
