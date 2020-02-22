<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Mentor Login</title>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta HTTP-EQUIV="cache-control" CONTENT="no-cache, no-store, must-revalidate">
<meta HTTP-EQUIV="Expires" CONTENT="Mon, 01 Jan 1970 23:59:59 GMT">
<meta name="viewport" content="width=device-width,initial-scale=1.0" />
<link href="{{ asset('css/loading.css') }}" rel="stylesheet">

<style>
	* {
		box-sizing: border-box;
	}
	
	body {
    font-size: 0.875rem;
		font-family: Roboto, "Segoe UI", "Helvetica Neue", Verdana, sans-serif;
    padding-top: 2.5rem;
    padding-bottom: 2.5rem;
    background-color: #f4f4f4;
	}
	
	.container {
		max-width: 350px;
		padding: 1.5rem 1.875rem;
		background-color: #fff;
		border-radius:2px;
		margin: 3rem auto;
		border: 1px solid #ddd;
		box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.05);
	}
  
  .login-title {
    margin: -1.5rem -1.875rem 2rem;
    background-color: #0772c3;
    padding: 1rem 1.5rem;
  }
  
  h2 {
    font-family: "Roboto","Segoe UI","Helvetica Neue",Roboto,sans-serif;
    font-weight: 200;
    font-size: 1.75rem;
    color: #fff;
    margin: 0;
  }
	
	img.ie10andup {
		display:block;
		width: 9rem;
		margin: auto auto 2rem;
	}
	
	img.ielt9 {
		display:none;
	}
	
	input {
		margin-bottom: 1.5rem;
    padding: 7px 9px;
		display:block;
		width: 100%;
	}
	
	input[type="text"],
	input[type="password"] {
		border: 1px solid #ddd;
		box-shadow: 0 1px 2px rgba(10, 10, 10, 0.1) inset;
		background-color: #FFF;
		transition: border 0.2s linear 0s, box-shadow 0.2s linear 0s;
		border-radius: 2px;
	}
	
	input[type="text"]:focus,
	input[type="password"]:focus {
		border-color: #cacaca; 
		outline: 0; 
		box-shadow: 0 0 5px #cacaca;
	}
	
	label {
		margin-bottom: 0.33rem;
		display:block;
    color: #8a8a8a;
	}
	
	.checkbox {
		display:block;
		padding: .5rem 0;
	}
	
	.checkbox label {
		display: inline-block;		 
	}
	
	input[type="checkbox"] {
		width: auto;
		display: inline-block;
	}
	
	button {
		cursor:pointer;
    margin-bottom: 1rem;
    padding: 0.85em 1em;;
		border-radius: 2px;
    font-weight: bold;
		color: #FFF; 
		background-color:#0772c3;
		transition:  background 0.5s;
    border: 1px solid transparent;
	}
	
	button:hover,
	button:active {
		background-color:#005692;
	}

	#login-error-msg {
		font-size: 10px;
		color: red;
    display:none;
  }
  p {
    font-size: .8rem;
    color: #bbb;
  }
	
</style>

<!--[ie lt IE 9]>
<style>
	img.ie10andup {
		display:none;
	}
	img.ielt9 {
		display:block;
		width: 206px;
		margin: 0 auto;
	}
</style>
<![endif]-->

</head>

<body>


<img class="ie10andup" src="{{ asset('images/mgc-asb-logo.svg') }}" alt="Mentor Graphics, A Siemens Business logo" title="Log in using your Mentor network credentials">
<img class="ielt9" src="{{ asset('images/mgc-asb-logo.png')}}" alt="Mentor Graphics, A Siemens Business logo" title="Log in using your Mentor network credentials">
<div class="container">
<div class="login-title">
  <h2>Login</h2>
</div>
<div style="display:none;" class="loading">Loading&#8230;</div>
<form  action="#" id="loginform" method="#">
	<label for="username">User name</label>
	<input type="text" name="USER" id="username" placeholder="User name" autocomplete="on" autofocus >
	
	<label for="password">Password</label>
	<input type="password" name="PASSWORD" id="password" placeholder="Password" autocomplete="on">
	

	<button id="submit" type="submit">Login</button>

	<input type="hidden" name="SMENC" value="ISO-8859-1">
	

<p>Intended for Mentor workforce only</p>
</form>

</div><!-- end container div -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script>

function ShowLoading()
{
	$('.loading').show();	
}
function HideLoading()
{
	$('.loading').hide();	
}

$(document).ready(function()
{
	console.log("Login Page Loaded");
	$("#loginform").submit(
		function (e)
		{
			e.preventDefault();
			formdata = {};
			formdata.data = {};
			
			//formdata.data = $('#loginform').serializeArray();
			$("#loginform").serializeArray().map(function(x)
			{
				if(x.value == '')
				{
					alert(x.name+" is empty");
					formdata.data = null;
				}
				formdata.data[x.name] = x.value;
			}); 
			
			if(formdata.data == null)
				return;
			ShowLoading();
			formdata._token = "{{ csrf_token() }}";
			console.log(formdata);
			$.ajax({
				type:"POST",
				url:'{{route("authenticate")}}',
				cache: false,
				data:formdata,
				success: function(response)
				{
					window.location.href = "{{route('triage')}}";
				},
				error: function(response)
				{
					HideLoading();
					alert("Invalid user or password");
				}
			});			
		}
	);
});
</script>
</body>
</html>
