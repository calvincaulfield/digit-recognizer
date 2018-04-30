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
		
		// Below methods are for mobile, but doesn't work
		/*
		canvas.touchstart = function(event) {
			event.preventDefault();
			var touch = event.touches[0];
			mouseDown(touch.clientX, touch.clientY);
		}
		
		canvas.touchend = function(event) {
			event.preventDefault();
			mouseUp(event);
		};
		
		canvas.touchmove = function(event) {
			event.preventDefault();
			var touch = event.touches[0];
			mouseMove(touch.clientX, touch.clientY);
		};*/
		
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
		$('#amazon-result').text(result);
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
		
		$('#lib-calvin-01').text(data[0][0] + ' (' + data[0][1] + '%)');
		$('#lib-calvin-02').text(data[1][0] + ' (' + data[1][1] + '%)');
		$('#lib-calvin-03').text(data[2][0] + ' (' + data[2][1] + '%)');
	}
	
	function resetDisplay() {
		$('#amazon-result').text('?');

		$('#lib-calvin-01').text('?');
		$('#lib-calvin-02').text('?');
		$('#lib-calvin-03').text('?');
	}
	
	function pollySubmit(input_text) {		
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
				var audio = new Audio('audio/' + result);
				audio.play();
			 },
			 error: function(err) {
				 alert(JSON.stringify(err));
			 }
		  });
	   }
   }

   function introduce() {
	   var date = new Date();
	   var city = 0;
	   $.ajax({
			type: "GET",
			url: "https://api.ipdata.co", 
			dataType: 'jsonp',
			cache: false,
			async: false,
			success: function(response) {
				city = JSON.stringify(response['city'], null, 4);
				var text = "初めまして。わたくし、アマゾンンの音声変換システムの水木ともうします。今度は南くんのAPI呼び出しに応じてまいりました。" + 
						"今日は" + date.getFullYear() + "年" + date.getMonth() + "月" + date.getDate() + "日" + "でございます。" +
						"現在貴方は" + city + "にいらっしゃいますね。" +
						"では、下のボックスにマウスで０から9までの数字を一つ書いてみてください。書き終わったらその下のボタンを押してみましょう。";
		pollySubmit(text);
			},
			error: function(err) {
				alert(JSON.stringify(err));
			}
	   });
   }
</script>
</head>

<body>

<h1>人工知能体験サイトにようこそ！</h1>
<div>
<button style="margin:0 auto" onclick="introduce();">1.説明を聞く</button>
</div>

<div style="overflow: auto;" id="out-of-canvas">
	<div id="input-canvas-container">
		<canvas id="input-canvas" width="300px" height="300px"></canvas>
	</div>
</div>

<div>
<button style="margin:0 auto" onclick="getBitmap();">2.AIに数字を判別してもらう</button>
</div>

<div style="margin-top:50px">
	<table style="width:300px; margin: 0 auto; text-align: center;">
		<tr><th>Amazon</th><th>lib_calvin</th></tr>
		<tr><td id="amazon-result">?</td><td id="lib-calvin-01">?</td></tr>
		<tr><td></td><td id="lib-calvin-02">?</td></tr>
		<tr><td></td><td id="lib-calvin-03">?</td></tr>
	</table>
</div>


<div>
	<h2>読んでほしい言葉を入力してください</h2>
	<div id="polly_input_form" >
		<input id="text" type="text">
	</div>
</div>

<pre id="response"></pre>

<script>
	document.getElementById("text").addEventListener("keyup", function(event) {
	event.preventDefault();
	// Number 13 is the "Enter" key on the keyboard
	if (event.keyCode === 13) {
		pollySubmit($("#text").val());
	}
});


</script>

</body>
</html>