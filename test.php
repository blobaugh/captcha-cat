<html>
<head>
	<title>PT API Captcha Test</title>
	
	<script>
		<!--//
		function ajaxRequest(theURL, sendString, callbackFunction) {
			var thisRequestObject;
			thisRequestObject = initiateRequest();
			thisRequestObject.onreadystatechange = processRequest;
			
			function initiateRequest() {
				if (window.XMLHttpRequest)
					return new XMLHttpRequest();
				elseif (window.ActiveXObject)
					return new ActiveXObject("Microsoft.XMLHTTP");
			}
			
			
			function processRequest() {
				if (thisRequestObject.readyState == 4) {
					if (thisRequestObject.status == 200) {
						if (callbackFunction)
							callbackFunction(thisRequestObject, sendString);
					} else
						alert("There was an error: (" + thisRequestObject.status + ") " + thisRequestObject.statusText);
				}
			}
			
			
			this.sendGetData = function() {
				if (theURL) {
					thisRequestObject.open("GET", theURL, true);
					thisRequestObject.send(sendString);
				}
			}
			
			
			this.sendPostData = function() {
				if (theURL) {
					thisRequestObject.open("POST", theURL, true);
					thisRequestObject.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
					thisRequestObject.send(sendString);
				}
			}
		}
		//-->
	</script>
	
	<script>
		window.onload = function() {
			//var sendData = new ajaxRequest('./index.php', 'method=display', showReceived);
			//sendData.sendPostData();
		}
		
		function showReceived(returnData) {
			document.getElementById("demo").innerHTML = (returnData.responseText);
		}
	</script>
	
</head>
<body>
	
	<form method="get" action="">
		<div id="demo">
		<script>
			var sendData = new ajaxRequest('./index.php', 'method=display', showReceived);
			(sendData.sendPostData());
		</script>
		</div>
		<input type="submit"/>
	</form>
	
</body>
</html>	