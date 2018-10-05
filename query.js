$(document).ready(function() {
tinysort.defaults.order = 'desc';
$.parallel($('#names').children('li'), function(listItem) {
  $.getJSON( 'query.php', {
    srsearch: listItem.innerText
  }).done(function( data ) {
    // data = json_decode(data);
    if (data != "nee"){
      for (var i = 0; i <= data.length - 1; i++) {
        var item= data[i];
    
        var innerDiv = document.createElement('li');
        innerDiv.className = 'block-2';
        innerDiv.innerHTML ="<b>"+item['sitelinks']+"</b>:<a href='https://wikidata.org/wiki/"+item['qitem']+"' target='_blank'>"+item['itemLabel']+"</a>";
        if(item['occupation']){
         innerDiv.innerHTML += ", "+ item['occupation'] ;
        }
        if(item['country']){
         innerDiv.innerHTML += " from "+ item['country'] ;
        }
        if (item['image']){
          innerDiv.setAttribute('style', "background:url('"+item['image']+"') no-repeat left top;");
        }
        if (item['isSportsPerson'] == 'true'){
          innerDiv.className += ' isSportsPerson';
          if($('#sportsPersonCheck')[0].checked == false){
            innerDiv.style.display = "none";
          }
        }
        innerDiv.setAttribute('weight', item['sitelinks']);
        listItem.children[0].append(innerDiv);
        tinysort(listItem.children[0].children, {attr:'weight'});  
        if (item['sitelinks'] > listItem.getAttribute('weight') && ((item['isSportsPerson'] == 'true' &&  $('#sportsPersonCheck')[0].checked == true)  || item['isSportsPerson'] == 'false') ){
          listItem.setAttribute('weight', item['sitelinks']);
          tinysort('ul#names>li', {attr:'weight'});  
        }
      }
    }
  });

  return $.wait(500); 
  }).progress(function(completed, total, percentage) {
    $( "#progressbar" ).progressbar({ value: Math.round( percentage) });
  }).done(function() {
    console.log('done!');
  }); 


});

$(function(){
  $('#sportsPersonCheck').on('click', function () {
    if (!this.checked){ //hide sportspeople, reasses weight of entry
      $('.isSportsPerson').each(function(){
        if (this.getAttribute('weight') == this.parentElement.parentElement.getAttribute('weight')){
          var weights = new Array();
          for (var i = this.parentElement.children.length - 1; i >= 0; i--) {
            if(this.parentElement.children[i].getAttribute('class').indexOf("isSportsPerson") == '-1'){
              weights.push(parseInt(this.parentElement.children[i].getAttribute('weight')));
            }
          }
          if(weights.length == 0){
            this.parentElement.parentElement.setAttribute('weight', '-1');
          }
          else{
            weights.sort().reverse();
            this.parentElement.parentElement.setAttribute('weight', weights[0]);
          }
        }
      });
    }
    else{
      $('.isSportsPerson').each(function(){
        if (parseInt(this.getAttribute('weight')) > parseInt(this.parentElement.parentElement.getAttribute('weight'))){
          this.parentElement.parentElement.setAttribute('weight', this.getAttribute('weight'));
        }
      });
    }
    $('.isSportsPerson').toggle();
    tinysort('ul#names>li', {attr:'weight'});  
  });     
});  