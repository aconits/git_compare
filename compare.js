document.addEventListener('DOMContentLoaded', function() {
	request(addSelectDepotToHtml, 'action=getTDepot');
});

function request(callback, args) 
{
	var xhr = new XMLHttpRequest();
	
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) 
		{
	        callback(xhr.responseText);
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
	var select = document.createElement('select');
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

	fragment.appendChild(select);
	target_div.appendChild(fragment);
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
	request(showDiff, args);
}

function execDiff()
{
	var args = 'action=execDiff&depot='+document.getElementById('depot').value;
	args += '&branch_a='+document.getElementById('branch_a').value;
	args += '&branch_b='+document.getElementById('branch_b').value;
	request(showDiff, args);
}

function showDiff(THtml)
{
	THtml = JSON.parse(THtml);

	var length = THtml.length;
	var content = document.getElementById('content');
	content.innerHTML = '';
	
	if (length > 0)
	{
		processLargeArray(content, THtml, length);
	}
}

/*
 * Source : http://stackoverflow.com/questions/10344498/best-way-to-iterate-over-an-array-without-blocking-the-ui/10344560#10344560
 */
function processLargeArray(content, THtml, length)
{
    document.getElementById('loading').style.display = 'block';
    
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
        if (index < length) {
            // set Timeout for async iteration
            setTimeout(doChunk, 1);
        }
        else
        {
        	document.getElementById('loading').style.display = 'none';
        }
    }    
    doChunk();
}