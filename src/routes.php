<?php

use Slim\Http\Request;
use Slim\Http\Response;


class PasteBin{
	private $apiKey = '';
	private $userName="";
	private $userPassword="";
	private $userKey="";
	private $pastePrivate=1;

	public $options = array(
		"defaults"=>array(
			"userKeyCacheFile"=>"../cache/userKey",
			"defaultOptionsFile"=>"../cache/userOptions"
		)
	);

	public function __construct() {

		$this->loadOptions();
		$this->getUserKey();

	}

	public function loadOptions(){
		$options = json_decode( file_get_contents($this->options["defaults"]["defaultOptionsFile"]), true);

		$this->apiKey=$options['apiKey'];
		$this->userName=$options['userName'];
		$this->userPassword=$options['userPassword'];
		$this->userKey=$options['userKey'];
		$this->pastePrivate=$options['pastePrivate'];
	}


	public function POSTasUser($url){
		$api_dev_key 		= $this->apiKey;
		$api_user_name 		= urlencode($this->userName);
		$api_user_password 	= urlencode($this->userPassword);
		$ch					= curl_init($url);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,
			'api_dev_key='.$api_dev_key.'&api_user_name='.$api_user_name.'&api_user_password='.$api_user_password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);

		$response 		= curl_exec($ch);
		return $response;
	}

	public function getUserKey(){
		if(file_exists($this->options["defaults"]["userKeyCacheFile"])){
			$this->userKey = file_get_contents($this->options["defaults"]["userKeyCacheFile"]);
			return $this->userKey;
		}

		$response = $this->POSTasUser('https://pastebin.com/api/api_login.php');

		$this->userKey = $response;
		file_put_contents($this->options["defaults"]["userKeyCacheFile"], $this->userKey);

		return $this->userKey;
	}

	public function makeBin($name, $text, $options=array()){
		$api_dev_key 		    = $this->apiKey;
		$api_paste_code 		= urlencode($text); // your paste text
		$api_paste_private 		= $this->pastePrivate; // 0=public 1=unlisted 2=private
		$api_paste_name			= urlencode($name); // name or title of your paste
		$api_user_key 			= $this->userKey; // if an invalid or expired api_user_key is used, an error will spawn. If no api_user_key is used, a guest paste will be created

		$args=array(
			"api_user_key"=> 'api_user_key='.$api_user_key,
			"api_paste_private"=>'api_paste_private='.$api_paste_private,
			"api_paste_name"=>"api_paste_name=".$api_paste_name
		);

		if($options["key"]){ $args['api_user_key'] = 'api_user_key='.$options["key"]; }
		if($options["private"]){ $args['api_paste_private'] = "api_paste_private=".$options["private"]; }
		if($options["name"]){ $args['api_paste_name'] = "api_paste_name=".$options["name"]; }
		if($options["expire"]){ $args['api_paste_expire_date'] = "api_paste_expire_date=".$options["expire"]; }
		if($options["format"]){ $args['api_paste_format'] = 'api_paste_format='.$options["php"]; }

		$url 				= 'https://pastebin.com/api/api_post.php';
		$ch 				= curl_init($url);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_option=paste&api_dev_key='.$api_dev_key.'&api_paste_code='.$api_paste_code."&".join("&",$args));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);

		//echo $url.'api_option=paste&api_user_key='.$api_user_key.'&api_dev_key='.$api_dev_key.'&api_paste_code='."&".join("&",$args);

		$response  			= curl_exec($ch);
		return $response;
	}

	public function encode($filePath){
		$s = base64_encode(file_get_contents($filePath));
		if($s > 100000000){
			echo "FILE WAS TOO BIG JUST GIVING UP ON LIFE";
			exit;
		}
		return $s;
	}

	public function getBin($url,$name){
		$arrContextOptions=array(
			"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			),
		);
		file_put_contents($name, file_get_contents($url, false, stream_context_create($arrContextOptions)));
	}
}

// Routes

$app->get('/[{name}]', function (Request $request, Response $response, array $args) {

    $this->logger->info("|".join("|",
			array(
				"GET",
				$args['name'],
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['HTTP_X_FORWARDED_FOR']
			)
		)."|");


    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/', function (Request $request, Response $response, array $args) {


	$pb = new PasteBin();

	if($pb->options["secretMessage"] != "")
	{
		if ( $_POST["secret"] != $pb->options['secretMessage'] )
		{
			echo "There was a young man who said,\n\t'Though, it seems that I know that I know,\n\tbut what I would like to see is the I that knows me\n\twhen I know that I know that I know.'\n";
			return;
		}
	}

	$files = $request->getUploadedFiles();
	$id = uniqid("serveBin/");

	if($files["upload"]->getSize() > 100000000){
		$this->logger->info("|".join("|",
				array(
					"TOOLARGE",
					$files["upload"]->getSize(),
					$_SERVER['REMOTE_ADDR'],
					$_SERVER['HTTP_X_FORWARDED_FOR']
				)
			)."|");
		echo "FILE WAS TOO BIG JUST GIVING UP ON LIFE";
		exit;
	}

	$options=array();

	foreach(array("private","name","expire","format","key") as $x){
		if(array_key_exists($x, $_POST)){
			$options[$x] = $_POST[$x];
		}
	}

	$postID=$pb->makeBin($id, $files["upload"]->getStream()->__toString(), $options );



	$this->logger->info("|".join("|",
			array(
				"PID",
				$id,
				$files["upload"]->getSize(),
				$postID,
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['HTTP_X_FORWARDED_FOR']
			)
		)."|");



	echo "$postID\n";

});