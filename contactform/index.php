<?php
session_start();

$_SESSION['role'] = 6;
$_SESSION['language'] = 'en_EN';
$_SESSION['property'] = '1';
$_SESSION['propertyID'] = '1';
$_SESSION['outletID'] = '';
// PHP part of page / business logic
// ** set configuration
	require("config.php");

	include('../config/config.general.php');
// ** business functions
	require('includes/business.class.php');
// ** database functions
	include('../web/classes/database.class.php');
// ** localization functions
	include('../web/classes/local.class.php');
// ** business functions
	include('../web/classes/business.class.php');
// ** connect to database
	include('../web/classes/connect.db.php');
// ** all database queries
	include('../web/classes/db_queries.db.php');
// ** set configuration
	include('../config/config.inc.php');
// translate to selected language
	translateSite(substr($_SESSION['language'],0,2));

//standard outlet for contact form
	if ($_GET['outletID']) {
		$_SESSION['outletID'] = (int)$_GET['outletID'];
		// set single outlet indicator
		$_SESSION['single_outlet'] = 'ON';
	}else if (!$_SESSION['outletID']){
		$_SESSION['outletID'] = querySQL('web_standard_outlet');
		// reset single outlet indicator
		$_SESSION['single_outlet'] = 'OFF';
	}


// ** get superglobal variables
	include('../web/includes/get_variables.inc.php');
// CSRF - Secure forms with token
	$barrier = md5(uniqid(rand(), true)); 
	$_SESSION['barrier'] = $barrier;


// Get POST data	
   // outlet id
    // property id
    if ($_GET['prp']) {
        $_SESSION['property'] = (int)$_GET['prp'];
    }elseif ($_POST['prp']) {
        $_SESSION['property'] = (int)$_POST['prp'];
    }

  //prepare selected Date
    list($sy,$sm,$sd) = explode("-",$_SESSION['selectedDate']);
  
	// get outlet maximum capacity
	$maxC = maxCapacity(); 
	 
	// get Pax by timeslot
    $resbyTime = reservationsByTime('pax');
    $tblbyTime = reservationsByTime('tbl');

    // get availability by timeslot
    $availability = getAvailability($resbyTime,$general['timeintervall']);
    $tbl_availability = getAvailability($tblbyTime,$general['timeintervall']);

  // some constants
    $outlet_name = querySQL('db_outlet');
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="utf-8"/>

	<!-- CSS - Setup -->
	<link href="style/style.css" rel="stylesheet" type="text/css" />
	<link href="style/base.css" rel="stylesheet" type="text/css" />
	<link href="style/grid.css" rel="stylesheet" type="text/css" />
	<!-- CSS - Theme -->
	<link id="theme" href="style/themes/<?= $default_style;?>.css" rel="stylesheet" type="text/css" />
	<link id="color" href="style/themes/<?= $default_color;?>.css" rel="stylesheet" type="text/css" />
	<!-- CSS - Datepicker -->
	<link href="style/datepicker.css" rel="stylesheet" type="text/css" />

    <!-- jQuery Library-->
    <script src="js/jQuery.min.js"></script>
    <script src="js/jquery.easing.1.3.js"></script>
    <script type="text/javascript" src="js/jquery-ui.js"></script> 
    <script src="js/functions.js"></script>
    

	<!--[if IE 6]>
		<script src="js/DD_belatedPNG.js"></script>
		<script>
			DD_belatedPNG.fix('#togglePanel, .logo img, #mainBottom, #twitter, ul li, #searchForm, .footerLogo img, .social img');
		</script>
	<![endif]--> 

    <title>Reservation</title>
</head>
<body>
	<!-- start header -->
	<div id="wrapper"> 
	  <header> 
	    <!-- logo -->
	    <h1 id="logo"><a href="index.php?p=2">mySeat</a></h1>
	    <!-- nav -->
	    <nav>
	      <ul id="nav">
	        <li><a href="<?= $home_link;?>">Home</a></li>
	        <li <? if($p == 2){echo'class="current"';} ?> ><a href="cancel.php?p=2"><?= $lang["contact_form_cxl"];?></a>
	      </ul>
	      <br class="cl" />
	    </nav>
	    <br class="cl" />
	  </header>
	<!-- end header -->
	<!-- page container -->
	  <div id="page"> 
	    <!-- page title -->
	    <h2 class="ribbon full"><?= $lang["conf_title"];?><span></span> </h2>
	    <div class="triangle-ribbon"></div>
	    <br class="cl" />
	    
	    <div id="page-content" class="container_12">
		
		<!-- page content goes here -->	


			<?php lang("contact_form_intro"); ?>
			<br/>
			<h3><?= $outlet_name." - ".date($general['dateformat'],strtotime($_SESSION['selectedDate'])); ?></h3>
			
			<?php
				// Generate captcha fields
				$captchaField1 = rand(1, 10);
				$captchaField2 = rand(1, 20);
				$captchaField3 = rand(1, 10);
				$captchaField2 = ($captchaField2%2) ? "+" : "-";
			?>

		<form action="process_booking.php" method="post" id="contactForm" style="margin-left:30px">
		    
		    <!-- Datepicker -->
		    <label> </label><div id="bookingpicker"></div>
		    <input type="hidden" name="dbdate" id="dbdate" value="<?= $_SESSION['selectedDate']; ?>"/>
      
		    <input type="hidden" name="reservation_date" value="<?= $_SESSION['selectedDate'];?>">
		    <input type="hidden" name="recurring_dbdate" value="<?= $_SESSION['selectedDate']; ?>"/>
            <br/><br/>
		    <!-- END datepicker -->
		    <div>
			<?php
			$num_outlets = 0;
			if ($_SESSION['single_outlet'] == 'OFF') {
				$num_outlets = querySQL('num_outlets');
			}
			
				if ($num_outlets>1) {
					echo"<label>RESTAURANT</label><br/>";
					$outlet_result = outletList($_SESSION['outletID'],'enabled','reservation_outlet_id');
					echo "<input type='hidden' id='single_outlet' value=''>";
				} else{
					echo "<input type='hidden' name='reservation_outlet_id' id='single_outlet' value='".$_SESSION['outletID']."'>";
				}
			
			// MESSAGES
			//Day off error message
			$day_off = getDayoff();
            if ($outlet_result == 1) {
				echo "<div class='alert_error'><p><img src='../web/images/icon_error.png' alt='error' class='middle'/>&nbsp;&nbsp;";
				echo lang('error_dayoff')."<br>";
				echo "</p></div>";
			}

			// Special event advertise
			$events_advertise = '';
			$events_advertise = querySQL('event_advertise_web');
			// Special event of the day and outlet
			$special_events = '';
			$special_events = querySQL('event_data_day');
			
			if ( $events_advertise || $special_events ) {
				if ( $special_events ) {
					echo "<div class='alert_info'>";
					$advertise = $special_events;
				}else{
					echo "<div class='alert_tip'>";
					$advertise = $events_advertise;
				}
			
					// special events
					foreach($advertise as $row) {
						echo "<p style='margin-bottom:6px;'>
						<img src='../web/images/icon_cutlery.png' alt='special' class='middle'/>
						<strong><a href='".$_SERVER['SCRIPT_NAME']."?outletID=".$row->outlet_id."&selectedDate=".$row->event_date."'>";
						echo ( $special_events ) ? _today : _sp_events;
						echo ": ".$row->subject.
						"</a></strong><br/><div style='margin-left:36px; font-size:0.8em; line-height:1.2em;'>".
						date($general['dateformat'],strtotime($row->event_date)) 
						." ".formatTime($row->start_time,$general['timeformat']).
						" - ".formatTime($row->end_time,$general['timeformat'])."<br/>".
						$row->outlet_name."<br/>".
						_open_to." ".$row->open_to."<br/>".
						_ticket_price.": ".number_format($row->price,2).
						"<br/><br/></div><div style='margin-left:36px; font-size:0.9em; line-height:1.2em; width:80%'>".
						$row->description."<br/></div><br/></p>";
					}
				echo "</div>";
			}
			
			?>
			
			<br/><br/>
		    </div>
		    <div>
			<label><?php lang("contact_form_time"); ?></label><br/>
			<?php
			    timeList($general['timeformat'], $general['timeintervall'],'reservation_time','',$_SESSION['selOutlet']['outlet_open_time'],$_SESSION['selOutlet']['outlet_close_time'],0);
			?>
		    </div>
		    <br/>
		    <div>
			<label><?php lang("contact_form_title"); ?></label><br/>
			<?php
			    titleList();
			?>
		    </div>
		    <br/>
		    <div>
			<label><?php lang("contact_form_name"); ?></label><br/>
                        <input type="text" name="reservation_guest_name" class="form required" id="reservation_guest_name" value="" />
                    </div>
		    <br/>
		    <div>
			<label><?php lang("contact_form_pax"); ?></label><br/>
                        <?php
							//personsList(max pax before menu , standard selected pax);
						    personsList($general['max_menu'],2);
						?>
                    </div>
		    <br/>
                    <div>
			<label><?php lang("contact_form_email"); ?></label><br/>
                        <input type="text" name="reservation_guest_email" class="form required email" id="reservation_guest_email" value="" />
                    </div>
		    <br/>
		    <div>
			<label><?php lang("contact_form_phone"); ?></label><br/>
                        <input type="text" name="reservation_guest_phone" class="form required" id="reservation_guest_phone" value="" />
                    </div>
		    <br/>
                    <div>
			<label><?php lang("contact_form_notes"); ?></label><br/>
                	<textarea cols="50" rows="10" name="reservation_notes" class="form" id="reservation_notes" ></textarea>
                    </div>
		    <br/>
		    <div class="side">
                	<div class="captchaContainer">
                		<label for="captcha">
		            		<span id="captchaField1" class="captchaField"><?php echo $captchaField1; ?></span>
		            		<input type="hidden" name="captchaField1" value="<?php echo $captchaField1; ?>"/>

		            		<span id="captchaField2" class="captchaField"><?php echo $captchaField2; ?></span>
		            		<input type="hidden" name="captchaField2" value="<?php echo $captchaField2; ?>"/>

							<span id="captchaField3" class="captchaField"><?php echo $captchaField3; ?></span>
							<input type="hidden" name="captchaField3" value="<?php echo $captchaField3; ?>"/>
							
							<span class="captchaField">=</span>
						</label>
                		<input type="text" name="captcha" class="form required captcha" id="captcha" value="" />
                  		
	            		
                		<div class="clear"></div>
                	</div>
		    </div> 
                	<div class="oh">
				<input type="hidden" name="action" id="action" value="submit"/>
				<input type="hidden" name="barrier" value="<?php echo $barrier; ?>" />
				<input type="hidden" name="reservation_hotelguest_yn" id="reservation_hotelguest_yn" value="PASS"/>
				<input type="hidden" name="reservation_booker_name" id="reservation_booker_name" value="Contact Form"/>
				<input type="hidden" name="reservation_author" id="reservation_author" value="<?= querySQL('db_property');?> Team"/>
				<input type="hidden" name="email_type" id="email_type" value="<?php echo $language; ?>"/>
                <?php
				$day_off = getDayoff();
                if ($day_off == 0) {
                	echo"<div style='text-align:center;'><br/><br/><input class='button ".$default_color." large' type='submit' value='".$lang['contact_form_send']."' /></div>";
                }
                ?>	
                	</div>
		</form>

	    <br class="cl" />
	    <br class="cl" />		
		</div><!-- page content end -->
	</div><!-- page container end -->
			
  <!-- Footer Start -->
  <footer>

    <p><small>&copy; 2010 by MYSEAT under the GPL license, designed deep in the heart of Germany.</small></p>
    <br class="cl" />
  </footer>
  <!-- footer end -->
</div><!-- main close -->

  <!-- Javascript at the bottom for fast page loading --> 
<script>
    jQuery(document).ready(function($) {
      // Setup datepicker input at customer reservation form
      $("#bookingpicker").datepicker({
	      nextText: '&raquo;',
	      prevText: '&laquo;',
	      firstDay: 1,
	      numberOfMonths: 2,
		  minDate: 0,
	      gotoCurrent: true,
	      altField: '#dbdate',
	      altFormat: 'yy-mm-dd',
	      defaultDate: 0,
	      dateFormat: '<?= $general['datepickerformat'];?>',
	      regional: '<?= substr($_SESSION['language'],0,2);?>',
	      onSelect: function(dateText, inst) { window.location.href="?selectedDate=" + $("#dbdate").val() + "&outletID=" + $("#single_outlet").val(); }
      });
      // month is 0 based, hence for Feb. we use 1
      $("#bookingpicker").datepicker('setDate', new Date(<?= $sy.", ".($sm-1).", ".$sd; ?>));
      $("#ui-datepicker-div").hide();
      $("#reservation_outlet_id").change(function(){
	    window.location.href='?outletID=' + this.value;
	  });
    });
</script>

</body>
</html>