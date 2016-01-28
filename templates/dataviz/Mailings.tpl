{crmTitle string="Mailing"}

<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
<div id="campaign" class="col-md-2"><div class="graph"></div></div>
<div id="type" class="col-md-2"><div class="graph"></div></div>
<div id="status" class="col-md-2"><div class="graph"></div></div>
<div id="date" class="col-md-6"><div class="graph"></div></div>
<table class="table table-striped" id="table">

<thead><tr>
<th>Date</th>
<th>Name</th>
<th>Campaign</th>
<th>Sent</th>
<th>Open</th>
<th>Clicks</th>
</tr></thead>
</table>



<script>
var data = {crmSQL json="mailing"};
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
  d.date = dateFormat.parse(d.activity_date_time);
  d.contact_create_date = dateFormat.parse(d.contact_create_date);
});



var ndx  = crossfilter(data.values)
  , all = ndx.groupAll();

var totalCount = dc.dataCount("#datacount")
      .dimension(ndx)
      .group(all);

function drawCampaign (dom) {
  var dim = ndx.dimension(function(d){return d.campaign});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(90)
    .width(250)
    .height(200)
    .dimension(dim)
    .colors(d3.scale.category20())
    .group(group);

  return graph;
}

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
    .colors(d3.scale.category20b())
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
    .x(d3.time.scale().domain(d3.extent(dim.top(2000), function(d) { return d.activity_date_time; })))
    .round(d3.time.day.round)
    .elasticY(true)
    .xUnits(d3.time.days);

  return graph;
}


function drawTable(dom) {
  var dim = ndx.dimension (function(d) {return d.contact_id});
  var graph = dc.dataTable(dom)
    .dimension(dim)
    .size(2000)
    .group(function(d){ return ""; })
    .sortBy(function(d){ return d.activity_date_time; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		return prettyDate(d.activity_date_time);
	    },
	    function (d) {
		return "<a href='https://act.wemove.eu/campaigns/"+d.speakout_id+"' target='_blank'>"+d.campaign+"</a>";
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

 
drawTable("#table");
drawType("#type .graph");
drawDate("#date .graph");
drawStatus("#status .graph");
drawCampaign("#campaign .graph");

dc.renderAll();

</script>

<style>
.clear {clear:both;}

</style>
{/literal}
