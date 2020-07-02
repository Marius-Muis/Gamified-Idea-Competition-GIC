function openNav() {
	document.getElementById("myNav").style.width = "100%";
	if (numparts == 0 && document.getElementById('overlay_message') == null) {
		var newLink = document.createElement('a');
		newLink.setAttribute('id', 'overlay_message');
		document.getElementById('Participants_Submenu').appendChild(newLink);
		newLink.innerHTML = 'No accounts active';
	}
}

function closeNav() {
	document.getElementById("myNav").style.width = "0%";
}