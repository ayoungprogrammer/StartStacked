<?php
// the markdown parser and RSS feed builder.
require 'vendor/autoload.php';
require 'src/parser.php';
require 'src/db.php';

config(parse_ini_file('./config/local_config.ini',true));


function render($page, $params){
	echo phtml($page, $params, 'layout');
}

map('GET','/', function ($db) {
    render("main",[],false);
});


map('GET','/about', function ($db) {
    render("about",[],false);
});

//EDIT and SUBMIT is for stage
if(config('env')=='staging'){
	map('GET','/<page>&=edit',function($params, $db){

		$page = $params['page'];
		$title = preg_replace('/\_/',' ',$page);

		if ($db->article_exists($page)){
            $content = $db->get_article($page);
			
			render("edit",['page'=>$page,'title'=>$title,'body'=>$content,'desc'=>$page],false);
		}
		else {
			render("edit",['page'=>$page,'title'=>$title,'body'=>'','desc'=>$page],false);
		}
	});
	map('POST','/<page>&=submit',function($params,$db){
		$page = $params['page'];
		$content = $_POST['content'];
		$db->save_article($page,$content);
		redirect('./'.$page);
	});
}

//Get articles
map('GET','/<page>',function($params, $db){
	$page = $params['page'];

	$title = preg_replace('/\_/',' ',$page);

	if($db->article_exists($page)){
		$content = $db->get_article($page);
		$content = parse($content);
		$desc = getDesc($content,$title);
		$tags = getTags($content,$title);
		render(
			"page",
			['page'=>$page,'title'=>$title,'body'=>$content,'desc'=>$desc,'tags'=>$tags],
			false
		);
	}else {
		//Edit for stage

		if(config('env')=='staging'){
			echo $page;
			redirect('./'.$page.'&=edit');
		}else {
			error(404);
		}
		
	}

});

map(404,function(){
	render(
		"page",
		['page'=>'Error 404','title'=>'Error 404','body'=>'The page you are looking for could not be found.','desc'=>'','tags'=>''],
		false	
	);
});

$db = new DB(config('db'));

dispatch($db);
?>
