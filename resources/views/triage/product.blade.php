<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
<link rel="stylesheet" href="{{ asset('tabulator/css/tabulator.min.css') }}" />
<body>
<h3>{{$product->name}} - Triage</h3>
<div id="table"></div>
<div id="tablecve"></div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script src="{{ asset('tabulator/js/tabulator.min.js') }}" ></script>
<script 
<script>
product = @json($product);

function CreateTable(url)
{
	console.log(url);
	var table = new Tabulator("#table", {
		pagination:"local",
		paginationSize:10,
		autoColumns:true,
		selectable:1,
		ajaxURL:url,
		ajaxResponse:function(url, params, response)
		{
			console.log(response);
			return response; //return the tableData property of a response json object
		},
		rowClick:function(e, row)
		{
			//e - the click event object
			//row - row component
			PopulateModal(row.getData());
			$('#modal').show();
		},
	});
}
function CreateCveTable(url)
{
	console.log(url);
	var table = new Tabulator("#table", {
		pagination:"local",
		paginationSize:10,
		autoColumns:true,
		selectable:1,
		ajaxURL:url,
		ajaxResponse:function(url, params, response)
		{
			console.log(response);
			return response; //return the tableData property of a response json object
		},
		rowClick:function(e, row)
		{
			//e - the click event object
			//row - row component
			PopulateModal(row.getData());
			$('#modal').show();
		},
	});
}
$(document).ready(function()
{
	console.log("Product Triage Page Loaded");
	CreateTable("{{route('triage.product.data',[$product->name])}}");
	
});
</script>
</body>
</head>
</html>
