	var clock;
		var d2 = new Date();
	 var d1 = new Date("2016","01","07","14","00","00");
	  var seconds =  (d1- d2)/1000;
	  console.log(seconds);
	  //alert(seconds.getSeconds());
		$(document).ready(function() {
			var clock;

			clock = $('.clock').FlipClock({
		        clockFace: 'DailyCounter',
		        autoStart: false,
		        callbacks: {
		        	stop: function() {
		        		$('.message').html('The clock has stopped!')
		        	}
		        }
		    });
	 
	
		    clock.setTime(seconds);
		    clock.setCountdown(true);
		    clock.start();

		});