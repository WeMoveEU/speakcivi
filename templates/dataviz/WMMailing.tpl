{crmTitle string="<span class='data_count'><span class='filter-count'></span> Mailings out of <span class='total-count'></span> in the last $id days</span>"}
{literal}
<style>
#campaign .dc-chart g.row text {fill:grey;}
#lang .pie-slice {fill:white;}
</style>
{/literal}
<a class="reset" href="javascript:sourceRow.filterAll();dc.redrawAll();" style="display: none;">reset</a>

<div class="row">
      <div class="col-xs-9 col-sm-9 col-md-4" id="filter_field_container"> 
        <p>Search or Filter:</p> 
        <div class="input-group"> 
            <input type="text" id="search-input" class="form-control input" placeholder="internal names or campaign">
        </div>
      </div>
</div>

<div class="row">
<div id="campaign" class="col-md-2"><h3>Campaign</h3><div class="graph"></div></div>
<div id="lang" class="col-md-2"><h3>Language</h3><div class="graph"></div></div>
<div id="sign" class="col-md-2"><h3>% Sign</h3><div class="graph"></div><div class="avg"></div></div>
<div id="sign_new" class="col-md-2"><h3>% New Members</h3><div class="graph"></div><div class="avg"></div></div>
<div id="date" class="col-md-4"><h3></h3><div class="graph"></div></div>
</div>

<div class="row">
<table class="table table-striped" id="table">

<thead><tr>
	<th>Date</th>
	<th>Mailing</th>
	<th>Campaign</th>
	<th>Recipients</th>
	<th>Sign</th>
	<th>New Members</th>
	<th>Pending</th>
	<th>Share</th>
	<th>Monthly €</th>
	<th>Single €</th>
	</tr></thead>
	</table>
	</div>

	<div class="row">
	</div>

	<script>
	var data = {crmSQL json="WMmailings" days=$id debug=1};
	if (data.is_error) CRM.alert(data.error);
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


	data.values.forEach(function(d){
	  d.date = dateFormat.parse(d.date);
	});


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

	function orderValue(p) {
	  return p.count;
	}




	var ndx  = crossfilter(data.values)
	  , all = ndx.groupAll();

	var totalCount = dc.dataCount("h1 .data_count")
	      .dimension(ndx)
	      .group(all);

	function drawNumber(graph,dom) {
	  var graph= dc.numberDisplay(dom)
	    .group(graph.group())
	;//    .html ({
	//     some:'%number'
	//    });
	  
	}

	function drawCampaign (dom) {
	  var dim = ndx.dimension(function(d){return d.campaign || "?"});
	  var _group = dim.group();
          var group = {
            all:function () {return _group.all().filter(function(d) {return d.value != 0;})}
          };
	//  .reduce(reduceAdd,reduceRemove,reduceInitial);
	.reduceSum(function(d){return 1;});
	  var graph  = dc.rowChart(dom)
	    .width(200)
	    .height(200)
	    .gap(0)
	    .rowsCap(10)
	    .ordering(function(d) { return -d.value })
	//    .ordering(function(d) { return -d.value.count })
	//    .valueAccessor( function(d) { return d.value.count })
	//    .label (function (d) {return d.key;})
	//    .title (function (d) {return d.key + ":" + d.value.count + "\nsignatures:" + d.value.sign + "\nnew:"+d.value.sign_new;})
	    .dimension(dim)
	    .elasticX(true)
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
	  var graph=dc.lineChart(dom + " .graph")
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

	  var num= dc.numberDisplay(dom+ " h3")
	    .group(group)
	    .html ({
	     some:'%number recipients'
	    });

	  return graph;
	}


	function drawPercent (dom,dimfct) {
	  //var dim = ndx.dimension(function(d){return 10 * Math.floor((accessor(d)/d.recipients* 10)) });
	  var dim = ndx.dimension(dimfct);
	//  var group = dim.group().reduce(reduceAdd,reduceRemove,reduceInitial);
	//Sum(function(d){return 1;});
	  var group = dim.group().reduceSum(function(d){return d.recipients;});


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
	       return;
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
	  var dim = ndx.dimension (function(d) {return d.name});
	  var graph = dc.dataTable(dom)
	    .dimension(dim)
	    .size(5000)
	    .group(function(d){ return ""; })
	    .sortBy(function(d){ return d.date; })
	    .order(d3.descending)
	    .columns(
		[
		    function (d) {
			return prettyDate(d.date);
		    },
		    function (d) {
		     return "<a title='"+d.subject+"' href='/civicrm/mailing/report?mid="+d.id+"' target='_blank'>"+d.name+"</span>";
		     //return "<a title='"+d.subject+"' href='/civicrm/dataviz/mailing/"+d.id+"' >"+d.name+"</span>";
		     //return "<a title='"+d.name+"' href='/civicrm/dataviz/mailing/"+d.id+"' >"+d.subject+"</span>";
		    },
		    function (d) {
			//return "<a href='/civicrm/campaign/add?reset=1&action=update&id="+d.campaign_id+"' target='_blank'>"+d.campaign+"</a>";
			return "<a href='/civicrm/dataviz/csvparam/CampaignActivities/"+d.campaign_id+"' target='_blank'>"+d.campaign+"</a>";
		    },
	//	    function (d) {return d.owner },
		    function (d) {
		      return d.recipients;
		    },
		    function (d) {
		      if (d.sign > 0)  return "<span title='"+d.sign+" contacts' >"+Math.round (100*d.sign/d.recipients)+"%</span>";
		      return "";
	     //         return "<span title='"+d.open+" contacts' >"+Math.round (100*d.open/d.recipients)+"%</span>";
		    },
		    function (d) {
		      if (d.sign > 0) return "<span title='"+d.sign_new+" contacts' >"+Math.round (1000*d.sign_new/d.sign)/10+"%</span>";
		      return "";
	    //          return "<span title='"+d.click+" contacts' >"+Math.round (100*d.click/d.recipients)+"%</span>";
		    },
		    function (d) {
		      if (d.sign > 0) return "<span title='"+d.pending+" contacts' >"+Math.round (1000*d.pending/d.sign)/10+"%</span>";
		      return "";
		    },
		    function (d) {
		      return "<span title='"+d.share+" contacts' >"+Math.round (1000*d.share/d.recipients)/10+"%</span>";
		    },
		    function (d) {
                      if (0 == d.paid_recur+d.processing_recur) return "";
		      return "<span title='processing:"+d.processing_recur+"\npaid:"+d.paid_recur+"€' >"+Math.floor(18*(d.processing_recur+d.paid_recur))+"€</span>";
		    },
		    function (d) {
                      if (0 == d.paid_single+d.processing_single) return "";
		      return "<span title='processing:"+d.processing_single+"\npaid:"+d.paid_single+"'>"+(d.processing_single+d.paid_single)+"€</span>";
	    },
	]
    );

  return graph;
}

var graphs={}; 
graphs.sign=drawPercent("#sign", function(d){return Math.floor(100*d.sign/d.recipients)});
graphs.sign=drawPercent("#sign_new", function(d){return Math.floor(100*d.sign_new/d.sign)});
graphs.wall = drawTable("#table");
//drawType("#type .graph");
graphs.date=drawDate("#date");
//drawStatus("#status .graph");
graphs.lang=drawLang("#lang .graph");
graphs.campaign=drawCampaign("#campaign .graph");

dc.renderAll();

jQuery (function($) {
  $("#search-input").keyup (function () {
    var s = $(this ).val(); //.toLowerCase();
    graphs.wall.dimension().filter(function (d) { 
      return d.indexOf (s) !== -1;} );
    $(".resetall").attr("disabled",true);
    dc.redrawAll();
  });

});
</script>

<style>
.clear {clear:both;}

</style>
{/literal}
