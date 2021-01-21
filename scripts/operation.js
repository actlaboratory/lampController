function operation(name){
    var reqArray = {
        "sessionId": sessionId,
        "softwareId": softwareId,
        "operation": name
    }
    var reqJson = JSON.stringify(reqArray);
    var xhr = new XMLHttpRequest;
    xhr.open('post', `${basePath}/ctrl/sendoperation`, true);    //(1)
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(reqJson);
}
