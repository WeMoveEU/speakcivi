{crmTitle string="Members overview"}

<div class="container dc_contacts" id="dataviz-contacts">
<div class="row">
  <div class="col-md-12">
	    <h2 id="datacount"><strong><span class="filter-count"></span></strong> members selected from a total of <strong><span id="total-count"></span></strong> records</h2>
  </div>
</div>
<div class="row">
  <div id="date" class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading">Date</div>
      <div class="panel-body" id="contacts-by-month">
      <graph></graph>
	    <!--a class="reset" href="javascript:monthLine.filterAll();dc.redrawAll();" style="display: none;">reset</a-->
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-4">
    <div class="panel panel-default">
      <div class="panel-heading">Language</div>
      <div class="panel-body" id="language">
        <graph></graph>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="panel panel-default">
      <div class="panel-heading">Source</div>
      <div class="panel-body source">
        <graph></graph>
      </div>
    </div>
  </div>
</div>

</div>

<script>
'use strict';
var data = {crmSQL json="members" group_id=42};

var campaigns={crmAPI entity='Campaign' action='get' option_limit=100000};
var types = {crmAPI entity='Campaign' action='getoptions' sequential=0 field="campaign_type_id"};
var speakout=[];
var parent_campaign=[];
(function(guid,$){ldelim}

	{literal}

		if(!data.is_error){//Check for database error
			var numberFormat = d3.format(".2f");

			var dateFormat = d3.time.format("%Y-%m-%d");

			var langPie=null, sourceRow=null, monthLine=null;

			jQuery(function($) {
                            $(".crm-container").removeClass("crm-container");

				var totalContacts = 0;

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
                                        if (d.source.startsWith("speakout petition ")) {
                                          var speakout_id = +d.source.replace ("speakout petition ", "");
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

		                langPie 	= dc.pieChart("#language graph").innerRadius(10).radius(90);
				monthLine 	= dc.lineChart('#contacts-by-month graph');

				var ndx  = crossfilter(data.values), all = ndx.groupAll();

				var totalCount = dc.dataCount("#datacount")
			        .dimension(ndx)
			        .group(all);

			    document.getElementById("total-count").innerHTML=totalContacts;



				var lang        = ndx.dimension(function(d) {return d.language;});
				var langGroup   = lang.group().reduceSum(function(d) { return d.count; });

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

				langPie
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


var pastel2= ["#fbb4ae","#b3cde3","#ccebc5","#decbe4","#fed9a6","#ffffcc","#e5d8bd","#fddaec","#f2f2f2"];
drawSource (".source graph");

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
          })
          .ordinalColors(pastel2)
          .colorAccessor(function(d){
            if (parent_campaign[d.key])
              return parent_campaign[+d.key].campaign_type_id;
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
}

				monthLine
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
				
				dc.renderAll();

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
         #crm-container g.row text {fill: #222;};
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

