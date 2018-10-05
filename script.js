//makes it possible to edit the list of possible matches
$(document).ready(function() {
    var words = document.querySelectorAll('.word');
    for (var i = 0; i < words.length; i++) {
	    words[i].addEventListener('click', function(event) {
	    	var childNodes = this.parentElement.childNodes;
	    	var name = '';
	    	var hiddenInput= '';
	    	for (var j = 0; j < childNodes.length; j++) {
	    		if (childNodes[j] != this && childNodes[j].className == "word" ){
	    			name += childNodes[j].innerText+" ";
	    		}
	    		else if (childNodes[j].nodeName == "INPUT"){
	    			hiddenInput = childNodes[j];
	    		}
	    	}
	    	name = name.trim();
	    	hiddenInput.value = name ;
	    	if (name == ''){
	    		this.parentElement.remove();
	    	}
	    	else{
	    		// remove duplicates
	    		var nameInputs = document.querySelectorAll('.hiddenNames');
		    	for (var j = 0; j < nameInputs.length; j++) {
		    		if(nameInputs[j] != hiddenInput && nameInputs[j].value == name ){
		    			nameInputs[j].parentElement.remove();
		    		}
		    	}
		        this.remove();
	    	}

	    });
	}
	var removeButtons = document.querySelectorAll('.remove');
    for (var i = 0; i < removeButtons.length; i++) {
	    removeButtons[i].addEventListener('click', function(event) {
	    	this.parentElement.remove();
	    });
	}

	$('textarea.retracted').focus(function () {
    	$(this).animate({ height: "200px" }, 500); 
	});
	$('textarea.retracted').focusout(function () {
    	$(this).animate({ height: "40px" }, 500); 
	});

});