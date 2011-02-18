<?PHP

/* tweetPHP, PHP twitter Class By Tim Davies
	Requires cURL and SimpleXML (both standard) */


function makeClickableLinks($text) {

  $text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
    '<a href="\\1" target="_blank">\\1</a>', $text);
  $text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)',
    '\\1<a href="http://\\2" target="_blank">\\2</a>', $text);
  $text = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})',
    '<a href="mailto:\\1">\\1</a>', $text);
  
return $text;

}

class twitter {

	function __construct($user) {
		$this->user = $user;
	}
	
	
	//fetch tweets via cURL
	public function fetch_tweets($tweetlimit = 5, $charlimit = False, $limit= 42) {		
		$tweetshtml = '';
		$ch = curl_init();
		$target = 'http://www.twitter.com/statuses/user_timeline/'.$this->user.'.xml';
		curl_setopt($ch, CURLOPT_URL, $target);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		//Parsing the data
		$getweet = curl_exec($ch);
		
		$twitters = new SimpleXMLElement($getweet);

		//error reporting
		if(array_key_exists('@attributes', $twitters)){
			//die("<b>Fatal Error</b> Twitter is currently unavaliable");
		}
		
		
		//echo each tweet that was fetched.
		$counter = 0; 
		foreach ($twitters->status as $twit) { 
		
			$twiturl = 'http://twitter.com/'. $this->user .'/statuses/'. $twit->id;
   			$created = substr($twit->created_at,0,16);

  			if(++$counter > $tweetlimit) { 
      			break; 	
   			}else{	
   			
   				if($charlimit){
   					
			   		if(strlen($twit->text) > $charlimit) {
 						$tweet = substr($twit->text, 0 , $limit)."...";
			   		}else{
 						$tweet = substr($twit->text, 0 , $limit);
 					}

				}else{
					$tweet = $twit->text;
				}
				
				$tweet = makeClickableLinks($tweet);
				$tweetshtml .= '<li class="tweet">'. $tweet .'<br /><small>'.$created.'</small></li>';
				
   			}
		} 
		return $tweetshtml;
	}

	public function fetch_followers($limit) {

		$followershtml = '';
		$ch = curl_init();
		$target = 'http://twitter.com/statuses/followers/'.$this->user.'.xml';
		curl_setopt($ch, CURLOPT_URL, $target);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$getfollowers = curl_exec($ch);
		
		$followers = new SimpleXMLElement($getfollowers);
		
		$counter = 0;
		foreach ($followers->user as $follower) { 
			if ($counter >= $limit) { break; }
  			$followershtml .= '<a target="_top" href="http://www.twitter.com/'.$follower->screen_name.'"><img width=24 height=24 src="'. str_replace('normal','mini',$follower->profile_image_url) .'" alt="'. $follower->screen_name .'" title="'. $follower->screen_name .'"></a>';
			++$counter;
		}

		return $followershtml;
	}
}

?>