document.addEventListener('DOMContentLoaded', function() {
	request(addSelectDepotToHtml, 'action=getTDepot');
});

function request(callback, args, show_progress) 
{
	if (show_progress == 1)
	{
		var progress_box = document.getElementById('progress');
	    var progress_txt = document.getElementById('progress_txt');
	    var progress_element = document.getElementById('progress_element');
	    progress_element.value = 0;
	    progress_txt.innerHTML = '0%';
	    progress_box.className = 'load';
	}
    
	var error = document.getElementById('error_msg');
	var xhr = new XMLHttpRequest();
	
	xhr.onreadystatechange = function() {
		error.className = '';
		
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) 
		{
			if (show_progress == 1) callback(xhr.responseText, progress_box, progress_txt, progress_element);
			else callback(xhr.responseText);
	    }
	    else if (xhr.readyState == 4 && xhr.status == 500)
	    {
	    	error.innerHTML = xhr.responseText;
	    	error.className = 'load';
	    }
	};
	
	xhr.open('POST', 'compare.php', true);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.send(args);
}

function addSelectDepotToHtml(TDepot)
{
	TDepot = JSON.parse(TDepot);
	
	var i;
	var target_div = document.getElementById('content_depot');
	/*var select = document.createElement('select');
	select.name = 'depot';
	select.id = 'depot';
	select.onchange = function() { request(fillSelectBranch, 'action=getTBranch&depot='+this.value); };
	var option = document.createElement('option');
	select.appendChild(option);
	var fragment = document.createDocumentFragment();
	
	for (i in TDepot)
	{
		var option = document.createElement('option');
		option.value = TDepot[i];
		option.text = i;
		
		select.appendChild(option);
	}

	fragment.appendChild(select);*/
	
	var input = document.createElement('input');
	//input.onchange = function() { request(fillSelectBranch, 'action=getTBranch&depot='+document.getElementById('depot-'+this.value.replace(' ', '_')).dataset.path); };
	input.addEventListener('input', function () {
		var option = document.getElementById('depot-'+this.value.replace(' ', '_'));
		if (option != null) request(fillSelectBranch, 'action=getTBranch&depot='+option.dataset.path);
	});
	input.id = 'depot';
	input.name = 'depot';
	input.setAttribute('list', 'list_depot');
	input.type = 'text';
	
	var datalist = document.createElement('datalist');
	datalist.id='list_depot';
	
	var option = document.createElement('option');
	datalist.appendChild(option);
	
	var fragment = document.createDocumentFragment();
	
	for (i in TDepot)
	{
		var option = document.createElement('option');
		option.value = i;
		option.setAttribute('data-path', TDepot[i]); 
		option.text = i;
		option.id = 'depot-'+i.replace(' ', '_');
		
		datalist.appendChild(option);
	}

	fragment.appendChild(input);
	fragment.appendChild(datalist);
	
	target_div.appendChild(fragment);
	
	var hash = window.location.hash;
	hash = hash.substr(1);
	var TArg = hash.split("&");
	for (var i in TArg)
	{
		var s = TArg[i].split("=");
		if (s[0] == 'depot' && s[1].length > 0) 
		{
			var evt = document.createEvent("HTMLEvents");
		    evt.initEvent("input", false, true);
		    
			input.value = s[1];
		    input.dispatchEvent(evt);
		}
		
	}
	
	setTimeout(function() {
		for (var i in TArg)
		{
			var s = TArg[i].split("=");
			if (s[0] == 'branch_a' || s[0] == 'branch_b' && s[1].length > 0)
			{
				updateDefaultSelected(s[0], s[1]);
			}
		}
		
		document.getElementById('execDiff').onclick();
	}, 150);
	
}

function updateDefaultSelected(target, value_to_compare)
{
	for (var y=0; y<document.getElementById(target).options.length; y++)
	{
		if (document.getElementById(target).options[y].value == value_to_compare)
		{
			document.getElementById(target).options[y].defaultSelected = true;
			break;
		}
	}
}

function fillSelectBranch(TBranch)
{
	TBranch = JSON.parse(TBranch);
	
	var branch_a = document.getElementById('branch_a');
	branch_a.innerHTML = '';
	var branch_b = document.getElementById('branch_b');
	branch_b.innerHTML = '';
	var fragment = document.createDocumentFragment();
	
	for (var i=0; i<TBranch.length; i++)
	{
		var option = document.createElement('option');
		option.value = TBranch[i].branch_name;
		option.text = TBranch[i].branch_name;
		
		if (TBranch[i].default == 1) option.selected = 1;
		
		fragment.appendChild(option);
	}
	
	var clone = fragment.cloneNode(true);
	
	branch_a.appendChild(fragment);
	branch_b.appendChild(clone);
}

function test()
{
	var args = 'action=test&depot='+document.getElementById('depot').value;
	args += '&branch_a='+document.getElementById('branch_a').value;
	args += '&branch_b='+document.getElementById('branch_b').value;
	request(showDiff, args, 1);
}

function execDiff()
{
	var depot = document.getElementById('depot').value;
	var depot_path = '';
	var option = document.getElementById('depot-'+depot.replace(' ', '_'));
	if (option != null) depot_path = option.dataset.path;
	
	var args = 'action=execDiff&depot='+depot;
	args += '&depot_path='+depot_path;
	args += '&branch_a='+document.getElementById('branch_a').value;
	args += '&branch_b='+document.getElementById('branch_b').value;
	
	if (depot.length > 0)
	{
		request(showDiff, args, 1);
		window.location.hash = args;	
	}
}

function showDiff(THtml, progress_box, progress_txt, progress_element)
{
	THtml = JSON.parse(THtml);

	var length = THtml.length;
	var content = document.getElementById('content');
	content.innerHTML = '';
	
	if (length > 0)
	{
		processLargeArray(content, THtml, length, progress_box, progress_txt, progress_element);
	}
}

/*
 * Source : http://stackoverflow.com/questions/10344498/best-way-to-iterate-over-an-array-without-blocking-the-ui/10344560#10344560
 */
function processLargeArray(content, THtml, length, progress_box, progress_txt, progress_element)
{
    var chunk = 10;
    var index = 0;
    function doChunk() 
    {
        var cnt = chunk;
        while (cnt-- && index < length) 
        {
        	content.insertAdjacentHTML('beforeend', THtml[index]);
        	++index;
        }
        
        var newVal = Math.floor((index*100)/length);
        progress_element.value = newVal;
        progress_txt.innerHTML = newVal+'%';
        
        if (index < length) {
            // set Timeout for async iteration
            setTimeout(doChunk, 1);
        }
        else
        {
        	setTimeout(function() { progress_box.className = ''; }, 800);
        }
    }    
    doChunk();
}