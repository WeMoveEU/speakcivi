<script>
var data={crmSQL file="WMContributionCampaign"};
{literal}

var graphs = {};
var ndx = crossfilter(data.values);

var dateFormat = d3.time.format("%Y-%m-%d %H:%M:%s");
var day = d3.time.format("%Y-%m-%d");

var avgformat = d3.format (".3s");

data.values.forEach(function(d){
//  var dd= d.date;
  //d.date = dateFormat.parse(dd);
});

(function ($) {
jQuery(function($) {
	if(data.is_error){
		 CRM.alert(data.error);
	}

	$(".crm-container").removeClass("crm-container");
  $("h1.page-header,.breadcrumb,#page-header").hide();

	drawNumbers(graphs);
	graphs.table = drawTable('#contribution');
  graphs.search = drawTextSearch('#input-filter');
	graphs.status= drawStatus('#status graph');
//	graphs.processor= drawProcessor('#processor graph');
	graphs.campaign= drawCampaign('#campaign');
//	graphs.country = drawCountry('#country');
//	graphs.month = drawMonth('#date graph');
//	graphs.amount = drawDate('#amount graph');
	dc.renderAll();

});

function drawNumbers (graphs){

  var formatPercent =d3.format(".2%");
  var format = d3.format (".3s");
 
	var dim = ndx.dimension(function(d) { return true; });

	var reducer = reductio();

//status=1 in progress=5
        var success=function(d) {return d.status_id == 1 || d.status_id == 5 || d.status_id==2};
        var fail=function(d) {return !success(d);};
	reducer.value("nb").count(true).filter(success).sum("nb");
	reducer.value("amount").count(true).filter(success).sum("amount").avg(true);
//	reducer.value("amount").count(true).sum("amount").avg(true);
	reducer.value("nb_fail").count(true).filter(fail).sum("nb");
	reducer.value("amount_fail").count(true).filter(fail).sum("amount").avg(true);

	var group=dim.group();
	reducer(group);

	graphs.nb=dc.numberDisplay(".nb") 
	.valueAccessor(function(d){ return d.value.nb.sum})
	.html({some:"%number",none:"no donations"})
  .formatNumber(format)
	.group(group);
	
  graphs.total_amount=dc.numberDisplay(".amount") 
	.valueAccessor(function(d){ return d.value.amount.sum})
  .formatNumber(format)
	.html({some:"%number"})
	.group(group);
  
  graphs.avg_amount=dc.numberDisplay(".amount_avg") 
	.valueAccessor(function(d){ return d.value.nb.sum ? d.value.amount.sum/d.value.nb.sum : 0 })
  .formatNumber(format)
	.html({some:"%number",none:"no recurring"})
	.group(group);

	graphs.nb=dc.numberDisplay(".nb_fail") 
	.valueAccessor(function(d){ return d.value.nb_fail.sum})
	.html({some:"%number",none:"no fails"})
  .formatNumber(format)
	.group(group);
	
  graphs.total_amount=dc.numberDisplay(".amount_fail") 
	.valueAccessor(function(d){ return d.value.amount_fail.sum})
  .formatNumber(format)
	.html({some:"%number"})
	.group(group);
  
  graphs.reducer=group;
}



function drawProcessor (dom) {
  var dim = ndx.dimension(function(d){return d.processor;});
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

function drawStatus (dom) {
  var dim = ndx.dimension(function(d){return d.status;});
  var group = dim.group().reduceSum(function(d){return d.nb;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(50)
    .width(100)
    .height(100)
    .dimension(dim)
    .colors(d3.scale.category10())
    .group(group);

  return graph;
}

function drawTextSearch (dom) {

  var dim = ndx.dimension(function(d) { return d.camp.toLowerCase() + " "+d.utm_source.toLowerCase() + " " + d.mailing.toLowerCase() || "?"});

	function debounce(fn, delay) {
		var timer = null;
		return function () {
			var context = this, args = arguments;
			clearTimeout(timer);
			timer = setTimeout(function () {
				fn.apply(context, args);
			}, delay);
		};
	}

  d3.select(dom).on("keyup",debounce (function () {
    var s= d3.select(this).property("value").toLowerCase();
    dim.filterAll();
    dim.filterFunction(function (d) { return d.indexOf (s) !== -1;} );
	  dc.redrawAll();
  },250));

  return dim;

}

	function drawCountry (dom) {
	  var dim = ndx.dimension(
       function(d){
         return d.country || "?";
    });

	  var _group = dim.group().reduceSum(function(d){return 1;});

	  var graph  = dc.rowChart(dom)
	    .width(200)
	    .height(275)
	    .gap(0)
	    .rowsCap(18)
	    .ordering(function(d) { return -d.value })
	    .dimension(dim)
	    .elasticX(true)
.labelOffsetY(10)
.fixedBarHeight(14)
.labelOffsetX(2)
    .colorCalculator(function(d){return 'lightblue';})
	    .group(group);

    graph.xAxis().ticks(4);
    graph.margins().left = 5;
    graph.margins().top = 0;
    graph.margins().bottom = 0;
	  return graph;
};

	function drawCampaign (dom) {
	  var dim = ndx.dimension(
       function(d){
         return d.camp || "?";
    });

	  var _group = dim.group().reduceSum(function(d){return d.nb;});
          var group = {
            all:function () {return _group.all().filter(function(d) {return d.value != 0;})},
            "top":function () {return _group.all().filter(function(d) {return d.value != 0;})}
          };

	  var graph  = dc.rowChart(dom)
	    .width(200)
	    .height(275)
	    .gap(0)
	    .rowsCap(18)
	    .ordering(function(d) { return -d.value })
	    .dimension(dim)
	    .elasticX(true)
.labelOffsetY(10)
.fixedBarHeight(14)
.labelOffsetX(2)
    .colorCalculator(function(d){return 'lightblue';})
	    .group(group);

    graph.xAxis().ticks(4);
    graph.margins().left = 5;
    graph.margins().top = 0;
    graph.margins().bottom = 0;
	  return graph;
};

function drawMonth (dom) {
  var dim = ndx.dimension(function (d) {return d3.time.month(d.date);}); 
  var group = dim.group().reduceSum(function(d) {return 1;});
  var range= [ dim.bottom(1)[0].date, dim.top(1)[0].date];
  var graph = dc.barChart(dom)
      .width(250)
			.height(100)
			.margins({top: 0, right: 50, bottom: 20, left:40})
			.dimension(dim)
			.group(group)
      .brushOn(true)
			.centerBar(true)
			.gap(1)
			.x(d3.time.scale().domain(range))
			.round(d3.time.month.round)
			.xUnits(d3.time.months);

    graph.xAxis().ticks(4);
    graph.yAxis().ticks(4);

  return graph;
}

function drawDate (dom) {
  var dim = ndx.dimension(function (d) {   return d.date;  }); 
  var group = dim.group().reduceSum(function(d) {return d.amount;});
  var range= [ dim.bottom(1)[0].date, dim.top(1)[0].date];



  var graph= dc.lineChart(dom)
	.width(200).height(180)
	.dimension(dim)
    .group(group)
    .renderArea(true)

	.x(d3.time.scale().domain(range))
    //.valueAccessor(function(d) { return d.value.min; }) 
//    .elasticX(true)
    .elasticY(true)
    .mouseZoomable(true)
    .rangeChart(graphs.month)
    //.brushOn(true)
    .margins({ top: 10, left: 50, right: 10, bottom: 50 });
    
  graph.xAxis().ticks(3);

  d3.select('#date_select').on('change', function(){ 
	  var nd = new Date(), now = new Date();
    switch (this.value) {
			case "today":
        nd = d3.time.day(now);
				break;
			case "week":
        nd = d3.time.monday(now);
				break;
			case "month":
        nd = d3.time.month(now);
				break;
			default:
        nd.setDate(nd.getDate() - +this.value);
		}
    dim.filterAll();
    dim.filterRange([nd, now]);
    //graph.replaceFilter(dc.RangedFilter(nd, now));
    graph.rescale();
    graph.redrawGroup();
//    dc.redrawAll();    
  });
  return graph;
//    .renderlet(function (chart) {chart.selectAll("g.x text").attr('dx', '-30').attr('dy', '-7').attr('transform', "rotate(-90)");});
}


function drawTable(dom) {
  var dim = ndx.dimension(function(d) {return d.campaign_id;});

  var graph=dc.dataTable(dom)
    .dimension(dim)
    .group(function(d) {
        return d.camp;//d.name;
    })
    .sortBy(function (d) { return d.campaign_id })
    .order(d3.descending)
    .size(200)
    .columns([
              function(d){return d.amount},
              function(d){return d.nb},
              function(d){return avgformat(d.amount/d.nb)},
              function(d){return d.instrument},
              function(d){return d.status},
              function(d){return d.utm_campaign},
              function(d){return d.mailing || d.utm_source},
              function(d){return d.utm_medium}
             ])
    ;
  return graph;
}

})(jQuery);
{/literal}</script>

<div class="row">
	<div class="col-md-3">
		<div id="overview">
			<ul class="list-group">
				<li class="list-group-item"><span class="badge nb"></span>Nb donations</li>
				<li class="list-group-item"><span class="badge amount_avg" title='average amount'></span><span class="amount"></span> amount</li>
				<li class="list-group-item"><span class="badge nb_fail"></span>Nb fails</li>
				<li class="list-group-item"><span class="badge amount_avg_fail" title='average failed amount'></span><span class="amount_fail"></span> amount fails</li>
			</ul>
      <span id="status"><graph/></span>
      <span id="processor"><graph/></span>

		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default" id="date">
			<div class="panel-heading" title="when was the recurring donation made?">Date
<select id="date_select">
  <option value="Infinity">All</option>
  <option value="today">Today</option>
  <option value='1'>last 24 hours</option>
  <option value="week">This week</option>
  <option value="month">This month</option>
  <option value='30'>last 30 days</option>
  <option value='90'>last 90 days</option>
</select>
</div>
			<div class="panel-body"> <graph />
        <div id="amount"><graph /></div>
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default" id="country">
			<div class="panel-heading" title="Language donation">Language</div>
			<div class="panel-body"><graph />
			</div>
		</div>
	</div>
	<div class="col-md-3">
		<div class="panel panel-default" id="campaign">
			<div class="panel-heading" title="Campaigns or source or ab test"><input id="input-filter" placeholder="Campaign"/></div>
			<div class="panel-body"><graph />
			</div>
		</div>
	</div>
</div>


<div class="row">
<div class="col-md-12">
<table id="contribution" class="table">
<thead>
<tr>
<th>amount</th>
<th>nb</th>
<th>avg</th>
<th>processor</th>
<th>status</th>
<th>campaign</th>
<th>source</th>
<th>medium</th>
</tr>
</thead>
<tbody>
</tbody>
</table>
</div>

</div>


{literal}
<style>
.row .dc-chart .pie-slice {fill:white;}
.row .dc-chart g.row text {fill:black;}
</style>
{/literal}
