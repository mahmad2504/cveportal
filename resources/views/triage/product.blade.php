<!DOCTYPE html>
<html class="no-js" lang="en">
<head>
 <!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('tabulator/css/tabulator.min.css') }}" />

<body>
<h3>{{$product->name}} - Triage</h3>
<select  id="select_version" style="margin-left:10px;float:none;"></select>

<div id="table"></div>
<div id="tablecve"></div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script src="{{ asset('tabulator/js/tabulator.min.js') }}" ></script>
<script 
<script>
product = @json($product);
UpdateVersionSelect();

function UpdateVersionSelect()
{
	$('#select_version').children().remove();
	addOption('select_version','select version',-1,0);
	for(i=0;i<product.version.length;i++)
	{
		addOption('select_version',product.version[i].version,product.version[i].id,0);
	}
}

function addOption(id,optionText,optionValue,selected) 
{ 
	if(!selected)
		$('#'+id).append(`<option value="${optionValue}"> ${optionText} </option>`); 
	else							
		$('#'+id).append(`<option value="${optionValue}" selected> ${optionText} </option>`);							
}
productid = -1;
$('#select_version').on('change', function() 
{
	productid = this.value;
	if(productid == -1)
		return;
	CreateTable('/triage/data/'+productid);
	//console.log(productid);
});

			
function UpdateStatus(cell)
{
	data = cell.getRow().getData();
	d = {};
	d.status = data.status;
	d._token = "{{ csrf_token() }}";
	$.ajax({
		type:"PUT",
		url:'{{route("cve.status.update")}}',
		cache: false,
		data:d,
		success: function(response){
			cell.getRow().getElement().style.backgroundColor = "#8FBC8F";
			function colorrevert()
			{
				element = cell.getRow().getElement();
				if($(element).hasClass('tabulator-row-even'))
					element.style.backgroundColor = "#EFEFEF";
				else
					element.style.backgroundColor = "#ffffff";
			};
			setTimeout(colorrevert, 2000);
		},
		error: function(response){
			cell.restoreOldValue();
			cell.getRow().getElement().style.backgroundColor = "#FFD700";
			function colorrevert()
			{
				element = cell.getRow().getElement();
				if($(element).hasClass('tabulator-row-even'))
					element.style.backgroundColor = "#EFEFEF";
				else
					element.style.backgroundColor = "#ffffff";
			};
			setTimeout(colorrevert, 2000);
		}
	});
}

function CreateTable(url)
{
	columns = [
        {title:"CVE", field:"cve", sorter:"string", width:130},
		{title:"Description", field:"nvd.description", sorter:"string", width:700},
		{title:"State", field:"status.state", editor:"select", editorParams:{
			"Investigate":"Investigate",
			"Vulnerable":"Vulnerable",
			"Won't Fix":"Won't Fix",
			"Fixed":"Fixed",
		},
			cellEdited:function(cell)
			{
				UpdateStatus(cell);
			},
		},
		{title:"Example", field:"status.publish", editor:"tick",
			cellEdited:function(cell)
			{
				UpdateStatus(cell);
			},
		},
		{title:"CVE", field:"fixedin", sorter:"numeric", width:50},
		];
	console.log(url);
	var table = new Tabulator("#table", {
		//pagination:"local",
		//paginationSize:10,
		//autoColumns:true,
		columns:columns,
		selectable:1,
		ajaxURL:url,
		ajaxResponse:function(url, params, response)
		{
			console.log(response);
			for(i=0;i<response.length;i++)
			{
				cve =  response[i];
				for(j=0;j<cve.product.length;j++)
				{
					product = cve.product[j];
					if((product.state == 'Fixed')&&(product.this==0))
					{
						cve.fixedin = 1;
					}
				}
			}
			return response; //return the tableData property of a response json object
		},
		rowClick:function(e, row)
		{
	
		},
		
	});
}

$(document).ready(function()
{
	console.log("Product Triage Page Loaded");
	//CreateTable("{{route('triage.product.data',[$product->name])}}");
	
});
</script>
</body>
</head>
</html>
