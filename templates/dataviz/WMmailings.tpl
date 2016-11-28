{crmTitle string="<span class='data_count'><span class='filter-count'></span> Mailings out of <span class='total-count'></span></span>"}
{literal}
<style>
#campaign .dc-chart g.row text {fill:grey;}
#lang .pie-slice {fill:white;}
.filter {
  display: inline-block;
  margin-right: 2em;
}
</style>
{/literal}

<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
  <div class="filter">Show:</div>
  <div class="filter"><input type="checkbox" name="small" id="filter_small" /> Small mailings</div>
  <div class="filter"><input type="checkbox" name="petitions" id="filter_petitions" checked /> Petitions</div>
  <div class="filter"><input type="checkbox" name="fundraisers" id="filter_fundraisers" checked /> Fundraisers</div>
  <div class="filter">Elapsed time:
    <input type="radio" name="timebox" value="120" /> 2h
    <input type="radio" name="timebox" value="300" /> 5h
    <input type="radio" name="timebox" value="720" /> 12h
    <input type="radio" name="timebox" value="1440" /> 1d
    <input type="radio" name="timebox" value="2880" /> 2d
    <input type="radio" name="timebox" value="144000" checked /> 100d
  </div>
</div>
<hr>

<div class="row">
<div id="campaign" class="col-md-2"><h3>Campaign</h3><div class="graph"></div></div>
<div id="lang" class="col-md-2"><h3>Language</h3><div class="graph"></div></div>
<div id="open" class="col-md-2"><h3>% Open</h3><div class="graph"></div><div class="avg"></div></div>
<div id="click" class="col-md-2"><h3>% Click</h3><div class="graph"></div><div class="avg"></div></div>
<div id="date" class="col-md-4"><h3>Date sent</h3><div class="graph"></div></div>
</div>

<div class="row">
<table class="table table-striped" id="table">

<thead><tr>
<th>Date</th>
<th>Name</th>
<th>Campaign</th>
<th>Recipients</th>
<th>Elapsed</th>
<th>Open</th>
<th>Clicks</th>
<th>Signs</th>
<th>Shares</th>
<th>Viral Signs</th>
<th>Viral Shares</th>
<th>New members</th>
<th># Donations</th>
<th>Total amount</th>
</tr></thead>
</table>
</div>

<div class="row">
</div>

<script>
var data = {crmSQL json="WMmailings"};
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
function percent(d, attr, precision) {
  return "<span title='"+d[attr]+" contacts' >"+ (100*d[attr]/d.recipients).toFixed(precision) +"%</span>";
}

function lookupTable(data,key,value) {
  var t= {}
  data.forEach(function(d){t[d[key]]=d[value]});
  return t;
}

data.values.forEach(function(d){
  d.date = dateFormat.parse(d.date);
});


function filterSmall(d) {
  return d > 1500;
}
function filterPetitions(d) {
  return !d;
}
function filterFundraisers(d) {
  return !d;
}
function filterTimebox(box) {
  return function (d) {
    return d == box;
  };
}

function reduceAdd(p, v) {
  ++p.count;
  p.sign += +v.sign;
  p.recipients+= +v.recipients;
  p.sign_new += +v.sign_new;
  return p;
}

function reduceRemove(p, v) {
  --p.count;
  p.sign -= +v.sign;
  p.recipients-= +v.recipients;
  p.sign_new -= +v.sign_new;
  return p;
}

function reduceInitial() {
  return {count: 0, sign: 0,sign_new:0,recipients:0};
}

var ndx  = crossfilter(data.values)
  , all = ndx.groupAll();
var sizeDim = ndx.dimension(function(d) { return d.recipients; });
var signDim = ndx.dimension(function(d) { return d.sign; });
var giveDim = ndx.dimension(function(d) { return d.nb_donations; });
var timeDim = ndx.dimension(function(d) { return d.timebox; });

sizeDim.filter(filterSmall);
timeDim.filterExact(144000);
jQuery(function($) {
  $('#filter_small').on('change', function() {
    if (this.checked) {
      sizeDim.filterAll();
    } else {
      sizeDim.filter(filterSmall);
    }
    dc.redrawAll();
  });

  $('#filter_petitions').on('change', function() {
    if (this.checked) {
      signDim.filterAll();
    } else {
      signDim.filter(filterPetitions);
    }
    dc.redrawAll();
  });

  $('#filter_fundraisers').on('change', function() {
    if (this.checked) {
      giveDim.filterAll();
    } else {
      giveDim.filter(filterFundraisers);
    }
    dc.redrawAll();
  });
  $('input[name=timebox]').on('click', function() {
    timeDim.filterExact(parseInt(this.value));
    dc.redrawAll();
  });
});

var totalCount = dc.dataCount("h1 .data_count")
      .dimension(ndx)
      .group(all);

function drawCampaign (dom) {
  var dim = ndx.dimension(function(d){return d.campaign});
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(50)
    .width(100)
    .height(100)
    .dimension(dim)
    .colors(d3.scale.category20())
    .group(group);

  return graph;
}

function drawLang (dom) {
  //var dim = ndx.dimension(function(d){return d.lang.substring(3)||"?"});
  var dim = ndx.dimension(function(d){return d.lang});
//  var group = dim.group().reduceSum(function(d){return 1;});
  var group = dim.group().reduce(reduceAdd,reduceRemove,reduceInitial);
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(50)
    .width(100)
    .height(100)
    .label (function (d) {return d.key.substring(3)||"?"})
    .valueAccessor( function(d) { return d.value.count })
    .title (function (d) {return d.key + ":\nmailings:" + d.value.count + "\nrecipients:" + d.value.recipients + "\nsignatures:" + d.value.sign + "\nnew:"+d.value.sign_new;})
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
  var dim = ndx.dimension(function(d){return d3.time.day(d.date)});
  //var group = dim.group().reduceSum(function(d){return 1;});
  var group = dim.group().reduceSum(function(d){return d.recipients;});
  var graph=dc.lineChart(dom)
   .margins({top: 10, right: 10, bottom: 20, left:50})
    .height(100)
    .dimension(dim)
    .renderArea(true)
    .group(group)
    .brushOn(true)
    .x(d3.time.scale().domain(d3.extent(dim.top(2000), function(d) { return d.date; })))
    .round(d3.time.day.round)
    .elasticY(true)
    .xUnits(d3.time.days);

   graph.yAxis().ticks(3);
   graph.xAxis().ticks(5);
  return graph;
}


function drawPercent (dom,attr,name) {
  //var dim = ndx.dimension(function(d){return 10 * Math.floor((accessor(d)/d.recipients* 10)) });
  var dim = ndx.dimension(function(d){return 10 * Math.floor((attr(d)/d.recipients* 10)) });
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
    .x(d3.scale.ordinal())
    .xUnits(dc.units.ordinal)
    .brushOn(false)
    .elasticY(true)
    .yAxisLabel(name)
    .dimension(dim)
    .group(group)
    .renderlet(function(chart) {
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


   graph.yAxis().ticks(3);
   graph.xAxis().ticks(4);
   return graph;
}


function drawTable(dom) {
  var dim = ndx.dimension (function(d) {return d.id});
  var graph = dc.dataTable(dom)
    .dimension(dim)
    .size(2000)
    .group(function(d){ return ""; })
    .sortBy(function(d){ return d.date; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		return prettyDate(d.date);
	    },
	    function (d) {
             //return "<a title='"+d.subject+"' href='/civicrm/mailing/report?mid="+d.id+"' target='_blank'>"+d.name+"</span>";
             return "<a title='"+d.subject+"' href='/civicrm/dataviz/mailing/"+d.id+"' >"+d.name+"</span>";
	    },
	    function (d) {
		return "<a href='/civicrm/campaign/add?reset=1&action=update&id="+d.campaign_id+"' target='_blank'>"+d.campaign+"</a>";
	    },
	    function (d) {
              return d.recipients;
	    },
	    function (d) {
              return d.timebox < 1440 ? (d.timebox/60)+'h' : (d.timebox/1440)+'d';
	    },
	    function (d) {
              return percent(d, 'open', 0);
	    },
	    function (d) {
              return percent(d, 'click', 0);
	    },
	    function (d) {
              return percent(d, 'sign', 0);
	    },
	    function (d) {
              return percent(d, 'share', 0);
	    },
	    function (d) {
              return percent(d, 'viral_sign', 2);
	    },
	    function (d) {
              return percent(d, 'viral_share', 2);
	    },
	    function (d) {
              return percent(d, 'new_member', 2);
	    },
	    function (d) {
              return "<span>"+ (d.nb_donations||0) + (d.recur ? " recurring" : " one-off") + "</span>";
	    },
	    function (d) {
              return "<span>"+ (d.total_amount||0) + " " + d.currency + "</span>";
	    },

	]
    );

  return graph;
}

 
drawPercent("#open", function(d){return d.open});
drawPercent("#click", function(d){return d.click});
drawTable("#table");
//drawType("#type .graph");
drawDate("#date .graph");
//drawStatus("#status .graph");
drawLang("#lang .graph");
drawCampaign("#campaign .graph");

dc.renderAll();

</script>

<style>
.clear {clear:both;}

</style>
{/literal}
