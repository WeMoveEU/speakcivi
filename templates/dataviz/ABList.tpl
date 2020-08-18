{crmTitle string="Latest AB mailings"}

<input id="input-filter" placeholder="mailing name" title="search on mailing"/>
<table>
</thead>
<tr><td>Date</td><td>Name</td></tr>
</thead>
<tbody>
</tbody>
</table>
<script>
var data = {crmSQL file="ABList"};
{literal}
var ndx = crossfilter(data.values);
var graphs = {};
draw();

function drawTextSearch (dom) {

  var dim = ndx.dimension(function(d){ 
    return d.name.toLowerCase();
  });

  var throttleTimer;

  jQuery(dom).keyup (function () {

    var s = jQuery(this ).val().toLowerCase();
    throttle();

    function throttle() {
      window.clearTimeout(throttleTimer);
      throttleTimer = window.setTimeout(function() {
        dim.filterAll();
        dim.filterFunction(function (d) { 
return d.indexOf (s) !== -1;} );
  dc.redrawAll();
      }, 250);
    }
  });

  return dim;

}

function drawTable(dom) {
  var dim=ndx.dimension(function(d) {return d.name;});
  var graph=dc.dataTable(dom)
    .dimension(dim)
    .group(function(d){ return ""; })
    .sortBy(function(d) { return d.date; })
    .order(d3.descending)
    .columns ([
       {label:'date',format:(d)=> d.status =="Draft"? "<i class='disabled'>"+d.date+"</i>" : d.date},
       {label:'name',format:(d)=>'<a href="/civicrm/dataviz/AB/'+d.id+'">'+d.name+'</a>'},
       'ab_recipient',
//       'ab_sign',
//       'ratio_ab',

    ]);
  return graph;
}

function draw() {
  graphs.table = drawTable("table");
  graphs.search = drawTextSearch('#input-filter');
  dc.renderAll();
}


{/literal}
</script>

