var img = document.getElementsByTagName("title")[0].textContent.toLowerCase().replace(/\s+/g, "-");

function UnityProgress(gameInstance, progress) {
  if (!gameInstance.Module)
    return;
  if (!gameInstance.logo) {
	gameInstance.bg = document.createElement("div");
	gameInstance.bg.className = "background";
	gameInstance.container.appendChild(gameInstance.bg);
	
	gameInstance.logo = document.createElement("div");
	gameInstance.logo.className = "logo";
	gameInstance.container.appendChild(gameInstance.logo);
  }
  if (!gameInstance.progress) {    
	gameInstance.progress = document.createElement("div");
	gameInstance.progress.className = "progress";
	gameInstance.container.appendChild(gameInstance.progress);
	
	gameInstance.progress.thumb = document.createElement("div");
	gameInstance.progress.thumb.className = "thumb";
	gameInstance.progress.thumb.style.backgroundImage = "url(//cdn.g55.co/"+img+".png)";
	gameInstance.progress.appendChild(gameInstance.progress.thumb);
	
	gameInstance.progress.gameName = document.createElement("p");
	gameInstance.progress.gameName.className = "name";
	gameInstance.progress.gameName.innerHTML = document.getElementsByTagName("title")[0].textContent;
	gameInstance.progress.appendChild(gameInstance.progress.gameName);
	
	gameInstance.progress.percentage = document.createElement("p");
	gameInstance.progress.percentage.className = "percentage";
	gameInstance.progress.appendChild(gameInstance.progress.percentage);
	
	gameInstance.progress.progressFrame = document.createElement("div");
	gameInstance.progress.progressFrame.className = "progressFrame";
	gameInstance.progress.appendChild(gameInstance.progress.progressFrame);
	
	gameInstance.progress.empty = document.createElement("div");
	gameInstance.progress.empty.className = "empty";
	gameInstance.progress.progressFrame.appendChild(gameInstance.progress.empty);
	
	gameInstance.progress.full = document.createElement("div");
	gameInstance.progress.full.className = "full";
	gameInstance.progress.progressFrame.appendChild(gameInstance.progress.full);
  }
  gameInstance.progress.full.style.width = (100 * progress) + "%";
  gameInstance.progress.empty.style.width = (100 * (1 - progress)) + "%";
  
  var percentage = gameInstance.progress.full.style.width = (100 * progress) + "%";
  var per = percentage.substring(0, percentage.indexOf("."));
  gameInstance.progress.percentage.innerHTML = "Loading: "+per+"%";
  
  if (progress == 1)
	gameInstance.logo.style.display = gameInstance.progress.style.display = gameInstance.bg.style.display = "none";
}