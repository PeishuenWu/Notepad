<?php
function base64_url_encode($input){
	return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

function base64_url_decode($input){
	return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
}

//$code_page to utf8
function getUTF8String($path, $code_page){
	if($code_page == 'utf-8'){
		return $path;
	}
	return mb_convert_encoding ($path, "utf-8", $code_page);
}

//file path
$path ='';

//title 
$show_path ='';

//file context
$value ='';

//file path code page
$path_code_page = "BIG-5";

if(!empty($_POST['q']) &&  isset($_POST['v'])){
	//save file
	$file_path = base64_url_decode($_POST['q']);
	$v = base64_decode($_POST['v']);
	file_put_contents($file_path, $v);
	echo '1';
	exit;
}

if(!empty($_GET['q'])){
    //open file
	$file_path = base64_url_decode($_GET['q']);
	$show_path = getUTF8String($file_path, $path_code_page);
	if(file_exists ($file_path)){
		$path = $_GET['q'];
		$value= base64_encode(file_get_contents($file_path));
	}
}
?>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title><?php echo $show_path; ?></title>
  <style type="text/css" media="screen">
    body {
        overflow: hidden;
    } 

	textarea,pre {
	    -moz-tab-size : 4;
	      -o-tab-size : 4;
	         tab-size : 4;
	}
	
	#title_bar{
	    width:100%;
	    height:4em;
	}

	.qrz_btn {
        width: 8em;
		height: 4em;
    }
    .hidden{
	    display:none;
	}

	#edit_div{
		position: relative;
		width: calc(100%);
		height: calc(100% - 60px);
	}
	#edit_line{
		width: 60px;
		height: calc(100%);
		background-color:#111111;
		font-size:1.8em;
		line-height:1em;
		color:#eeeeee;
		overflow:hidden;
		resize : none;
		float:left;
		text-align:right;
		padding: 3px;
	}	            	
    #editor{
		left:60px;
		width: calc(100% - 60px);
		height: calc(100%);
		background-color:#111111;
		font-size:1.8em;
		line-height:1em;
		color:#eeeeee;
		resize : none;
		float:left;
		padding: 3px;
    }
	
  </style>
</head>
	<body>
		<div id="title_bar">
			<div style="float:left;padding:2px;">
				<label style="font-size:24px;" id="filepath"><?php echo $show_path; ?></label><label id="unsaved_tag" class="hidden">*</label>
			</div>
			<div style="float:right">
				<input type="button" class="qrz_btn" value="Reload" onclick="location.reload();"></input>
				<input type="button" class="qrz_btn" value="Save"  onclick="Save();"></input>
			</div>
		</div>
		<div id="edit_div">
			<textarea id="edit_line" id="li" disabled></textarea>
			<textarea  id="editor" class='edit' wrap="off" spellcheck='false' ></textarea>
		</div>
		<script>
			function editor_keydown() {
				e = event;
				
				//if input keyup,keydown,pageup,pagedown scroll to left
				if(e.keyCode === 33 || e.keyCode === 34|| e.keyCode === 38 || e.keyCode === 40) { 
					this.scrollLeft=0;
				}
					
				//process input tab
				if(e.keyCode === 9) { 
					var start = this.selectionStart;
					var ori_start = this.selectionStart;
					var end = this.selectionEnd;

					var value = this.value;

					//if multi select, support tab and shifht+tab
					if(start > 0 && start != end){
						var add_n=0;
						var pre_n=0;
						pre_tab_pos = value.substring(0, start).lastIndexOf("\n");
						if(start > pre_tab_pos ){
							start = pre_tab_pos;
							pre_n=1;
						}

						var select_value = value.substring(start, end);
						var replace = "\t";
						var rindex = select_value.indexOf("\n");
						var substart = start;
						if (rindex >= 0) {
							if(e.shiftKey){
								add_n = (select_value.match(/\n\t/g) || []).length;
								replace = select_value.replace(/\n\t/g,"\n");				
							}else{
								add_n = (select_value.match(/\n/g) || []).length;
								replace = select_value.replace(/\n/g,"\n\t");				
							}
						}
						if(add_n == 0){
							pre_n = 0;
						}
						this.value = (value.substring(0, start) + replace + value.substring(end));
						this.selectionStart = ori_start + pre_n;
						this.selectionEnd = end + add_n;
					}else{
						this.value = value.substring(0, start) + "\t" + value.substring(end);
						this.selectionStart = this.selectionEnd = start + 1;
					}	
			
					e.preventDefault();
				}
			}
			
			function editor_input() {
				ObjectFromID("unsaved_tag").classList.remove("hidden");	
			}
			
			function window_keydown() {
				if(event.ctrlKey && (event.which == 83)) {
					Save();
					event.preventDefault();
				}
			}
			
			function base64_encode( s ) {
				return btoa(unescape(encodeURIComponent(s)))
			}
			
			function base64_decode( s ) {
				return decodeURIComponent(escape(atob(s)))
			}
			
			function AJAX(url, method, data, on_success, on_error){
				var request = new XMLHttpRequest();
				request.open(method, url, true);
				request.onload = function() {
					if (this.status >= 200 && this.status < 400) {
						on_success(this.response);
					}else {  
						on_error(this);
					}
				};
				request.onerror = on_error;
				request.send(data);
			}

			function ObjectFromID(objid){
				return document.getElementById(objid);
			}
			
			function editor_keyup(){
				var obj = ObjectFromID("editor");
				var str = obj.value; 
				str=str.replace(/\r/gi,"");
				str=str.split("\n");
				n=str.length;
				setLineNumber(n);
			}
			
			function setLineNumber(n){
				var num="";
				var lineobj = ObjectFromID("edit_line");
				for(var i=1;i<=n;i++){
				   if(document.all){
					num+=i+"\r\n";
				   }else{
					num+=i+"\n";
				   }
				}
				lineobj.value=num;
				num="";
			}
			
			function autoScroll(){
				var nV = 0;
				if(!document.all){
				   nV = ObjectFromID("editor").scrollTop;
				   ObjectFromID("edit_line").scrollTop=nV;
				   setTimeout("autoScroll()",20);
				} 
			}
			
			function Save(){
				var path = "<?php echo $path; ?>"; 
				if(path.length === 0){
				   var target = prompt("Enter Save name", "tmp.txt");
					if (target != null) {
						path = base64_encode(encodeURI(target));
					} 
				}

				var data= new FormData();
				data.append('q', path); 
				data.append('v', base64_encode(ObjectFromID("editor").value )); 
				
				
				if(path.length > 0){
					AJAX(document.URL, "POST", data, function(response) {
						if(response=== '1'){
							ObjectFromID("unsaved_tag").classList.add("hidden");
						}
					},function(data) {
						location.reload();
					});
				}
			}
			
			if(!document.all){
				window.addEventListener("load",autoScroll,false);
			}
			window.addEventListener("keydown", window_keydown, false);
			ObjectFromID("editor").addEventListener("keydown", editor_keydown, false);			
			ObjectFromID("editor").addEventListener("keyup", editor_keyup ,false);
			ObjectFromID("editor").addEventListener("input", editor_input ,false);
			ObjectFromID("editor").value =base64_decode("<?php echo $value; ?>");
			editor_keyup();
		</script>
</body>
</html>