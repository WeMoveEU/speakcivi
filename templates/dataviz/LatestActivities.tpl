{crmTitle title="Here be dragons"}

<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
<div id="type" class="col-md-3"><div class="graph"></div></div>
<div id="status" class="col-md-3"><div class="graph"></div></div>
<div id="date" class="col-md-6"><div class="graph"></div></div>
<table class="table table-striped" id="table">

<thead><tr>
<th>date</th>
<th>Campaign</th>
<th>Name</th>
<th>Activity</th>
<th>Status</th>
<th>Created</th>
</tr></thead>
</table>



<script>
var data = {crmSQL file="latestactivities"};
var activityType = lookupTable({crmAPI entity='OptionValue' option_group_id="activity_type" return="value,label" option_limit=200}.values,"value","label");
var activityStatus = lookupTable({crmAPI entity='OptionValue' option_group_id="activity_status" return="value,label"}.values,"value","label");
var dateFormat = d3.time.format("%Y-%m-%d %H:%M:%S");
            var currentDate = new Date();


{literal}

var prettyDate = function (dateString){
  var date = new Date(dateString);
  var d = date.getDate();
  var m = ('0' + (date.getMonth()+1)).slice(-2);
  var y = date.getFullYear();
  var min = ('0' + date.getMinutes()).slice(-2);
  return d+'/'+m+'/'+y +' ' +date.getHours() + ':'+min;
}


function lookupTable(data,key,value) {
  var t= {}
  data.forEach(function(d){t[d[key]]=d[value]});
  return t;
}

data.values.forEach(function(d){
  d.activity_date_time = dateFormat.parse(d.activity_date_time);
  d.contact_create_date = dateFormat.parse(d.contact_create_date);
});



var ndx  = crossfilter(data.values)
  , all = ndx.groupAll();

var totalCount = dc.dataCount("#datacount")
      .dimension(ndx)
      .group(all);

function drawStatus (dom) {
  var dim = ndx.dimension(function(d){return activityStatus[d.status_id]});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(90)
    .width(250)
    .height(200)
    .dimension(dim)
    .colors(d3.scale.category10())
    .group(group);

  return graph;
}

function drawType (dom) {
  var dim = ndx.dimension(function(d){return activityType[d.activity_type_id]});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(90)
    .width(250)
    .height(200)
    .dimension(dim)
    .colors(d3.scale.category10())
    .group(group);

  return graph;
}

function drawDate (dom) {
  var dim = ndx.dimension(function(d){return d3.time.day(d.activity_date_time)});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph=dc.lineChart(dom)
   .margins({top: 10, right: 50, bottom: 20, left:40})
    .height(200)
    .dimension(dim)
    .group(group)
    .brushOn(true)
    .x(d3.time.scale().domain(d3.extent(dim.top(1000), function(d) { return d.activity_date_time; })))
    .round(d3.time.day.round)
    .elasticY(true)
    .xUnits(d3.time.days);

  return graph;
}


function drawTable(dom) {
  var dim = ndx.dimension (function(d) {return d.contact_id});
  var graph = dc.dataTable(dom)
    .dimension(dim)
    .size(1000)
    .group(function(d){ return ""; })
    .sortBy(function(d){ return d.activity_date_time; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		return prettyDate(d.activity_date_time);
	    },
	    function (d) {
		return d.campaign_id;
	    },
	    function (d) {
		return "<a href='"+CRM.url("civicrm/contact/view",{cid:d.contact_id})+"'>"+d.display_name+"</a>";
	    },
	    function (d) {
		return activityType[d.activity_type_id];
	    },
	    function (d) {
		return activityStatus[d.status_id];
	    },
	    function (d) {
              if (d.activity_date_time.getTime() == d.contact_create_date.getTime()) {
                return "<span title='new member' class='glyphicon glyphicon-certificate'></span>";
              }
	      return prettyDate(d.contact_create_date);

	    }
	]
    );

  return graph;
}

function bla() {
	var gender = ndx.dimension(function(d){if(d.gender!="") return d.gender; else return 3;});
		var genderGroup = gender.group().reduceSum(function(d){return d.qty;});
	var genderPie   = dc.pieChart('#gender')
	 .innerRadius(10).radius(90)
	 .width(250)
	  .height(200)
	  .dimension(gender)
	  .colors(d3.scale.category10())
	  .group(genderGroup);
	/*
	  .label(function(d) {
	    if (genderPie.hasFilter() && !genderPie.hasFilter(d.key))
		      return d.key + "(0%)";
	    return d.key+"(" + Math.floor(d.value / all.reduceSum(function(d) {return d.qty;}).value() * 100) + "%)";;
	  });
	*/

	var type = ndx.dimension(function(d) {return d.type;});
	var typeGroup= type.group().reduceSum(function(d){return d.qty;});
	var typeRow = dc.rowChart('#type')
		 .height(200)
		  .margins({top: 20, left: 10, right: 10, bottom: 20})
		  .dimension(type)
		  .cap(5)
		  .ordering (function(d) {return d.qty;})
		  .colors(d3.scale.category10())
		  .group(typeGroup)
		  .elasticX(true);
//var typePie   = dc.pieChart("#type").innerRadius(10).radius(90);
}

 
drawTable("#table");
drawType("#type .graph");
drawDate("#date .graph");
drawStatus("#status .graph");

dc.renderAll();

</script>

<style>
.clear {clear:both;}

</style>
{/literal}
