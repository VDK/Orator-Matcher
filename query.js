$(document).ready(function() {
tinysort.defaults.order = 'desc';
$.parallel($('#names').children('li'), function(listItem) {
  $.getJSON( 'query.php', {
    srsearch: listItem.innerText
  }).done(function( data ) {
    var srsearch = listItem.innerText;
    if (data != "nee"){
      var categories = '';
      for (var i = 0; i <= data.length - 1; i++) {
        var item= data[i];
        var responseList = $(listItem).find('.response')[0];
        var response = document.createElement('li');
        response.className = 'block-2';
        response.innerHTML ="<b>"+item['sitelinks']+"</b>:<a href='https://wikidata.org/wiki/"+item['qitem']+"' target='_blank'>"+item['itemLabel']+"</a>";
        if(item['dateOfBirth']){
          var d = new Date(item['dateOfBirth']);
          response.innerHTML += "("+ d.getFullYear() +")";
        }
        if(item['occupation']){
         response.innerHTML += ", "+ item['occupation'] ;
        }
        if(item['country']){
         response.innerHTML += " from "+ item['country'] ;
        }
        if (item['image']){
          response.setAttribute('style', "background:url('"+item['image']+"') no-repeat left top;");
          listItem.setAttribute('image', true);
        }
        if (item['isSportsPerson'] == 'true'){
          response.className += ' isSportsPerson';
          if($('#sportsPersonCheck')[0].checked == false){
            response.style.display = "none";
          }
        }
        if (item['categories']){
           categories += " "+item['categories'];

        }
        response.setAttribute('weight', item['sitelinks']);
        responseList.append(response);
        tinysort(responseList.children, {attr:'weight'});  
        if (item['sitelinks'] > listItem.getAttribute('weight') && ((item['isSportsPerson'] == 'true' &&  $('#sportsPersonCheck')[0].checked == true)  || item['isSportsPerson'] == 'false') ){
          listItem.setAttribute('weight', item['sitelinks']);
          tinysort('ul#names>li', {attr:'weight'});  
        }
      }
      //link to search Commons
      var a = document.createElement('a');
      var innserText = document.createTextNode("\""+srsearch.trim()+"\"");
      a.appendChild(innserText);
      a.title  = "commons search for "+srsearch;
      a.href   = "https://commons.wikimedia.org/w/index.php?search="+encodeURI("\""+srsearch+"\" "+categories);
      a.target = '_blank';

      var link = document.createElement('span');
      link.setAttribute('class', 'commonslink');
      var outerText = document.createTextNode("Search for ");
      link.appendChild(outerText);
      link.appendChild(a);
      outerText = document.createTextNode(" on Commons");
      if (categories != ''){
        outerText.textContent += ", minus categorized images";
      }
      link.appendChild(outerText);
      responseList.before(link);
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
          var count = 0;
          for (var i = this.parentElement.children.length - 1; i >= 0; i--) {
            if(this.parentElement.children[i].getAttribute('class').indexOf("isSportsPerson") == '-1'){
              weights.push(parseInt(this.parentElement.children[i].getAttribute('weight')));
            }
            else{
              count++;
            }
          }
          if(weights.length == 0){
            this.parentElement.parentElement.setAttribute('weight', '-1');
          }
          else{
            weights.sort().reverse();
            this.parentElement.parentElement.setAttribute('weight', weights[0]);
          }
          if (this.parentElement.children.length == count){
            $(this).parent().parent().find('.commonslink')[0].style.display = "none";
          }
        }

      });
    }
    else{
      $('.isSportsPerson').each(function(){
        if (parseInt(this.getAttribute('weight')) > parseInt(this.parentElement.parentElement.getAttribute('weight'))){
          this.parentElement.parentElement.setAttribute('weight', this.getAttribute('weight'));
        }
        $(this).parent().parent().find('.commonslink')[0].style.display = "block";
      });
    }
    $('.isSportsPerson').toggle();
    tinysort('ul#names>li', {attr:'weight'});  
  });     
});  