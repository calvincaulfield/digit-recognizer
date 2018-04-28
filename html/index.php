<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="Cache-Control" content="no-store" />
<meta name="robots" content="noindex">
<link rel="stylesheet" href="/css/mystyle.css" />
<script type="text/javascript" src="/lib/jquery/jquery.js"></script>
<script type="text/javascript" src="handwriting_network.js"></script>
<script type="text/javascript" src="neural_network.js"></script>
<script>
	let canvas = null;
	let context = null;
	let isDrawingNow = false;
	var currentPoint = null;
	
	let info1 = null;
	let info2 = null;
	let info3 = null;
		
	$(document).ready(function() {
		//alert(JSON.stringify(neuralNetwork));
		canvas = document.getElementById('input-canvas');
		canvas.onmousedown = mouseDown;
		canvas.onmouseup = mouseUp;
		canvas.onmousemove = mouseMove;
		
		canvas.touchstart = function(event) {
			var touch = event.touches[0];
			mouseDown(touch.clientX, touch.clientY);
		}
		
		canvas.touchend = function(event) {
			mouseUp(event);
		};
		
		canvas.touchmove = function(event) {
			var touch = event.touches[0];
			mouseMove(touch.clientX, touch.clientY);
		};
		
		let container = document.getElementById('out-of-canvas');
		container.onmousedown = reset;
		
		// Stop drawing if mouse goes out of canvas.
		$(document).mousemove(function() {
			isDrawingNow = false;
		});
		
		context = canvas.getContext('2d');
		context.lineWidth = 20;
		context.lineJoin = 'round';
		context.lineCap = 'round';
		
		info1 = document.getElementById('info-1');
		info2 = document.getElementById('info-2');
		info3 = document.getElementById('info-3');
		
		// Prevent scrolling when touching the canvas
		document.body.addEventListener("touchstart", function (e) {
		  if (e.target == canvas) {
			e.preventDefault();
		  }
		}, false);
		document.body.addEventListener("touchend", function (e) {
		  if (e.target == canvas) {
			e.preventDefault();
		  }
		}, false);
		document.body.addEventListener("touchmove", function (e) {
		  if (e.target == canvas) {
			e.preventDefault();
		  }
		}, false);
		
	});
	
	function reset() {
		resetCanvas();
		resetDisplay();
	}
	
	function resetCanvas() {		
		context.clearRect(0, 0, canvas.width, canvas.height);
	}
	
	function canvasClicked(event) {
		var offX = event.layerX - canvas.offsetLeft;
		var offY = event.layerY - canvas.offsetTop;
		alert(offX + ',' + offY);
	}
	
	function mouseDown(event) {
		isDrawingNow = true;
		var offX = event.layerX - canvas.offsetLeft;
		var offY = event.layerY - canvas.offsetTop;
		currentPoint = [offX, offY];
		event.stopPropagation();
		//alert(offX + ',' + offY);		
	}
	
	function mouseUp(event) {
		isDrawingNow = false;
		//getBitmap();		
	}
	
	function mouseMove(event) {
		if (isDrawingNow === false) {
			return;
		}
		var offX = event.layerX - canvas.offsetLeft;
		var offY = event.layerY - canvas.offsetTop;
		if (Math.abs(offX - currentPoint[0] < 0.1)) {
			//return;
		}
		drawLine(currentPoint, [offX, offY]);	
		currentPoint = [offX, offY];
		event.stopPropagation();	
	}
	
	function mouseDoubleClick(event) {
		resetCanvas();
		event.stopPropagation();	
	}
	
	function drawLine(point1, point2) {
		//alert(point1 + ' aa ' + point2);
		context.beginPath();
		context.moveTo(point1[0], point1[1]);
		context.lineTo(point2[0], point2[1]);
		context.stroke();
		context.closePath();
	}
	
	// Get bitmap from the Canvas context and convert it to 28x28 bitmap. 
	// Return value is an array of size 28x28 representing greyscale values.
	function getBitmap() {
		let width = 300;
		let height = 300;
		let newWidth = 28;
		let newHeight = 28;
		let bitmap = context.getImageData(0, 0, width, height);
		let greyscale = convertToGreyscale(bitmap.data);		
		let result = convertBitmap(greyscale, height, width, newHeight, newWidth);
		//alert(JSON.stringify(result));
		let output = analyze(result);
		display(output);
		
		doAwsPredict(result);
	}
	
	function doAwsPredict(bitmap) {
	  $.ajax({
		 type: "GET",
		 url: "machine.php",
		 data: { input: JSON.stringify(bitmap) },
		 dataType: 'json',
		 cache: false,
		 async: false,
		 success: function(result){
			 //alert(result);
			 showAwsResult(result);
		 },
		 error: function(err) {
			 alert(JSON.stringify(err));
		 }
	  });		
	}
	
	function showAwsResult(result) {
		$('#aws_result').text('Amazon machine learning predicts: ' + result);
	}
	
	// Convert Canvas bitmap to greyscale, taking Alpha values.
	// White is 0, black is 255
	function convertToGreyscale(bitmap) {
		let greys = [];
		for (let i = 0; i < bitmap.length; i += 4) {
			let grey = bitmap[i + 3]; // Alpha value
			greys.push(grey);
		}
		return greys;
	}
	
	// For a given bitmap array (greyscale) of any size, 
	//  convert it to another size.
	function convertBitmap(bitmap, oldHeight, oldWidth, newHeight, newWidth) {
		let newBitmap = [];
		let blockHeight = Math.floor(oldHeight / newHeight);
		let blockWidth = Math.floor(oldWidth / newWidth);		
		function getBlockAvg(topLeft) {
			let sum = 0.0;
			for (let i = topLeft[0]; i < topLeft[0] + blockHeight; i++) {
				for (let j = topLeft[1]; j < topLeft[1] + blockWidth; j++) {
					sum += bitmap[oldWidth*i + j];
				}
			}	
			return Math.floor(sum / (blockHeight*blockWidth)); 
		}
		for (let i = 0; i < newHeight; i++) {
			for (let j = 0; j < newWidth; j++) {
				let topLeft = 
					[Math.floor(oldHeight * i / newHeight), 
						Math.floor(oldWidth * j / newWidth)];
				let avg = getBlockAvg(topLeft);
				newBitmap.push(avg);
			}
		}
		return newBitmap;
	}
	
	function analyze(bitmapData) {
		let output = runNetwork(neuralNetwork, bitmapData, Math.tanh);
		return output;
	}
	
	// Argument is array of ten floats, each of which denotes the probability
	//  of the handwriting being that digit. 
	function display(data) {
		data = data.map((val, index) => { return [index, Math.round(val * 100)] });
		data.sort((a, b) => { return b[1] - a[1]; });
		//alert('AI result: ' + JSON.stringify(output.slice(0, 3)));	
		
		info1.innerHTML = data[0][0] + ' (' + data[0][1] + '%)';
		info2.innerHTML = data[1][0] + ' (' + data[1][1] + '%)';
		info3.innerHTML = data[2][0] + ' (' + data[2][1] + '%)';
		//alert('아마 ' + data[0][0] + ' 인거 같아요. 아니면, ' + data[1][0] + ' 이거나 ' +
		//	data[2][0] + ' 인 것 같네요');
	}
	
	function resetDisplay() {
		info1.innerHTML = '';
		info2.innerHTML = '';
		info3.innerHTML = '';
	}
	
	function pollySubmit() {	
		
	   var input_text = $("#text").val();
	   //alert(input_text);
	   if (input_text == '') {
		  return;
	   } else {
		  $.ajax({
			 type: "GET",
			 url: "polly_ajax.php", 
			 data: { input: input_text, dummy: Date().toString() },
			 dataType: 'json',
			 cache: false,
			 async: false,
			 success: function(result){
				 //alert(result);
				 var audio = new Audio('audio/' + result);
					audio.play();

				//var audioElement = document.createElement('audio');
						//audioElement.setAttribute('src', '/audio/temp.mp3');
						//audioElement.load();
						//audioElement.play();
				// Plays the mp3 at the returned URL
				//$("#audio_player").src = "polly.mp3";
				//$("#audio_player").play();
				//audioElement.setAttribute('src', result);
			 },
			 error: function(err) {
				 alert(JSON.stringify(err));
			 }
		  });
	   }
   }
</script>
</head>

<body>
<div style="overflow: auto;" id="out-of-canvas">
	<div id="input-canvas-container">
		<canvas id="input-canvas" width="300px" height="300px"></canvas>
	</div>
</div>

<button onclick="getBitmap();">AIに数字を判別してもらう</button>

<p id="aws_result"></p>

<div style="width:300px; margin: 0 auto; text-align: center;">
<p id="info-1" style="font-size: 50px; font-weight:bold;"></p>
<p id="info-2" style="font-size: 30px;"></p>
<p id="info-3" style="font-size: 30px;"></p>
</div>


<div id="mainform">
	<h2>読んでほしい言葉を入力してください</h2>
	<div id="polly_input_form" >
		<input id="text" type="text">
	</div>
</div>

<?php
echo file_get_contents('/home/ubuntu/git/aws_web_id');

?>

<script>
	document.getElementById("text").addEventListener("keyup", function(event) {
	event.preventDefault();
	// Number 13 is the "Enter" key on the keyboard
	if (event.keyCode === 13) {
		pollySubmit();
	}
});
</script>

<audio id="player" controls>
	<source src="audio/temp.mp3" type="audio/mpeg">
</audio>

</body>
</html>