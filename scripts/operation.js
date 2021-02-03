function operation(name){
    var reqArray = {
        "sessionId": sessionId,
        "softwareId": softwareId,
        "operation": name
    }
    var reqJson = JSON.stringify(reqArray);
    var xhr = new XMLHttpRequest;
    var sendoperationURL = basePath + "/ctrl/sendoperation";
    xhr.open('post', sendoperationURL, true);    //(1)
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(reqJson);
}
