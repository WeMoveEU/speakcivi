{crmTitle string="Recurring Donations"}
<script>

var data={crmSQL file="WMContributionRecur"};
var _fundraisers={crmSQL file="Fundraiser"};
{literal}
  jQuery.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return decodeURI(results[1]) || 0;
    }
};
var fundraiser={};

var graphs = {};
var ndx = crossfilter(data.values);

var dateFormat = d3.time.format("%Y-%m-%d %H:%M:%s");
var day = d3.time.format("%Y-%m-%d");
var time = d3.time.format("%H:%M");

_fundraisers.values.forEach(function(d){
  fundraiser["civimail-"+d.id]=d;
});

_fundraisers=null;

data.values.forEach(function(d){
//  var dd= d.date;
  //d.date = dateFormat.parse(dd);
  if (d.currency== "EUR") d.currency = "&euro;";
  if (d.currency== "GBP") d.currency = "?";
  d.date = new Date (d.date);
  if (d.contact_since) {
    d.contact_since = new Date (d.contact_since);
    d.contact_since_days = Math.floor(( d.date - d.contact_since ) / 86400000);  
  }
  if (d.cancel_date)
    d.cancel_date = new Date (d.cancel_date);
  d.day= day(d.date); 
  if (d.status == "Pending" && +d.nb > 0) 
    d.status = "Running";
});

(function ($) {
jQuery(function($) {
	if(data.is_error){
		 CRM.alert(data.error);
	}

//https://www.wemove.eu/civicrm/dataviz/WM_contribution_recur?since=2017-12-01

	$(".crm-container").removeClass("crm-container");
  $("h1.page-header,.breadcrumb,#page-header").hide();


	drawNumbers(graphs);
	graphs.table = drawTable('#contribution');
  graphs.search = drawTextSearch('#input-filter');
	graphs.status= drawStatus('#status graph');
	graphs.processor= drawProcessor('#processor graph');
	graphs.campaign= drawCampaign('#campaign .panel-body');
	graphs.country = drawCountry('#country .panel-body');
	graphs.language = drawLanguage('#language .panel-body');
	graphs.month = drawMonth('#date graph');
	graphs.amount = drawDate('#amount graph');
	dc.renderAll();

});

function drawNumbers (graphs){

  var formatPercent =d3.format(".2%");
  var format = d3.format (".3s");
 
	var dim = ndx.dimension(function(d) { return true; });

	var reducer = reductio();

	reducer.value("nb").count(true).sum("nb").avg(true);
	reducer.value("amount").count(true).sum("amount").avg(true);
	reducer.value("total_amount").sum("total_amount");

	var group=dim.group();
	reducer(group);

	graphs.nb_mailing=dc.numberDisplay(".nb_recurring") 
	.valueAccessor(function(d){ return d.value.nb.count})
	.html({some:"%number",none:"no recurring"})
  .formatNumber(format)
	.group(group);
	
  graphs.nb_mailing=dc.numberDisplay(".amount_recurring") 
	.valueAccessor(function(d){ return d.value.amount.sum})
  .formatNumber(format)
	.html({some:"%number",none:"no recurring"})
	.group(group);
  
  graphs.nb_mailing=dc.numberDisplay(".amount_recurring_avg") 
	.valueAccessor(function(d){ return d.value.amount.avg})
  .formatNumber(format)
	.html({some:"%number",none:"no recurring"})
	.group(group);

  graphs.nb_mailing=dc.numberDisplay(".nb_donation") 
	.valueAccessor(function(d){ return d.value.nb.sum})
	.html({some:"%number"}).formatNumber(format).group(group);

  graphs.nb_mailing=dc.numberDisplay(".nb_donation_avg") 
	.valueAccessor(function(d){ return d.value.nb.avg})
	.html({some:"%number",none:"no donations"})
  .formatNumber(format).group(group);

  graphs.nb_mailing=dc.numberDisplay(".total_donation") 
	.valueAccessor(function(d){ return d.value.total_amount.sum})
	.html({some:"%number",none:"no donations"})
  .formatNumber(format)
	.group(group);

  graphs.nb_mailing=dc.numberDisplay(".total_donation_avg") 
	.valueAccessor(function(d){ return d.value.total_amount.sum/d.value.nb.count})
	.html({some:"%number",none:"no donations"})
  .formatNumber(format)
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
  var group = dim.group().reduceSum(function(d){return 1;});
  var graph  = dc.pieChart(dom)
    .innerRadius(10).radius(50)
    .width(100)
    .height(100)
    .dimension(dim)
    .colors(d3.scale.category10())
    .group(group);

  graph.filter("Pending");
  graph.filter("In Progress");
  graph.filter("Running");
  return graph;
}

function drawTextSearch (dom) {

  var dim = ndx.dimension(function(d) { 
    var t="";
    var t= d.camp.toString().toLowerCase() + " "+d.source.toLowerCase()+ " "+ d.content.toLowerCase() + d.ab_test.toLowerCase()+" "+d.ab_variant.toLowerCase() || "?";
    if (fundraiser[d.source]) t += " "+ fundraiser[d.source].name.toLowerCase();
    return t;
});

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

function drawLanguage(dom) {
	  var dim = ndx.dimension(
       function(d){
         return d.lang.substring(0,2) || "?";
    });

	  var group = dim.group().reduceSum(function(d){return 1;});

	  var graph  = dc.rowChart(dom)
	    .width(0)
	    .height(105)
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

	function drawCountry (dom) {
	  var dim = ndx.dimension(
       function(d){
         return d.country || "?";
    });

	  var group = dim.group().reduceSum(function(d){return 1;});

	  var graph  = dc.rowChart(dom)
	    .width(0)
	    .height(150)
	    .gap(0)
	    .rowsCap(8)
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
         if (fundraiser[d.source]) return fundraiser[d.source].name;
        
         return d.camp || "?";
    });

	  var group = dim.group().reduceSum(function(d){return 1;});

	  var graph  = dc.rowChart(dom)
	    .width(0)
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
  //var dim = ndx.dimension(function (d) {   return [+d.date, +d.amount, d.lang];  }); 
  var dim = ndx.dimension(function (d) {   return d.date;  }); 
  var group = dim.group().reduceSum(function(d) {return d.amount;});
  //var group = dim.group();
  var since = dim.bottom(1)[0].date;

  if (jQuery.urlParam("since")){
    since = day.parse(jQuery.urlParam("since"))
    dim.filterAll();
    dim.filterRange([since, new Date()]);
  }
  //var range= [since, dim.top(1)[0].date];
  var range= [since, new Date()];

  var graph= dc.lineChart(dom) //scatterPlot(dom) //lineChart(dom)
	.width(0).height(180)
	.dimension(dim)
    .group(group)
	.x(d3.time.scale().domain(range))
//.y(d3.scale.linear().domain([0., 100.]))
    //.valueAccessor(function(d) { return d.value.min; }) 
    .elasticX(true)
//    .elasticY(true)
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
  var dim = ndx.dimension(function(d) {return d.id;});

  var graph=dc.dataTable(dom)
    .dimension(dim)
    .group(function(d) {
        return "<b>"+day(d.date)+"</b>";//d.name;
    })
    .sortBy(function (d) { return d.date })
    .order(d3.descending)
    .size(200)
    .columns([
              function(d){
                var c = "";
                if (d.cancel_date) {
                  var delta = Math.floor(( d.cancel_date - d.date ) / 86400000);  
                  if (delta == 0) delta ="";
                  c = " <span class='glyphicon glyphicon-log-out' title='Cancelled donation on "+day(d.cancel_date)+"'></span> "+delta+" ";
                }
                return "<a href='"+CRM.url('civicrm/contact/view',{cid: d. contact_id})+"'>"+time(d.date)+c+"</a>"
	      },
              function (d) {
                return "<a title='contact since "+day(d.contact_since)+" "+d.contact_since_days +" days' href='"+CRM.url('civicrm/contact/view',{cid: d. contact_id, selectedChild:'contribute'})+"'>"+d.first_name+"</a>";

              },
              function(d){
                return "<a title='received "+d.total_amount+d.currency+" in "+d.nb +" donations'href='"+CRM.url('civicrm/contact/view/contributionrecur',{cid: d. contact_id,id:d.id}) +"'>"+ d.amount +" " +d.currency + "/"+d.frequency+"</a>";},
              function(d){return d.processor},
              function(d){return d.country},
              function(d){return d.status},
              function(d){return d.camp},
              function(d){
                 if (fundraiser[d.source]) return "<span title='"+fundraiser[d.source].subject+"'>"+fundraiser[d.source].name+"</span>";
                 return d.source},
              function(d){return d.medium},
              function(d){return d.content},
              function(d){return d.ab_test},
              function(d){return d.ab_variant},
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
				<li class="list-group-item"><span class="badge nb_recurring"></span>Nb recurring</li>
				<li class="list-group-item"><span class="badge amount_recurring_avg" title='average amount'></span><span class="amount_recurring"></span> recurring amount</li>
				<li class="list-group-item" title="number of donations generated by the recurring"><span class="badge nb_donation_avg"></span> <span class="nb_donation"></span> donations received</li>
				<li class="list-group-item" title="total amount of donations generated by the recurring"><span title='average' class="badge total_donation_avg"></span>Total received <span class="total_donation"></span></li>
				<li class="list-group-item" title="how long have the donors been members?"><span class="badge known_since"></span>Known since</li>
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
		<div class="panel panel-default" id="language">
			<div class="panel-heading" title="Languages donation">Language</div>
			<div class="panel-body"><graph />
			</div>
		</div>
		<div class="panel panel-default" id="country">
			<div class="panel-heading" title="Country donation">Country</div>
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
<th>date</th>
<th>donor</th>
<th>amount</th>
<th>processor</th>
<th>country</th>
<th>status</th>
<th>campaign</th>
<th>source</th>
<th>medium</th>
<th>AB test</th>
<th>AB variant</th>
</tr>
</thead>
<tbody>
</tbody>
</table>
</div>

</div>


{literal}
<style>
.row div.dc-chart {float:none;}

.row .dc-chart .pie-slice {fill:white;}
.row .dc-chart g.row text {fill:black;}
</style>
{/literal}
