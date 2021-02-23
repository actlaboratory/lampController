function operation(name){
    if (softwareId == null){
        alert("操作対象のLAMPが設定されていません。");
        return;
    }
    if (softwareStatus.innerText == "未接続"){
        alert("現在、LAMPと接続されていません。ソフトウェアの起動と、設定を確認してください。\nまたは、操作対象のLAMPを変更してください。");
        return;
    }
    
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
