{crmTitle string="Tested"}
	{literal}
	<style>
    .widget-content .container {width:auto;}
    .widget-content .col-md-3 {width:50%;}

      nopeh1.page-header,.breadcrumb,header p.lead {display:none;}
 
	 #name g.row text {fill: grey;};
	.countries {stroke:grey;stroke-width:1;}

	.panel .panel-heading .nav-tabs {
		    margin:-10px -15px -12px -15px;
		    border-bottom-width:0;
	}

	.panel .panel-heading .nav-tabs li a {
			    padding:15px;
			    margin-bottom:1px;
			    border:solid 0 transparent;
	}
	.panel .panel-heading .nav-tabs li a:hover {
			    border-color: transparent;
			    }


	.panel .panel-heading .nav-tabs li.active a,.panel .panel-heading .nav-tabs li.active a:hover {
				border:solid 0 transparent;                         
			    }

         #date path.area {fill-opacity:.2;}

         #date .brush rect.extent {fill:lightgrey;}
	</style>
	<div class="container" role="main">
	<div class="page-header"></div>
	<div class="row">
	<div id="date" class="col-xs-12"><div class="panel panel-default"><div class="panel-heading">Date</div>
<div class="panel-body"><div class="graph"></div>
</div>
</div>
</div>
</div>
<div class="row">
	<div id="name" class="col-sm-6 col-xs-12"><div class="panel panel-default">
	  <div class="panel-heading" title="click to select mailing">
	<input id="input-filter" placeholder="Mailing" title="search on name"/>
	</div>
	<div class="panel-body"> <div class="graph"></div></div></div></div>
</div>

	    <script>
	"use strict";
	   var $=jQuery;
           var ndx=null;
var pastel2= ["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd","#fddaec","#f2f2f2"];
var colorType = d3.scale.ordinal().range(pastel2);

	  function setUrl(){
	   //var lang=graphs.lang.filters();
	   var lang=null; // need to take it from the map
	   var name=jQuery("#input-filter").val();
	   var url="?";
	   if (lang && lang.length > 0) url +="lang="+lang+"&";
	   if (name) url +="name="+name+"&";
	   window.history.pushState(null, lang + " " + name, url);

	  };

	  function hasFilter() {
	    return jQuery.urlParam("lang") || jQuery.urlParam("name");
	  };

	  jQuery.urlParam = function(name){
	    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
	    if (results==null){
	       return null;
	    }
	    else{
	       return decodeURI(results[1]) || 0;
	    }
	};

	    var graphs = [];
	    var summary= {};
          var campaigns={};
	{/literal}
          var data= {crmSQL json="Mvt" months=5 lang="INT-EN" debug=1};
          var t={crmSQL json="MvtMailings" limit=300 lang="INT-EN"};
	{literal}
           t.values.forEach(d=>{campaigns[d.id]=d});
           t=null;
	    (function($){
	    draw();
	    })(CRM.$);
	    


    function draw () {
	      
	      //var dateFormat = d3.time.format.utc("%Y-%m-%d");
	      var dateFormat = d3.time.format("%Y%m%d%H");
	      var formatNumber = function (d){ return d3.format(",")(d).replace(",","'")};
	      var formatPercent =d3.format(".2%");
	      $(".crm-container").removeClass("crm-container");
//var colorWrap = function (name,text) { return '<span style="color:'+colorType(name)+' "}; 

   ['new','total','new','existing','share','pending','activated'].forEach (function(d) {
     $("."+d).closest("li").css("background-color",colorType(d));//.css("font-weight","bold");
   });
/*
	      data.values.forEach(function(d) {
		d.total = + d.total;
		d.date = dateFormat.parse(d.ts.toString());
	      }); 
*/

	      ndx = crossfilter(data.values);
	//      graphs.pie = drawPie("#test");
//	      drawNumbers(graphs); //needs to be the first one
	      graphs.name = drawMailing("#name .graph");
	//      graphs.map = drawMap ("#lang .graph");
//	      graphs.lang = drawLang("#lang .pie");
      graphs.date = drawDate("#date .graph");
//      graphs.table = drawTable("#table");
//      graphs.total.on("postRedraw", function(){setUrl()});

//      summary.total = graphs.total.data();


      jQuery (function($) {
        dc.renderAll();
      });


function drawNumbers (graphs){

  
  var group = ndx.groupAll().reduce(
    function (p, v) {
	p.total += +v.total;
	p.completed_new += +v.completed_new;
	p.completed_activated += +v.completed_activated;
	p.completed_existing_member += +v.completed_existing_member;
	p.pending+= +v.pending;
	p.optout+= +v.optout;
	p.share+= +v.share;
	return p;
    },
    function (p, v) {
	p.total -= +v.total;
	p.completed_new -= +v.completed_new;
	p.completed_activated -= +v.completed_activated;
	p.completed_existing_member -= +v.completed_existing_member;
	p.pending-= +v.pending;
	p.optout-= +v.optout;
	p.share-= +v.share;
	return p;
    },
    function () { return {
       total:0,completed_new:0,completed_existing_member:0,pending:0,optout:0,share:0,completed_activated:0
      };
    });
 

  var badging = function (attribute,dom){
    graphs[dom]=dc.numberDisplay("."+dom).group(group)
      .valueAccessor(function(d){return d[attribute]/d.total})
      .formatNumber(formatPercent);
  };

  graphs.total=dc.numberDisplay(".total") 
    .valueAccessor(function(d){ 
       summary.filtered=d.total;
       return d.total
    })
    .formatNumber(formatNumber)
    .group(group);

  graphs.total_percent=dc.numberDisplay(".total_percent") 
    .valueAccessor(function(d){
       if (d.total == summary.total){
         $(".summary_total").parent().slideUp();
         return 1;
       }
       $(".summary_total").parent().slideDown();
       return d.total/summary.total})
    .formatNumber(formatPercent)
    .group(group);

  graphs.new=dc.numberDisplay(".new").group(group)
    .valueAccessor(function(d){return d.completed_new})
    .formatNumber(formatNumber);
  badging ("completed_new","new_percent");


  graphs.existing=dc.numberDisplay(".existing").group(group)
    .valueAccessor(function(d){return d.completed_existing_member})
    .formatNumber(formatNumber);
  badging ("completed_existing_member","existing_percent");

  graphs.existing=dc.numberDisplay(".activated").group(group)
    .valueAccessor(function(d){return d.completed_activated})
    .html({some:"%number (re)activated",none:"activations not computed yet"})
    .formatNumber(formatNumber);
  badging ("completed_activated","activated_percent");

  graphs.optout=dc.numberDisplay(".optout").group(group)
    .valueAccessor(function(d){return d.optout})
    .formatNumber(formatNumber);
  badging ("optout","optout_percent");

  graphs.pending=dc.numberDisplay(".pending").group(group)
    .valueAccessor(function(d){return d.pending})
    .formatNumber(formatNumber);
  badging ("pending","pending_percent");

  graphs.share=dc.numberDisplay(".share").group(group)
    .valueAccessor(function(d){return d.share})
    .formatNumber(formatNumber);
  badging ("share","share_percent");
}


}

function drawDate (dom) {
  //var dim = ndx.dimension(function(d){return [+d.delay,+d.id];});
  var dim = ndx.dimension(function(d){return d.delay;});
  var mailings = graphs.name.group().top(100);
  var _groups = [];
  var groups = [];
  mailings.forEach (m => {
    _groups[m.key] = dim.group().reduceSum (d => (d.id==m.key ? d.total : 0));
    groups[m.key] = {
    all:function () {
     var cumulate = 0;
     var g = [];
     _groups[m.key].all().forEach(function(d) {
       cumulate += d.value;
       g.push({key:d.key,value:{absolute:cumulate,percent:100*cumulate/campaigns[m.key].recipient}})
     });
     return g;
    }
  }; 
//    groups[m] = dim.group().reduceSum (d => {console.log(d.id); return 1});
  });

  var graph=dc.compositeChart(dom)
   .margins({top: 0, right: 20, bottom: 20, left:30})
    .height(350)
    .width(0)
    .dimension(dim)
    .brushOn(false)
    .renderHorizontalGridLines(true)
    .title (function(d) {return d.key+": "+d.value.absolute+" signatures\n"+d.value.percent +"%"})
//    .title (function(d) {return d.key[0]+": "+d.value+" signatures"})
    .x(d3.scale.linear().domain([0,1440]))
//    .x(d3.time.scale.utc().domain([dim.bottom(1)[0].date,dim.top(1)[0].date]))
//    .round(d3.time.day.utc.round)
    .elasticY(true)
//    .xUnits(d3.time.days.utc);

    function line (group,name) {
      return new dc.lineChart(graph)
       .group(group)
       .colors(colorType)
       .title (function(d) {
         if (!campaigns[name]) return "???";
       return campaigns[name].name+"\n "+d.value+" signatures\n"+(100*d.value/campaigns[name].recipient)+"%"}
    )
       .valueAccessor(d=>d.value.percent)
       .colorAccessor(function (d) { return campaigns[name].campaign_id})
       .interpolate('monotone');
    };

    var lines = [];
    mailings.forEach (m => {
       lines.push (line (groups[m.key],m.key));
    });
    graph.compose (lines);
/*
    graph.compose([
        line(group,"total")
          .renderArea(true),
    ]);
*/

/* SeriesChart isn't available on our version --- yet
  var graph=dc.SeriesChart(dom)
   .margins({top: 0, right: 20, bottom: 20, left:30})
    .height(150)
    .width(0)
    .dimension(dim)
    .chart(function(c) { return new dc.LineChart(c).curve(d3.curveCardinal); })
    .seriesAccessor(function(d) {return "Mailing: " + d.key[0];})
        .keyAccessor(function(d) {return +d.key[1];})
    .valueAccessor(function(d) {return +d.value;})
    .legend(dc.legend().x(350).y(350).itemHeight(13).gap(5).horizontal(1).legendWidth(140).itemWidth(70))
*/


   graph.yAxis().ticks(5).tickFormat(d3.format(".2s"));
   graph.xAxis().tickValues([60, 120, 360, 720, 1440]).tickFormat(x=> (x/60));

  return graph;
}


      function drawLang (dom) {
        var dim  = ndx.dimension(function(d) {return d.lang;});
        var group = dim.group().reduceSum(d => (d.total));
        var chart = dc.pieChart(dom)
          .width(0)
          .height(0)
          .innerRadius(10)
          .dimension(dim)
          .group(group);
   
          return chart;
      }
    
function drawTextSearch (dom,$,val) {

  var dim = ndx.dimension(function(d) { return d.name});

  var throttleTimer;

  $(dom).keyup (function () {

    var s = jQuery(this ).val().toLowerCase();
    $(".resetall").attr("disabled",false);
    throttle();

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

 function drawMailing (dom) {
	function remove_empty_bins(source_group) {
	    return {
		top:function () {
		    return source_group.all().filter(function(d) {
			return d.value != 0;
		    })},
		all:function () {
		    return source_group.all().filter(function(d) {
			return d.value != 0;
		    });
		}
	    };
	}


       var dim = ndx.dimension(
         function(d){
         return d.id;
       });

       var allGroup = dim.group()
         .reduceSum(d => (d.total));
       var group = remove_empty_bins(allGroup);

	  var graph  = dc.rowChart(dom)
	    .width(0)
	    .height(500)
	    .gap(0)
//	    .rowsCap(30)
	    .ordering(function(d) { return -d.value/campaigns[d.key].recipient })
	//    .ordering(function(d) { return -d.value.count })
	//    .valueAccessor( function(d) { return d.value.count })
	    .label (function (d) {return campaigns[d.key].name;})
	    .title (function (d) {
               return campaigns[d.key].name 
                    + "\n" + campaigns[d.key].subject 
                    + "\nsignatures:" + d.value 
                    + "\ndate:" + campaigns[d.key].date
                    + "\nrecipients:"+campaigns[d.key].recipient
                    + "\n%:"+(100*d.value/campaigns[d.key].recipient);})
       .colors(colorType)
       .colorAccessor(function (d) { return campaigns[d.key].campaign_id})
	    .dimension(dim)
	    .elasticX(true)
	.labelOffsetY(10)
	.fixedBarHeight(14)
	.labelOffsetX(2)
	.group(group);

    graph.xAxis().ticks(4);
    graph.margins().left = 5;
    graph.margins().top = 0;
    graph.margins().bottom = 0;
	  return graph;
	}



d3.select(window).on('resize.updatedc', function() {
  dc.events.trigger(function() {
    dc.chartRegistry.list().forEach(function(chart) {
            var container = chart.root().node().parentNode.getBoundingClientRect();
            chart.width(container.width);
            chart.rescale && chart.rescale();
      });
    dc.redrawAll(); 
  },500);
});     



    </script>
{/literal}

