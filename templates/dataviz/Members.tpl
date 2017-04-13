{crmTitle string="Members overview"}

<div class="container dc_contacts" id="dataviz-contacts">
<div class="row">
  <div id="date" class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">Date
<div class="btn-group" id="btn-date"></div>

</div>
      <div class="panel-body" id="contacts-by-month">
      <graph></graph>
	    <!--a class="reset" href="javascript:monthLine.filterAll();dc.redrawAll();" style="display: none;">reset</a-->
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
  </div>
</div>
<div class="row">
  <div class="col-md-3">
      <ul class="list-group">
        <li class="list-group-item"><select id="group" class="crm-select2"></select>{id}</li>
        <li class="list-group-item"><span class="summary_total"></span> total</li>
        <li class="list-group-item list-group-item-success"><span class="badge total_percent"></span><span class="total"></span> members</li>
      </ul>

    <div class="panel panel-default">
      <div class="panel-heading">Language</div>
      <div class="panel-body" id="language">
        <graph></graph>
      </div>
    </div>
    <div class="panel panel-default">
      <div class="panel-heading">Type</div>
      <div class="panel-body" id="type">
        <graph></graph>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="panel panel-default">
      <div class="panel-heading">
<input id="input-filter" placeholder="Source" title="search on source"/>
</div>
      <div class="panel-body source">
        <graph></graph>
      </div>
    </div>
  </div>
</div>

</div>

<script>
'use strict';
{if isset($id)}
{else}
{assign var="id" value="42"}
{/if}
var group_id={$id};


var data = {crmSQL json="members" group_id=$id};

var campaigns={crmAPI entity='Campaign' action='get' option_limit=100000};
var types = {crmAPI entity='Campaign' action='getoptions' sequential=0 field="campaign_type_id"};

{php}
  $this->assign("param_name", array('NOT LIKE' => "Reminder%"));
{/php}
var groups= {crmAPI entity='Group' action='get' return="title" is_hidden=0 option_limit=10000 sequential=0  option_sort="title" name=$param_name};
var speakout=[];
var parent_campaign=[];

{literal}
var graphs= {};
    var summary= {};
 
(function(guid,$){

		if(!data.is_error){//Check for database error
			var numberFormat = d3.format(".2f");

			var dateFormat = d3.time.format("%Y-%m-%d");
     var formatNumber = function (d){ return d3.format(",")(d).replace(",","'")};
      var formatPercent =d3.format(".2%");
 
 
				var totalContacts = 0;

                                types = types.values;
                                groups = groups.values;
                                types[""]="Other";
                                campaigns.values.forEach(function(d){
                                  var id = d.parent_id || d.id;
                                  if (d.parent_id == d.id || !d.parent_id) {
                                    parent_campaign[id]=d;
                                    parent_campaign[id]['name']=parent_campaign[id]['name'].replace("-EN","").replace("_EN","").replace("-en","");
                                  }
                                  speakout[+d.external_identifier] = id;
                                });
				data.values.forEach(function(d){ 
					totalContacts+=d.count;
					d.dd = dateFormat.parse(d.created_date);
                                        if (d.source.startsWith("speakout ")) {
                                          var speakout_id = +d.source.replace ("speakout petition ", "").replace ("speakout share ", "");
                                          if (speakout[speakout_id]) {
                                           d.source=parent_campaign[speakout[speakout_id]].name;
                                            d.campaign_id=speakout[speakout_id];
                                          } else {
                                            console.log ("missing campaign for "+speakout_id);
                                          }
                                        } else {
					  //d.source='None';
                                        }
				});

				//var min = d3.time.day.offset(d3.min(data.values, function(d) { return d.dd;} ),-2);
				var min = dateFormat.parse("2015-11-01");
				var max = d3.time.day.offset(d3.max(data.values, function(d) { return d.dd;} ), 2);


				var ndx  = crossfilter(data.values), all = ndx.groupAll();

				var totalCount = dc.dataCount("#datacount")
			        .dimension(ndx)
			        .group(all);

			jQuery(function($) {
                            $(".crm-container").removeClass("crm-container");
			    //document.getElementById("total-count").innerHTML=totalContacts;


var html="";
$.each(groups,function(i,d){
  html += "<option value='"+d.id+"'>"+d.title+"</option>";
});

$("#group").html(html);
$("#group option[value='"+group_id+"']").attr("selected", "selected");
CRM.$("#group").select2()
.on("change", function (e) {
  window.location.href = "/civicrm/dataviz/members/"+e.val;
});
;
var pastel2= ["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd","#fddaec","#f2f2f2"];


graphs.date = drawDate("#date graph");
graphs.lang = drawLang("#language graph");
graphs.type = drawType("#type graph");
graphs.btn_date = drawDateButton("#date .btn-group",graphs.date);
graphs.source= drawSource (".source graph");
graphs.search = drawTextSearch('#input-filter',jQuery);
drawNumbers(graphs);  
summary.total = graphs.total.data();
$(".summary_total").text(formatNumber(summary.total));
 
dc.renderAll();


function drawNumbers (graphs){

  
  var group = ndx.groupAll().reduce(
    function (p, v) {
  p.total += +v.count;
  return p;
    },
    function (p, v) {
  p.total -= +v.count;
  return p;
    },
    function () { return {
       total:0,
      };
    });
  
  graphs.total=dc.numberDisplay(".total") 
      .valueAccessor(function(d){ return d.total})
    .formatNumber(formatNumber)
    .group(group);
  graphs.total_percent=dc.numberDisplay(".total_percent") 
      .valueAccessor(function(d){ 
       if (d.total == summary.total){
         $(".summary_total").parent().slideUp();
         return 1;
       }
       $(".summary_total").parent().slideDown();

        return d.total/summary.total
    })
    .formatNumber(formatPercent)
    .group(group);
}

function drawLang (dom) {
				var lang        = ndx.dimension(function(d) {return d.language;});
				var langGroup   = lang.group().reduceSum(function(d) { return d.count; });


		                var langPie 	= dc.pieChart(dom).innerRadius(10).radius(90)
					.width(250)
					.height(200)
					.dimension(lang)
					.colors(d3.scale.category10())
					.group(langGroup)
					.label(function(d){
                                           return d.key.substring(0,2);
                                        })
                                        .title (function (d) {
					  if (langPie.hasFilter() && !langPie.hasFilter(d.key))
			                    return d.key + "(0%)";
					   return d.key+': '+d.value+" (" + Math.floor(d.value / all.reduceSum(function(d) {return d.count;}).value() * 100) + "%)";
					});
  return langPie;
}

function drawType (dom) {
  var dim        = ndx.dimension(function(d) {
    if (!d.campaign_id || !parent_campaign[d.campaign_id])
      return "";
    return parent_campaign[d.campaign_id].campaign_type_id;
  });

  var group   = dim.group().reduceSum(function(d) { return d.count; });


  var graph	= dc.pieChart(dom).innerRadius(10).radius(90)
					.width(250)
					.height(200)
					.dimension(dim)
          .ordinalColors(pastel2)
          .colorAccessor(function(d){
            return +d.key || 1;

          })
					.group(group)
					.label(function(d){
                                           return types[d.key];
                                        })
                                        .title (function (d) {
					  if (graph.hasFilter() && !graph.hasFilter(d.key))
			                    return types[d.key] + "(0%)";
					   return types[d.key]+': '+d.value+" (" + Math.floor(d.value / all.reduceSum(function(d) {return d.count;}).value() * 100) + "%)";
					});
  return graph;
}
function drawTextSearch (dom,$,val) {

  var dim = ndx.dimension(function(d){ 

              var k=d.campaign_id;
              if (parent_campaign[k])
                return parent_campaign[k].custom_11;
              return d.source.toLowerCase();
      });

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

function drawDateButton(dom, graph) {
var data = [
    { key: "today", label: "Today" },
    { key: "yesterday", label: "Yesterday" },
    { key: "week", label: "This week" },
    { key: "7", label: "Last 7 days" },
    { key: "month", label: "This month" },
    { key: "30", label: "Last 30 days" },
    { key: "90", label: "Last 90 days" },
    { key: "Infinity", label: "All" }
];
  d3.select(dom)
    .selectAll("button")
    .data(data)
    .enter()
    .append ("button")
    .text(function (d) {return d.label})
    .classed("btn",true)
    .classed("btn-default",true)
    .on("click", function () {
       var btn=d3.select(this);
       d3.selectAll(dom +" .active").classed("active", false);
       btn.classed("active",true);
      var s = new Date(), e = new Date();
      switch (btn.data()[0].key) {
        case "today":
    s = d3.time.day.utc(e);
    break;
        case "yesterday":
    e = d3.time.day.utc(s);
    s = d3.time.day.offset(e, -1);
    break;
        case "week":
    s = d3.time.monday.utc(e);
    break;
        case "month":
    s = d3.time.month.utc(e);
    break;
        default:
    s = d3.time.day.offset(e, - + btn.data()[0].key);
      }

      graph.filterAll(); //reset filter
      graph.filter(dc.filters.RangedFilter(s,e));
      graph.redrawGroup();
    });


}

function drawSource (dom) {
  var dim = ndx.dimension(function(d){ return d.campaign_id || d.source;});
  var group = dim.group().reduceSum(function(d){return d.count;});
  var graph = dc.rowChart(dom)
	.width(300)
	.height(400)
	.margins({top: 20, left: 10, right: 10, bottom: 20})
	.dimension(dim)
	.cap(15)
          .gap(1)
          .title (function(d) {
            if (parent_campaign[d.key]) {
              return parent_campaign[+d.key].description + ": "+d.value;
            }
            return d.key + ": " +d.value; 
          })
          .ordinalColors(pastel2)
          .colorAccessor(function(d){
            if (parent_campaign[d.key]) {
              return parent_campaign[d.key].campaign_type_id;
            }
            return 1;
             //d.source=parent_campaign[speakout[speakout_id]].name;

          })
          .ordering (function(d) {return d.count;})
	  .group(group)
	  .label(function(d){
	      if (graph.hasFilter() && !graph.hasFilter(d.key))
	        return d.key + "(0%)";
              var k=d.key;
              if (parent_campaign[d.key])
                k= parent_campaign[+d.key].custom_11;
	      return k+"(" + Math.floor(d.value / all.reduceSum(function(d) {return d.count;}).value() * 100) + "%)";
	    })
	    .elasticX(true);

    graph.xAxis().ticks(4);

  return graph;
}

function drawDate() {
				var creationMonth = ndx.dimension(function(d) { return d.dd; });
				var creationMonthGroup = creationMonth.group().reduceSum(function(d) { return d.count; });

				var _group   = creationMonth.group().reduceSum(function(d) {return d.count;});
				var group = {
					all:function () {
						var cumulate = 0;
						var g = [];
						_group.all().forEach(function(d,i) {
							cumulate += d.value;
							g.push({key:d.key,value:cumulate})
						});
						return g;
					}
				};
var graph= dc.lineChart('#contacts-by-month graph')
					.width(800)
					.height(200)
           .margins({top: 10, right: 50, bottom: 30, left: 50})
					.dimension(creationMonth)
					.group(group)
					.x(d3.time.scale().domain([min, max]))
					.round(d3.time.day.round)
					.elasticY(true)
                                        .renderArea(true)
					.xUnits(d3.time.days);
			
    graph.yAxis().ticks(4);
  return graph;
}	

			});
		}
		else{
			$('.dc_contacts').html('<div style="color:red; font-size:18px;">There is a database error. Please Contact the administrator as soon as possible.</div>');
		}
	{/literal}
{rdelim})("#dataviz-contacts ",CRM.$);
</script>
{literal}
        <style>
      h1.page-header,.breadcrumb,header p.lead {display:none;}
         #crm-container g.row text, #type .pie-slice {fill: #222;};
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
        </style>

{/literal}

