<!DOCTYPE html>
<?php


require "grab_globals.inc.php";
require_once "systemdefaults.inc.php";
require_once "areadefaults.inc.php";
require_once "internalconfig.inc.php";
require "style.inc";
require_once "language.inc";
require_once "mincals.inc";

if(empty($_GET['year'])){
  $year= date('Y');
  $_GET['year']=$year;
}else{
  $year=$_GET['year'];
}
if(empty($_GET['month'])){
 $month=date('m');
  $_GET['month']=$month;
}else{
  $month=$_GET['month'];
}
if(empty($_GET['day'])){
  $day=date('d');
  $_GET['day']=$day;
}else{
  $day=$_GET['day'];
}
$area=10;
$room=7;

$timestamp = mktime(12, 0, 0, $month, $day, $year);
//print_header($day, $month, $year, $area, isset($room) ? $room : "");
echo "<div id=\"dwm_header\" class=\"screenonly\">\n";
minicals($year, $month, $day, $area, $room, 'cc');
echo "</div>\n";


echo "<hr>\n";
echo "<div id=\"dwm\">\n";
echo "<h2>" . utf8_strftime($strftime_format['date'], $timestamp) . "</h2>\n";

echo "</div>\n";

$i= mktime(12,0,0,$month-1,1,$year);
$yy = date("Y",$i);
$ym = date("n",$i);
$yd = $day;
while (!checkdate($ym, $yd, $yy) && ($yd > 1))
{
  $yd--;
}

$i= mktime(12,0,0,$month+1,1,$year);
$ty = date("Y",$i);
$tm = date("n",$i);
$td = $day;
while (!checkdate($tm, $td, $ty) && ($td > 1))
{
  $td--;
}

$cy = date("Y");
$cm = date("m");
$cd = $day;    // preserve the day information
while (!checkdate($cm, $cd, $cy) && ($cd > 1))
{
  $cd--;
}
$before_after_links_html = "<div class=\"screenonly\">
  <div class=\"date_nav\">
    <div class=\"date_before\">
      <a href=\"cc.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area&amp;room=$room\">
          <<< &nbsp;".get_vocab("monthbefore")."
        </a>
    </div>
    <div class=\"date_now\">
      <a href=\"cc.php?year=$cy&amp;month=$cm&amp;day=$cd&amp;area=$area&amp;room=$room\">
          ".get_vocab("gotothismonth")."
        </a>
    </div>
    <div class=\"date_after\">
       <a href=\"cc.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area&amp;room=$room\">
          ".get_vocab("monthafter")."&nbsp; >>>
        </a>
    </div>
  </div>
</div>
";
print $before_after_links_html;
$month_start = mktime(0, 0, 0, $month, 1, $year);

// What column the month starts in: 0 means $weekstarts weekday.
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;

$days_in_month = date("t", $month_start);

$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);

echo "<table class=\"dwm_main\" id=\"month_main\">\n";

// Weekday name header row:
echo "<thead>\n";
echo "<tr>\n";
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
  if (is_hidden_day(($weekcol + $weekstarts) % 7))
  {
    // These days are to be hidden in the display (as they are hidden, just give the
    // day of the week in the header row 
    echo "<th class=\"hidden_day\">" . day_name(($weekcol + $weekstarts)%7) . "</th>";
  }
  else
  {
    echo "<th>" . day_name(($weekcol + $weekstarts)%7) . "</th>";
  }
}
echo "\n</tr>\n";
echo "</thead>\n";

// Main body
echo "<tbody>\n";
echo "<tr>\n";

// Skip days in week before start of month:
for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
{
  if (is_hidden_day(($weekcol + $weekstarts) % 7))
  {
    echo "<td class=\"hidden_day\"><div class=\"cell_container\">&nbsp;</div></td>\n";
  }
  else
  {
    echo "<td class=\"invalid\"><div class=\"cell_container\">&nbsp;</div></td>\n";
  }
}

// Draw the days of the month:
for ($cday = 1; $cday <= $days_in_month; $cday++)
{
  // if we're at the start of the week (and it's not the first week), start a new row
  if (($weekcol == 0) && ($cday > 1))
  {
    echo "</tr><tr>\n";
  }
  
  // output the day cell
  if (is_hidden_day(($weekcol + $weekstarts) % 7))
  {
    // These days are to be hidden in the display (as they are hidden, just give the
    // day of the week in the header row 
    echo "<td class=\"hidden_day\">\n";
    echo "<div class=\"cell_container\">\n";
    echo "<div class=\"cell_header\">\n";
    // first put in the day of the month
    echo "<span>$cday</span>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</td>\n";
  }
  else
  {   
    echo "<td class=\"valid\">\n";
    echo "<div class=\"cell_container\">\n";
    
    echo "<div class=\"cell_header\">\n";
    // If it's a Monday (the start of the ISO week), show the week number
    if ($view_week_number && (($weekcol + $weekstarts)%7 == 1))
    {
      echo "<a class=\"week_number\" href=\"week.php?year=$year&amp;month=$month&amp;day=$cday&amp;area=$area&amp;room=$room\">";
      echo date("W", gmmktime(12, 0, 0, $month, $cday, $year));
      echo "</a>\n";
    }
    // then put in the day of the month
    //echo "<a class=\"monthday\" href=\"day.php?year=$year&amp;month=$month&amp;day=$cday&amp;area=$area\">$cday</a>\n";
    echo "<a class=\"monthday\" href=\"\">$cday</a>\n";

    echo "</div>\n";
    // then any bookings for the day
    if (true)
    {
      //echo "<div class=\"booking_list\">\n";
      echo "<div class=\"\">\n";
      $n = 1;
      // Show the start/stop times, 1 or 2 per line, linked to view_entry.
      for ($i = 0; $i < $n; $i++)
      {
        // give the enclosing div the appropriate width: full width if both,
        // otherwise half-width (but use 49.9% to avoid rounding problems in some browsers)
        $class = $d[$cday]["color"][$i];  
        echo "<div class=\"" . $class . "\"" .
          " style=\"width: " . (($monthly_view_entries_details == "both") ? '100%' : '49.9%') . "\">\n";    
        $yoyaku="Order";
        echo "<a href=\"$booking_link\" title=\"$full_text\">";
        echo ($d[$cday]['is_repeat'][$i]) ? "<img class=\"repeat_symbol\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
        echo "$yoyaku</a>\n";
        echo "</div>\n";
      }
      echo "</div>\n";
    }
    
    echo "</div>\n";
    echo "</td>\n";
  }
  
  // increment the day of the week counter
  if (++$weekcol == 7)
  {
    $weekcol = 0;
  }

} // end of for loop going through valid days of the month

// Skip from end of month to end of week:
if ($weekcol > 0)
{
  for (; $weekcol < 7; $weekcol++)
  {
    if (is_hidden_day(($weekcol + $weekstarts) % 7))
    {
      echo "<td class=\"hidden_day\"><div class=\"cell_container\">&nbsp;</div></td>\n";
    }
    else
    {
      echo "<td class=\"invalid\"><div class=\"cell_container\">&nbsp;</div></td>\n";
    }
  }
}
echo "</tr></tbody></table>\n";


?>