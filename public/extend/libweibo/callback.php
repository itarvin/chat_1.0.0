<?php
session_start();
include_once( 'config.php' );
include_once( 'saetv2.ex.class.php' );
$o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
if (isset($_REQUEST['code'])) {
	$keys = array();
	$keys['code'] = $_REQUEST['code'];
	$keys['redirect_uri'] = WB_CALLBACK_URL;
	try {
		$token = $o->getAccessToken( 'code', $keys ) ;
	} catch (OAuthException $e) {
	}
}
if ($token) {
	$_SESSION['token'] = $token;
	setcookie( 'weibojs_'.$o->client_id, http_build_query($token) );
?>
<?php
if($_session['userid']){
	echo "<h2>授权完成!<a href='https://www.itarvin.info/index.php/admin/door/weibologin'>欢迎进入系统！</a></h2><br />";
}else {
	echo "<h2>授权完成!<a href='https://www.itarvin.info/index.php/admin/admin/wbbind'>确认绑定当前账号！</a></h2><br />";
}
;?>
<?php
} else {
?>
<?php
if($_session['userid']){
	echo '<h1>授权失败！</h1>';
}else {
	echo '<h1>绑定失败了！</h1>';
}
;?>
<?php
}
?>
