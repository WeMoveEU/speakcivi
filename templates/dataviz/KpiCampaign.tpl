{crmTitle string="Campaign activities"}

<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
<div id="campaign" class="col-md-2"><div class="graph"></div></div>
<div id="type" class="col-md-2"><div class="graph"></div></div>
<div id="status" class="col-md-2"><div class="graph"></div></div>
<div id="date" class="col-md-6"><div class="graph"></div></div>
</div>
<div class="row">

<div ><span class="glyphicon glyphicon-download-alt"></span><span id='csv'>CSV</span></div>

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


function drawTable(dom) {
  var dim = ndx.dimension (function(d) {return d.campaign_id});
  toCsv('#csv',dim.top(Infinity) ); 
  var graph = dc.dataTable(dom)
    .dimension(dim)
    .size(2000)
    .group(function(d){ return ""; })
    .sortBy(function(d){ return d.campaign_id; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		return "<a href='https://act.wemove.eu/campaigns/"+d.speakout_id+"' target='_blank'>"+d.speakout_title+"</a>";
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


drawTable("#table");

dc.renderAll();

</script>

<style>
.clear {clear:both;}

</style>
{/literal}
