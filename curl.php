<?php
class curl{
	public $UTF;
	public $referer;
	public $xurl;

		public function getUrl($url,$post=null,$header=array(),$cookie=null){

			if ($url){
			  if( $curl = curl_init() ) {
				  $header[] = 'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
				   $header[] = 'Accept-Encoding:gzip, deflate, br';
				   $header[] = 'Accept-Language:ru,en-US;q=0.8,en;q=0.6,ja;q=0.4,it;q=0.2';
				   $header[] = 'Cache-Control:max-age=0';
				   $header[] = 'Content-Type:application/x-www-form-urlencoded';

				curl_setopt($curl, CURLOPT_HEADER, 0);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
				curl_setopt($curl, CURLOPT_ENCODING , "gzip");
				curl_setopt($curl, CURLOPT_USERAGENT , "Mozilla/8.0 (Windows NT 6.1; rv:31.0) Gecko/20100101 Firefox/31.0");
				curl_setopt($curl, CURLOPT_COOKIEJAR,  "cookie.txt");
				curl_setopt($curl, CURLOPT_COOKIEFILE, "cookie.txt");
				if (! file_exists('cookie.txt') || ! is_writable('cookie.txt'))
				{
					echo 'Cookie file missing or not writable. (create cookie.txt and chmod 777 it)';
					exit;
				}
				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($curl, CURLOPT_COOKIESESSION, false);
				curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				if($cookie)curl_setopt($curl, CURLOPT_COOKIE, $cookie);
				//echo $cookie;
				if ($this->referer) {
				     curl_setopt($curl, CURLOPT_REFERER, $this->referer);
				}
				if ($this->xurl==true) {
				    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With' => 'XMLHttpRequest'));
				}
				if (is_array($post)){
					if (count($post)>0){
						$str='';
						foreach($post as $key=>$value){
							$str.=$key.'='.urlencode($value).'&';
						}
				

					$str=substr($str, 0, -1);

					//var_dumP($post);
					curl_setopt($curl, CURLOPT_POST, true);	
					curl_setopt($curl, CURLOPT_POSTFIELDS,$str);
					}
				} 
				if ($this->UTF) {
				    $response = iconv('CP1251','UTF-8',curl_exec($curl));
				} else {
				   $response =curl_exec($curl);
				}

				curl_close($curl);
			  }
			return $response;
			}
		}

}
?>
