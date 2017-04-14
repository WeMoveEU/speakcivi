{crmTitle string="Mailings"}
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
  <div class="filter"><input type="checkbox" name="small" id="filter_reminders" /> Reminders</div>
  <div class="filter"><input type="checkbox" name="petitions" id="filter_petitions" checked /> Petitions</div>
  <div class="filter"><input type="checkbox" name="fundraisers" id="filter_fundraisers" checked /> Fundraisers</div>
  <div class="filter"><input type="checkbox" name="surveys" id="filter_surveys" checked /> Surveys</div>
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
	<div class="col-md-3">
		<div id="overview">
			<ul class="list-group">
				<li class="list-group-item"><span class="badge nb_mailing"></span><button id="download"><span class="glyphicon glyphicon-download"></span>Mailings</button></li>
				<li class="list-group-item"><span class="badge nb_recipient"></span>Recipients</li>
				<li class="list-group-item"><span class="badge badge_unsub"></span><span class="nb_unsub"></span></li>
				<li class="list-group-item"><span class="badge nb_open"></span>Open</li>
				<li class="list-group-item"><span class="badge nb_click"></span>click</li>
				<li class="list-group-item"><span class="badge nb_share"></span>Shares</li>
				<li class="list-group-item"><span class="badge badge_signature"></span><span class="nb_signature"></span></li>
				<li class="list-group-item"><span class="badge badge_new_member"></span><span class="nb_new_member"></span></li>
				<li class="list-group-item"><span class="badge amount"><span class='amount_recur'></span><span class='amount_oneoff'></span></span><span class='nb_recur'></span><span class='nb_oneoff'></span> donations</li>
			</ul>
		</div>
	</div>
<div id="campaign" class="col-md-2"><div class="panel panel-default">
  <div class="panel-heading" title="click to select campaigns">Campaign
</div>
<div class="panel-body"> <div class="graph"></div></div></div></div>
<div id="lang" class="col-md-2"><div class="panel panel-default"><div class="panel-heading">Language</div><div class="panel-body"> <div class="graph"></div></div></div></div>
<div id="date" class="col-md-4"><div class="panel panel-default"><div class="panel-heading">Date sent
<select id="date_select">
  <option value="Infinity">All</option>
  <option value="today">Today</option>
  <option value='1'>last 24 hours</option>
  <option value="week">This week</option>
  <option value='7'>Last 7 days</option>
  <option value="month">This month</option>
  <option value='30'>last 30 days</option>
  <option value='90'>last 90 days</option>
</select>

</div><div class="panel-body"> <div class="graph"></div></div></div></div>
</div>

<div class="row">
<table class="table table-striped" id="table">

<thead><tr>
<th>Date</th>
<th><input id="input-filter" placeholder="Name" title="search on mailing or campaign name"/>
</th>
<th>Campaign</th>
<th>Recipients</th>
<th>Open</th>
<th>Clicks</th>
<th>Unsubs</th>
<th>Signs</th>
<th>Shares</th>
<th>New members</th>
<th># Donations</th>
<th>Total amount</th>
</tr></thead>
</table>
</div>

<div class="row">
</div>

<script>
var data = {crmSQL file="WMmailings"};
var campaigns= {crmSQL file="Campaigns"};

var dateFormat = d3.time.format("%Y-%m-%d %H:%M:%S");
var currentDate = new Date();
var graphs = [];
var color = d3.scale.linear().range(["red", "orange","green"]).domain([0,1,1.5]).interpolate(d3.interpolateHcl).clamp(true);
var colordt = d3.scale.linear().range(["red", "black"]).domain([60, 10]).interpolate(d3.interpolateHcl).clamp(true);

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
  if (d[attr] ==0) return " ";
  return "<span title='"+d[attr]+" contacts' >"+ (100*d[attr]/d.recipients).toFixed(precision) +"%</span>";
}
var formatPercent =d3.format(".2%");

function lookupTable(data,key,value) {
  var t= {}
  data.forEach(function(d){t[d[key]]=d[value]});
  return t;
}

function downloadButton (dom,dim) {
  CRM.$(dom).click(function() {
    var blob = new Blob([d3.csv.format(dim.top(Infinity))],{type: "text/csv;charset=utf-8"});
    saveAs(blob, 'data.csv');
  });
}

parentCampaign={};

campaigns.values.forEach(function(d){
  if (d.id==d.parent_id)
    parentCampaign[d.id]=d.name.slice(0,-3);
});


data.values.forEach(function(d){
  d.date = dateFormat.parse(d.date);
  d.received_median= dateFormat.parse(d.received_median);
});

CRM.$("h1").html(CRM.$("h1").html() + " last updated :"+ prettyDate(data.values[0].last_updated));

function filterReminders(d) {
  return d.indexOf('-Reminder-') === -1;
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
  p.sign_new += +v.new_member;
  return p;
}

function reduceRemove(p, v) {
  --p.count;
  p.sign -= +v.sign;
  p.recipients-= +v.recipients;
  p.sign_new -= +v.new_member;
  return p;
}

function reduceInitial() {
  return {count: 0, sign: 0,sign_new:0,recipients:0};
}

var ndx  = crossfilter(data.values)
  , all = ndx.groupAll();
var nameDim = ndx.dimension(function(d) { return d.name; });
var signDim = ndx.dimension(function(d) { return d.sign; });
var giveDim = ndx.dimension(function(d) { return +d.nb_oneoff + +d.nb_recur; });
var timeDim = ndx.dimension(function(d) { 
  if(!d.timebox) return 144000; 
  return d.timebox; 
});

nameDim.filter(filterReminders);
timeDim.filterExact(144000);
jQuery(function($) {
  $(".crm-container").removeClass("crm-container");

  $('#filter_reminders').on('change', function() {
    if (this.checked) {
      nameDim.filterAll();
    } else {
      nameDim.filter(filterReminders);
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

function drawNumbers (graphs){
  var average = function(d) {
      return d.qty ? d.total / d.qty : 0;
  };

  var percentRecipient=function (value) {return formatPercent (value / graphs.nb_recipient.value());}
  var percentSignature=function (value) {return formatPercent (value / graphs.nb_signature.value());}

  var group = ndx.groupAll().reduce(
		function (p, v) {
        p.mailing++;
				p.new_member += +v.new_member;
				p.optout += +v.optout;
				p.pending += +v.pending;
				p.share+= +v.share;
				p.viral_share+= +v.viral_share;
				p.signature += +v.sign;
				p.viral_signature += +v.viral_sign;
        p.recipient += +v.recipients;
        p.open += +v.open;
        p.unsub += +v.unsub;
        p.click += +v.click;
        p.amount_recur += +v.amount_recur;
        p.amount_oneoff += +v.amount_oneoff;
        p.nb_recur += +v.nb_recur;
        p.nb_oneoff += +v.nb_oneoff;
				return p;
		},
		function (p, v) {
        p.mailing--;
				p.optout -= +v.optout;
				p.new_member -= +v.new_member;
				p.pending -= +v.pending;
				p.share -= +v.share;
				p.signature -= +v.sign;
				p.viral_share -= +v.viral_share;
				p.viral_signature -= +v.viral_sign;
        p.recipient -= +v.recipients;
        p.open -= +v.open;
        p.unsub -= +v.unsub;
        p.click -= +v.click;
        p.amount_recur -= +v.amount_recur;
        p.amount_oneoff -= +v.amount_oneoff;
        p.nb_recur -= +v.nb_recur;
        p.nb_oneoff -= +v.nb_oneoff;
				return p;
		},
		function () { return {unsub:0, mailing:0,nb_recur:0,nb_oneoff:0,amount_recur:0,amount_oneoff:0,share:0,new_member:0,optout:0,pending:0,signature:0,recipient:0,click:0,open:0,viral_share:0,viral_signature:0}}
  );
  
	function renderLetDisplay(chart,factor, ref) {
		 ref = ref || graphs.nb_recipient.value() || 1;
     var c=1;
     if (factor) {
       var avg_value={open:30,click:10,signature:7,share:1,new_member:6,unsub:0.25};
       c=(chart.value()/ref*100)/avg_value[factor];
     }
		 d3.selectAll(chart.anchor()).style("background-color", color(c))
		 .attr("title", d3.format("")(chart.value()));
	}

	graphs.nb_mailing=dc.numberDisplay(".nb_mailing") 
	.valueAccessor(function(d){ return d.mailing})
	.html({some:"%number",none:"no mailing"})
	.group(group);

	graphs.nb_signature=dc.numberDisplay(".nb_signature") 
	.valueAccessor(function(d){ return +d.signature + d.viral_signature})
	.html({some:"<span title='viral and direct'>%number signatures</span>",none:"No signatures"})
	.group(group);

	dc.numberDisplay(".badge_signature") 
	.valueAccessor(function(d){ return d.signature})
	.html({some:"%number",none:""})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'signature')})
	.group(group);

	graphs.nb_unsub=dc.numberDisplay(".nb_unsub") 
	.valueAccessor(function(d){ return d.unsub})
	.html({some:"%number unsubscriptions",none:"No unsubscriptions"})
	.group(group);

	dc.numberDisplay(".badge_unsub") 
	.valueAccessor(function(d){ return d.unsub})
	.html({some:"%number",none:""})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'unsub')})
	.group(group);

	graphs.badge_nb_new_member=dc.numberDisplay(".badge_new_member") 
	.valueAccessor(function(d){ return d.new_member})
	.html({some:"%number",none:"nobody joined"})
  .formatNumber(percentSignature).renderlet(function(chart) {renderLetDisplay(chart,'new_member',graphs.nb_signature.value())})
	.group(group);

	graphs.nb_new_member=dc.numberDisplay(".nb_new_member") 
	.valueAccessor(function(d){ return d.new_member})
	.html({some:"%number new members",none:"No growth"})
	.group(group);

	graphs.nb_pending = dc.numberDisplay(".nb_pending") 
	.valueAccessor(function(d){ return d.pending})
	.html({some:"%number",none:"no signature pending"})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'pending')})
	.group(group);

	graphs.nb_recipient = graphs.nb_recipient= dc.numberDisplay(".nb_recipient") 
	.valueAccessor(function(d){ return d.recipient})
	.html({some:"%number",none:"nobody mailed"})
	.group(group)
	.renderlet(function(c) {
			if (ndx.groupAll().value() == ndx.size())
				d3.selectAll(".resetall").style("display","none");
			else
				d3.selectAll(".resetall").style("display","block");
	})
	;
	dc.numberDisplay(".nb_share") 
	.valueAccessor(function(d){ return d.share})
	.html({some:"%number",none:"nobody shared"})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'share')})
	.group(group);

	dc.numberDisplay(".nb_open") 
	.valueAccessor(function(d){ return d.open})
	.html({some:"%number",none:"nobody opened"})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'open')})
	.group(group);

	dc.numberDisplay(".nb_click") 
	.valueAccessor(function(d){ return d.click})
	.html({some:"%number",none:"nobody clicked"})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'click')})
	.group(group);

	dc.numberDisplay(".nb_leave") 
	.valueAccessor(function(d){ return d.leave})
	.html({some:"%number",none:"nobody left"})
  .formatNumber(percentRecipient).renderlet(function(chart) {renderLetDisplay(chart,'leave')})
	.group(group);

	graphs.amount_oneoff = dc.numberDisplay(".amount_oneoff") 
	.valueAccessor(function(d){ return d.amount_oneoff})
	.html({some:"<span title='one off'>%number<span aria-hidden='true' class='glyphicon glyphicon-gift'></span></span>",none:""})
  .formatNumber(d3.format("2d"))
	.group(group);

	graphs.amount_recur = dc.numberDisplay(".amount_recur") 
	.valueAccessor(function(d){ return d.amount_recur})
	.html({some:'%number<span aria-hidden="true" class="glyphicon glyphicon-repeat"></span> ',none:""})
  .formatNumber(d3.format("2d"))
	.group(group);

	graphs.nb_oneoff = dc.numberDisplay(".nb_oneoff") 
	.valueAccessor(function(d){ return d.nb_oneoff})
	.html({some:"<span title='One off donations'>%number<span aria-hidden='true' class='glyphicon glyphicon-gift'></span> </span>",none:""})
  .formatNumber(d3.format("3d"))
	.group(group);

	graphs.nb_recur = dc.numberDisplay(".nb_recur") 
	.valueAccessor(function(d){ return d.nb_recur})
	.html({some:'%number<span aria-hidden="true" class="glyphicon glyphicon-repeat"></span> ',none:""})
  .formatNumber(d3.format("3d"))
	.group(group);
};

function drawTextSearch (dom) {

  var dim = ndx.dimension(function(d) { return d.name.toString().toLowerCase() +" "+ d.campaign.toString().toLowerCase()});

  CRM.$(dom).keyup (function () {
    var s = CRM.$(this ).val().toLowerCase();
    CRM.$(".resetall").attr("disabled",false);
    throttle();
//        dc.redrawAll();

		var throttleTimer;
		function throttle() {
			window.clearTimeout(throttleTimer);
			throttleTimer = window.setTimeout(function() {
      dim.filterAll();
      dim.filterFunction(function (d) { return d.indexOf (s) !== -1;} );
				dc.redrawAll();
		  }, 250);
		}
  });

  return dim;

}

	function drawCampaign (dom) {
	  var dim = ndx.dimension(
       function(d){
         if (d.parent_campaign_id)
           return parentCampaign[d.parent_campaign_id];
         return d.campaign || "?"}
       );
	  var group = dim.group()
	//  .reduce(reduceAdd,reduceRemove,reduceInitial);
	.reduceSum(function(d){return 1;});
	  var graph  = dc.rowChart(dom)
	    .width(200)
	    .height(275)
	    .gap(0)
	    .rowsCap(18)
	    .ordering(function(d) { return -d.value })
	//    .ordering(function(d) { return -d.value.count })
	//    .valueAccessor( function(d) { return d.value.count })
	//    .label (function (d) {return d.key;})
	//    .title (function (d) {return d.key + ":" + d.value.count + "\nsignatures:" + d.value.sign + "\nnew:"+d.value.sign_new;})
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
	}

function drawNOCampaign (dom) {
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
   .margins({top: 10, right: 20, bottom: 20, left:50})
    .height(250)
    .width(350)
    .dimension(dim)
    .renderArea(true)
    .group(group)
    .brushOn(true)
    .x(d3.time.scale().domain(d3.extent(dim.top(2000), function(d) { return d.date; })))
    .round(d3.time.day.round)
    .elasticY(true)
    .xUnits(d3.time.days);

   graph.yAxis().ticks(3).tickFormat(d3.format(".2s"));
   graph.xAxis().ticks(5);

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
    .size(1000)
    .group(function(d){ return ""; })
    .sortBy(function(d){ return d.date; })
    .order(d3.descending)
    .columns(
	[
	    function (d) {
		//return "<span title='Last updated: " + prettyDate(d.last_updated) + "'>" + prettyDate(d.date) + "</span>";
		//return "<span title='median delivered : " + prettyDate(d.received_median) + "'>" + prettyDate(d.date) + "</span>";
                if (!d.received_median)
  		  return "<span title='delivery median time not generated yet'><i>" + prettyDate(d.date) + "</i></span>";
                var dt = Math.ceil((d.received_median - d.date) / 60000);
                if (dt < 0) //bug and received_median = epoch 
  		  return "<span title='delivery median error'><i>" + prettyDate(d.date) + "</i></span>";
            
		return "<span title='median delivered in "+dt+" min. Started at " + prettyDate(d.date) 
                  +"' style='color:"+colordt(dt)+"' >"
                  + prettyDate(d.received_median) + "</span>";
                
	    },
	    function (d) {
             return "<a title='"+d.subject+"' href='/civicrm/mailing/report?mid="+d.id+"' target='_blank'>"+d.name+"</a>";
	    },
	    function (d) {
		return "<a href='/civicrm/dataviz/WMCampaign/"+d.parent_campaign_id+"' target='_blank'>"+d.campaign+"</a>";
	    },
	    function (d) {
        if (!d.is_completed)
          return "<span class='glyphicon glyphicon-refresh spin' title='not all sent, mailing in progress'></span>";
              return d.recipients;
	    },
//	    function (d) {return d.timebox < 1440 ? (d.timebox/60)+'h' : (d.timebox/1440)+'d';},
	    function (d) {
             return "<a title='"+d.subject+"' href='/civicrm/dataviz/mailing/"+d.id+"' >"+percent(d,'open',0)+"</a>";
              return percent(d, 'open', 0);
	    },
	    function (d) {
              return percent(d, 'click', 1);
	    },
	    function (d) {
              return percent(d, 'unsub', 2);
	    },
	    function (d) {
              return "<span title='" + +d.sign +" directs + " + +d.viral_sign+" virals'>" + formatPercent ((+d.sign + +d.viral_sign)/ d.recipients)+"</span>";
              return percent(d, 'sign', 0);
	    },
	    function (d) {
              return percent(d, 'share', 0);
	    },
//	    function (d) {return percent(d, 'viral_sign', 2);},
//	    function (d) {return percent(d, 'viral_share', 2);},
	    function (d) {
              return percent(d, 'new_member', 2);
	    },
	    function (d) {
          if (!d.nb_oneoff && !d.nb_recur) return "";
          var tx="";
          if (d.nb_recur) 
            tx +="<span title='recurring donations'>"+ d.nb_recur +'<span aria-hidden="true" class="glyphicon glyphicon-repeat"></span></span> '; 
          if (d.nb_oneoff) 
            tx += "<span title='one off'>"+d.nb_oneoff+"<span aria-hidden='true' class='glyphicon glyphicon-gift'></span></span>";
          return tx;
	    },
	    function (d) {
        if (!d.nb_oneoff && !d.nb_recur) return "";
          var tx="";
          if (d.amount_recur) 
            tx +="<span title='recurring donations'>"+ d.amount_recur +'<span aria-hidden="true" class="glyphicon glyphicon-repeat"></span></span> '; 
          if (d.amount_oneoff) 
            tx += "<span title='one off'>"+d.amount_oneoff+"<span aria-hidden='true' class='glyphicon glyphicon-gift'></span></span>";
          return tx + (d.currency=="EUR" ? "&euro;" : "£");
	    },

	]
    );

  return graph;
}

 
//drawPercent("#open", function(d){return d.open});
//drawPercent("#click", function(d){return d.click});
graphs.table= drawTable("#table");
downloadButton ("#download", graphs.table.dimension());
//drawType("#type .graph");
drawNumbers(graphs);
graphs.date = drawDate("#date .graph");
//drawStatus("#status .graph");
graphs.lang = drawLang("#lang .graph");
graphs.campaign = drawCampaign("#campaign .graph");
graphs.search = drawTextSearch('#input-filter');

dc.renderAll();

</script>

<style>
.spin{
     -webkit-transform-origin: 50% 58%;
     transform-origin:50% 58%;
     -ms-transform-origin:50% 58%; /* IE 9 */
     -webkit-animation: spin 2s infinite linear;
     -moz-animation: spin 2s infinite linear;
     -o-animation: spin 2s infinite linear;
     animation: spin 2s infinite linear;
}
.glyphicon-repeat,.glyphicon-gift {font-size:0.7em;}
.clear {clear:both;}

</style>
{/literal}
