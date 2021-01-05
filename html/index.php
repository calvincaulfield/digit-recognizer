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
    let container = null;
    let context = null;
    let isDrawingNow = false;
    var currentPoint = null;

    let info1 = null;
    let info2 = null;
    let info3 = null;

    $(document).ready(function() {
      canvas = document.getElementById('input-canvas');
      canvas.onmousedown = mouseDown;
      canvas.onmouseup = mouseUp;
      canvas.onmousemove = mouseMove;
      container = document.getElementById('out-of-canvas');
      container.onmousedown = reset;

      canvas.addEventListener("touchstart", (event) => {
        event.preventDefault();
        touchStart(event);
      }, false);

      canvas.addEventListener("touchend", (event) => {
        event.preventDefault();
        touchEnd(event);
      }, false);

      canvas.addEventListener("touchmove", (event) => {
        event.preventDefault();
        touchMove(event);
      }, false);

      // Stop drawing if mouse goes out of canvas.
      $(document).mousemove(function() {
        isDrawingNow = false;
      });

      context = canvas.getContext('2d');
      context.lineWidth = 20;
      context.lineJoin = 'round';
      context.lineCap = 'round';
    });

    function touchStart(event) {
      isDrawingNow = true;

      var touch = event.touches[0];
      const x = event.changedTouches[0].pageX - canvas.getBoundingClientRect().left
      const y = event.changedTouches[0].pageY - canvas.getBoundingClientRect().top

      currentPoint = [x, y];

      event.stopPropagation();
    }

    function touchEnd(event) {
      isDrawingNow = false;
    }

    function touchMove(event) {
      if (isDrawingNow === false) {
        return;
      }

      var touch = event.touches[0];
      const x = event.changedTouches[0].pageX - canvas.getBoundingClientRect().left
      const y = event.changedTouches[0].pageY - canvas.getBoundingClientRect().top

      if (Math.abs(x - currentPoint[0] < 0.1)) {
        //return;
      }

      drawLine(currentPoint, [x, y]);
      currentPoint = [x, y];

      event.stopPropagation();
    }

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
      let resultBitmap = convertBitmap(greyscale, height, width, newHeight, newWidth);
      let output = analyze(resultBitmap);
      display(output);
      doAwsPredict(resultBitmap, output);
    }

    function doAwsPredict(bitmap, my_output) {
      var start_time = Date.now();

      $.ajax({
        type: "GET",
        url: "machine.php",
        data: {
          input: JSON.stringify(bitmap)
        },
        dataType: 'json',
        cache: false,
        async: true,
        success: function(predicted_number) {
          //alert(result);
          var aws_result = [predicted_number, (Date.now() - start_time) / 1000];
          showAwsResult(aws_result);
          annouce_result(aws_result, my_output);
        },
        error: function(err) {
          alert(JSON.stringify(err));
        }
      });
    }

    function showAwsResult(aws_result) {
      $('#amazon-result').text(aws_result[0]);
      $('#amazon-time').text(aws_result[1]);
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
            sum += bitmap[oldWidth * i + j];
          }
        }
        return Math.floor(sum / (blockHeight * blockWidth));
      }
      for (let i = 0; i < newHeight; i++) {
        for (let j = 0; j < newWidth; j++) {
          let topLeft = [Math.floor(oldHeight * i / newHeight),
            Math.floor(oldWidth * j / newWidth)
          ];
          let avg = getBlockAvg(topLeft);
          newBitmap.push(avg);
        }
      }
      return newBitmap;
    }

    function analyze(bitmapData) {
      var start_time = Date.now();
      let output = runNetwork(neuralNetwork, bitmapData, Math.tanh);
      var duration = Date.now() - start_time;
      //alert(start_time);
      return [process_data(output), duration / 1000];
    }

    function display(data_and_duration) {
      //alert('AI result: ' + JSON.stringify(output.slice(0, 3)));

      var data = data_and_duration[0];
      $('#lib-calvin-01').text(data[0][0] + ' (' + data[0][1] + '%)');
      $('#lib-calvin-02').text(data[1][0] + ' (' + data[1][1] + '%)');
      $('#lib-calvin-03').text(data[2][0] + ' (' + data[2][1] + '%)');

      $('#lib-calvin-time').text(data_and_duration[1]);
    }

    // Argument is array of ten floats, each of which denotes the probability
    //  of the handwriting being that digit.
    function process_data(data) {
      data = data.map((val, index) => {
        return [index, Math.round(val * 100)]
      });
      data.sort((a, b) => {
        return b[1] - a[1];
      });
      return data;
    }

    function resetDisplay() {
      $('#amazon-result').text('?');

      $('#amazon-time').text('?');
      $('#lib-calvin-time').text('?');

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
          data: {
            input: input_text,
            dummy: Date().toString()
          },
          dataType: 'json',
          cache: false,
          async: false,
          success: function(result) {
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
      var text = "初めまして。わたくしはアマゾンの音声変換システムの水木ともうします。今度は南くんのAPI呼び出しに応じてまいりました。" +
        "今日は" + date.getFullYear() + "年" + (date.getMonth() + 1) + "月" + date.getDate() + "日" + "でございます。" +
        "では、下のボックスにマウスで０から9までの数字を一つ書いてみてください。書き終わったらその下のボタンを押してみましょう。";
      pollySubmit(text);
    }

    function annouce_result(amazon_result, my_result) {
      var amazon_number = amazon_result[0];
      var amazon_time = amazon_result[1];
      var my_numbers = my_result[0];
      var my_time = my_result[1];
      var is_same_answer = amazon_number == my_numbers[0][0];
      var text = "アマゾンのAIは" + amazon_time + "秒をかけて" + amazon_number + "だと判断しました。" +
        "南くんのAIは" + my_time + "秒をかけて" + (is_same_answer ? "同じく" : "") + my_numbers[0][0] + "だと判断しました。";
      pollySubmit(text);
    }
  </script>
</head>

<body>

  <h1>calvincaulfieldがC++で実装した深層学習</h1>
  <p>四角の中に０から９までの数字（一桁）を書いて、その下のボタンをおしてみましょう</p>
  <p>もういっかい試すためには、四角の外をクリックしてください</p>
  <!-- <p>音声で案内するのでスピーカーやイヤフォンを用意してください</p>
  <div>
    <button style="margin:0 auto" onclick="introduce();">1.説明を聞く</button>
  </div> -->

  <div style="overflow: auto;" id="out-of-canvas">
    <div id="input-canvas-container">
      <canvas id="input-canvas" width="300px" height="300px"></canvas>
      <!-- <canvas id="input-canvas""></canvas> -->
    </div>
  </div>

  <div>
    <button style="margin:0 auto" onclick="getBitmap();">AIに数字を判別してもらう</button>
  </div>

  <div style="margin-top:50px">
    <table style="width:300px; margin: 0 auto; text-align: center;">
      <tr>
        <th></th>
        <th>Amazon</th>
        <th>lib_calvin</th>
      </tr>
      <tr>
        <th>時間</th>
        <td id="amazon-time">?</td>
        <td id="lib-calvin-time">?</td>
      </tr>
      <tr>
        <th>1順位</th>
        <td id="amazon-result">?</td>
        <td id="lib-calvin-01">?</td>
      </tr>
      <tr>
        <th>2順位</th>
        <td></td>
        <td id="lib-calvin-02">?</td>
      </tr>
      <tr>
        <th>3順位</th>
        <td></td>
        <td id="lib-calvin-03">?</td>
      </tr>
    </table>
  </div>

  <pre id="response"></pre>

  <!--
<div>
	<h2>読んでほしい言葉を入力してください</h2>
	<div id="polly_input_form" >
		<input id="text" type="text">
	</div>
</div>

<script>
	document.getElementById("text").addEventListener("keyup", function(event) {
	event.preventDefault();
	// Number 13 is the "Enter" key on the keyboard
	if (event.keyCode === 13) {
		pollySubmit($("#text").val());
	}
});
</script>
-->


</body>

</html>