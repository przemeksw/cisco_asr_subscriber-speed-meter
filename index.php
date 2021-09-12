<?php
//error_reporting(E_ALL);
$ROUTER_IP=array("ASR_PRABUTY"=>"45.128.111.250","ASR_KWIDZYN"=>"45.128.111.251");
$ROUTER_SNMP_COM="ASar12jfa123";
$OID_WALK_ALL_SESSION="1.3.6.1.4.1.9.9.150.1.1.3.1.2";
$OID_GET_INTERFACE_ID="1.3.6.1.4.1.9.9.150.1.1.3.1.8";
$OID_GET_SUBSCRIBER_IP="1.3.6.1.4.1.9.9.150.1.1.3.1.3";
snmp_set_valueretrieval(SNMP_VALUE_PLAIN);

if ($_GET['monitoruj']=='true')
{
    $subscriber_interface_id=$_GET['subscriber_interface_id'];
    $router_ip=$_GET['router_ip'];
    $session_monitoruj = new SNMP(SNMP::VERSION_2c, $router_ip, $ROUTER_SNMP_COM);
    $subscriber_ifInOctets = $session_monitoruj->get('1.3.6.1.2.1.2.2.1.10.'.$subscriber_interface_id);
    $subscriber_ifInUcastPkts = $session_monitoruj->get('1.3.6.1.2.1.2.2.1.11.'.$subscriber_interface_id);
    $subscriber_ifOutOctets = $session_monitoruj->get('1.3.6.1.2.1.2.2.1.16.'.$subscriber_interface_id);
    $subscriber_ifOutUcastPkts = $session_monitoruj->get('1.3.6.1.2.1.2.2.1.17.'.$subscriber_interface_id);
    $subscriber_ifMtu = $session_monitoruj->get('1.3.6.1.2.1.2.2.1.4.'.$subscriber_interface_id);
    $json=array('ifInOctets' => $subscriber_ifInOctets, 'ifInUcastPkts' => $subscriber_ifInUcastPkts, 'ifOutOctets' => $subscriber_ifOutOctets, 'ifOutUcastPkts' =>$subscriber_ifOutUcastPkts , 'ifMtu' => $subscriber_ifMtu);
    echo json_encode($json);
    exit;
}

if ($_GET['load_site']=='true')
{
    foreach ($ROUTER_IP as $router_key =>$router_val)
    {
	$session = new SNMP(SNMP::VERSION_2c, $router_val, $ROUTER_SNMP_COM);
	$subscriber_list = $session->walk($OID_WALK_ALL_SESSION);
	foreach ($subscriber_list as $sub_key =>$sub_val) 
	{
	    preg_match('/^(.*)\.(.*)\.(.*)\.(.*)\.(.*)\.(.*)\.(.*)\.(.*)\.(.*)\.(.*)/', $sub_key, $sub_matches);
	    $subscriber_interface_id = $session->get($OID_GET_INTERFACE_ID.".".$sub_matches[10]);
    	    $subscriber_ip = $session->get($OID_GET_SUBSCRIBER_IP.".".$sub_matches[10]);
    	    $json[] = array('id'=>$subscriber_interface_id, 'text'=>$sub_val, 'data-router'=>$router_val,'data-sub_ip'=>$subscriber_ip);
        }
	$session->close();
    }
    echo json_encode($json);
    exit;
}

?>

<html>
<head>
<title>CISCO ASR - Subscriber bandwidth monitoring</title>
<meta name="title" content="CISCO ASR - Subscriber bandwidth monitoring"/>
<meta name="author" content="Przemysław Świderski" />
<meta name="description" content="p.swiderski@viphost.it" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<link href="css/bootstrap5/bootstrap.min.css" rel="stylesheet" type="text/css"/>
<link href="css/select2/select2.min.css" rel="stylesheet" type="text/css"/>
<link href="css/preloader.css" rel="stylesheet" type="text/css"/>
<script src="js/jquery-3.6.0.min.js"></script>
<script src="js/bootstrap5/bootstrap.min.js"></script>
<script src="js/select2/select2.full.min.js"></script>
</head>
<body >
<div class="preloader" id="preloader">  
    <div class="loader" id="loader"></div>
    <span><center><b>Trwa pobieranie danych z routerów.</b></center></span>
</div>

<div class="mb-5"><center><h4>Wyświetlanie aktualnej przepustowości abonentow PPPoE z routerow Cisco ASR</h4></center></div>
<div class="container" style="display:none;">
  <div class="row">
    <div class="col">
	<select class="form-select subscriber_select_list">
	</select>
    </div>
    <div class="col">
	<div id="btn_akcja"><button id="monitoruj" class="btn btn-success monitoruj" >Uruchom monitoring</button></div>
    </div>    
  </div>
  <div class="row">
    <div class="col">
	<b>Odświeżenie danych za</b>: <div style="display: inline;" class="timer"></div> <Br><br>
	<b>Pobieranie aktualnej prędkości dla adresu IP:</b> <div style="display: inline;" class="subscriber_ip"></div> <br>
	<b>Router:</b> <div style="display: inline;" class="subscriber_router"></div> <br>
	<b>Pobieranie:</b> <div style="display: inline;" class="download_speed"></div> Mbps <br>
	<b>Wysyłanie:</b> <div style="display: inline;" class="upload_speed"></div> Mbps
    </div>
  </div>  
</div>
 <footer class="page-footer font-small purple pt-4 fixed-bottom">
    <div class="footer-copyright text-center py-3">© 2021 Copyright:
      <a href="mailto:p.swiderski@viphost.it"> Przemysław Świderski - viphost.it</a>
    </div>
  </footer>
</body>
<script>


$(document).ready(function() {

    $.ajax({
            url: 'index.php',
            dataType: 'json',
            type: 'get',
                async: true,
                data: {'load_site':'true'},
                success: function( data, textStatus, jQxhr ){
        	    var len = data.length;
        	    console.log(len);
        	    for( var i = 0; i<len; i++)
        	    {
        	        var id = data[i]['id'];
                	var text = data[i]['text'];
                	var sub_ip = data[i]['data-sub_ip'];
                	var router = data[i]['data-router'];
        		$('.subscriber_select_list').append('<option data-sub_ip="'+sub_ip+'" data-router="'+router+'" value="'+id+'">'+text+'</option>');

            	    }
		    $('.subscriber_select_list').select2({ tags: true, cache: false, width:'100%', allowClear: true, placeholder: 'Wyszukaj abonenta PPPoE'});
		    $(".preloader").hide();
		    $(".container").show();

                },
                error: function( jqXhr, textStatus, errorThrown ){
                    console.log( errorThrown );
                }
            });
    	
    var subscriber_router=null;
    var subscriber_id=null;
    var ajax_refresh_Interval;
    var counter;
    var down_speed_old;
    var up_speed_old;
    var count = 11;

    $('.subscriber_select_list').on('change', function() {
	subscriber_id =this.value;
	subscriber_router=$(this).find(':selected').attr('data-router');
    });

    $(document).on("click", "button.stop_monitoruj" , function() {
	clearInterval(ajax_refresh_Interval);
	clearInterval(counter);
	$(this).removeClass("btn-danger");
	$(this).addClass("btn-success");
	$(this).removeClass("stop_monitoruj");
	$(this).addClass("monitoruj");
	$('#monitoruj').html("Uruchom monitorowanie");
	$(".timer").html("");
	$('.subscriber_ip').html("");
	$('.download_speed').html("");
	$('.upload_speed').html("");
    });

    $(document).on("click", ".monitoruj" , function() {
	$('#monitoruj').removeClass("btn-success");
	$('#monitoruj').addClass("btn-danger");
	$('#monitoruj').removeClass("monitoruj");
	$('#monitoruj').addClass("stop_monitoruj");
	$('#monitoruj').html("Zatrzymaj monitorowanie");
	$('.subscriber_ip').html($('.subscriber_select_list').find(':selected').attr('data-sub_ip'));
	$('.subscriber_router').html($('.subscriber_select_list').find(':selected').attr('data-router'));
	count = 11;
	counter = setInterval(timer, 1000);
	ajax_refresh_Interval=setInterval(function(){
	    count = 11;
	    $.ajax({
                url: 'index.php',
                dataType: 'json',
                type: 'get',
                data: {'monitoruj':'true','subscriber_interface_id':subscriber_id, 'router_ip':subscriber_router},
                success: function( data, textStatus, jQxhr ){
                    var actual_up_speed=((((data['ifInOctets']-up_speed_old)/11)*8)/(1024*1024))*1.15;
                    up_speed_old=data['ifInOctets'];
                    $(".upload_speed").html(actual_up_speed.toFixed(3));
                    var actual_down_speed=((((data['ifOutOctets']-down_speed_old)/11)*8)/(1024*1024))*1.15;
                    down_speed_old=data['ifOutOctets'];
                    $(".download_speed").html(actual_down_speed.toFixed(3));

                },
                error: function( jqXhr, textStatus, errorThrown ){
                    console.log( errorThrown );
                }
            });
    	},11000);

    });
    
    function timer()
    {
        --count;
        var minutes = Math.floor(count / 60);
        var sec = count % 60;
        if(sec<10) sec = '0' + sec;
        var out = minutes + ':' + sec;
        $(".timer").html(out);
        if( count <= 0) 
        {
            return; 
        }
    }
});

</script>

</html>