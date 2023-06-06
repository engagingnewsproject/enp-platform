
jQuery(function ($) {
    /* 
    localized variables passed through params object:
    params.clearLogRestUrl
    params.clearLogButtonId  
    params.downloadLogRestUrl
    params.downloadLogButtonId
    
    */

 $("#"+params.clearLogButtonId).on("click", () => {

   $.post(
      params.clearLogRestUrl,
     { },
     function (json) {
       console.log(json);
     }
   );
 });

 $("#"+params.downloadLogButtonId).on("click", () => {
   $.post(
     params.downloadLogRestUrl,
     {},
     function (json) {
       let download = json.data;

       var blob = new Blob([download], { type: "json" });

       var a = document.createElement("a");
       a.download = "nf-file-uploads-debug-log.json";
       a.href = URL.createObjectURL(blob);
       a.dataset.downloadurl = ["json", a.download, a.href].join(":");
       a.style.display = "none";
       document.body.appendChild(a);
       a.click();
       document.body.removeChild(a);
       setTimeout(function () {
         URL.revokeObjectURL(a.href);
       }, 15000);
     }
   );
 });

return;

});
