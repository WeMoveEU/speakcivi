{crmTitle string="Campaign activities"}

<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
<div id="language" class="col-md-2"><div class="graph"></div></div>
<div id="ratio_added" class="col-md-2"><div class="graph"></div></div>
<div id="actually_added" class="col-md-2"><div class="graph"></div></div>
<div id="ratio_new" class="col-md-2"><div class="graph"></div></div>
<div id="opt_out" class="col-md-2"><div class="graph"></div></div>
<div id="pending" class="col-md-2"><div class="graph"></div></div>
</div>
<div class="row">

<button class'btn btn-primary btn-lg'><span class="glyphicon glyphicon-download-alt"></span><span id='csv'>CSV</span></button>

<table class="table table-striped" id="table">
<thead><tr>
<th>campaign</th>
<th>language</th>
<th>Signatures</th>
<th>From new people</th>
<th>came in</th>
<th>ratio added</th>
<th>actually added</th>
<th>ratio new</th>
<th>opt_out</th>
<th>pending</th>
<th>share</th>
</tr></thead>
</table>



<script>
var data = {crmSQL file="kpicampaign" debug=1};

{literal}


var ndx  = crossfilter(data.values)
  , all = ndx.groupAll();

function toCsv (dom,array) {

  var str = '';

  var line = '';
  for (var index in array[0]) {
    if (line != '') line += ','
    line += index;
  }
  var str = line;

  for (var i = 0; i < array.length; i++) {
    var line = '';
    for (var index in array[i]) {
      if (line != '') line += ','
      line += array[i][index];
    }
    str += line + '\r\n';
  }
   

  var data = "text/csv;charset=utf-8," + encodeURIComponent(str);
  jQuery (dom).html('<a href="data:' + data + '" download="data.csv">Download</a>');
} 

var totalCount = dc.dataCount("#datacount")
      .dimension(ndx)
      .group(all);

function drawPercent (dom,attr,name) {
  //var dim = ndx.dimension(function(d){return 10 * Math.floor((accessor(d)/d.recipients* 10)) });
  //var dim = ndx.dimension(function(d){return 1 * Math.floor((attr(d) * 10)) });
  var dim = ndx.dimension(function(d){return Math.floor(attr(d) * 100) });
  var group = dim.group().reduceSum(function(d){return 1;});
  //var group = dim.group().reduceSum(function(d){return d.recipients;});


  var graph = dc.barChart(dom+ " .graph")
    .height(100)
    .width(150)
    .gap(0)
    .margins({top: 10, right: 0, bottom: 20, left: 20})
    .colorCalculator(function(d, i) {
        return "#f85631";
        })
     .x(d3.scale.linear().domain([0, 100*attr(dim.top(1)[0])]))
//    .x(d3.scale.ordinal())
//    .xUnits(dc.units.ordinal)
    .brushOn(true)
    .elasticY(true)
    .yAxisLabel(name)
    .dimension(dim)
    .group(group)
/*    .renderlet(function(chart) {
	    var d = chart.dimension().top(Number.POSITIVE_INFINITY);
	    var total = nb = recipients = 0;
	    d.forEach(function(a) {
		++nb;
                recipients += a.recipients;
                if (a.recipients)
	  	  total += attr(a);
	    });
	    if (nb) {
		//var avg = 100 * total / nb;
		var avg = 100 * total / recipients;
		jQuery(dom + " .avg").text(Math.round(avg) + "%");
	    } else {
		jQuery(dom +" .avg").text("");
	    }
      }
    );
*/
   graph.yAxis().ticks(3);
   graph.xAxis().ticks(4);

return graph;
}

function drawLanguage (dom) {
  var dim = ndx.dimension(function(d){return d.language.substring(3,5)});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(50)
    .width(100)
    .height(100)
    .dimension(dim)
    .colors(d3.scale.category10())
    .group(group);

  return graph;
}

function drawTable(dom) {
  var dim = ndx.dimension (function(d) {return d.campaign_id});
  toCsv('#csv',dim.top(Infinity) ); 
  var graph = dc.dataTable(dom)
    .dimension(dim)
    .size(2000)
    .group(function(d){ return parent_id; })
    .sortBy(function(d){ return d.campaign_id; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		return "<a class='btn btn-default btn-xs' href='/civicrm/campaign/add?reset=1&action=update&id="+d.id
                   +"'><span class='glyphicon glyphicon-pencil'></span></a>"
                   +"<a href='https://act.wemove.eu/campaigns/"+d.speakout_id+"' target='_blank'>"+d.speakout_title+"</a>";
	    },
	    function (d) {
		return d.language;
	    },
	    function (d) {  return d.total_signatures;},
	    function (d) {
		return d.new_people_signees;
	    },
	    function (d) {
		return d.people_that_actually_came_in;
	    },
	    function (d) {
                return d.ratio_actually_added;
            },
	    function (d) {  return d.ratio_added;},
	    function (d) {  return d.ratio_new;},
	    function (d) {  return d.ratio_opt_out;},
	    function (d) {  return d.ratio_pending;},
	    function (d) {  return d.ratio_share;},
	]
    );

  return graph;
}

drawPercent("#ratio_added", function(d){return d.ratio_added});
drawPercent("#actually_added", function(d){return d.ratio_actually_added});
drawPercent("#opt_out", function(d){return d.ratio_opt_out});
drawPercent("#pending", function(d){return d.ratio_pending});
drawLanguage("#language");
drawTable("#table");

dc.renderAll();

</script>

<style>
.clear {clear:both;}

</style>
{/literal}
