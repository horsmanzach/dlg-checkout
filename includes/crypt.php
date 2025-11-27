<?php

class Cryptor
{

  protected $method = 'aes-128-ctr'; // default cipher method if none supplied
  private $key;

  protected function iv_bytes()
  {
    return openssl_cipher_iv_length($this->method);
  }

  public function __construct($key = FALSE, $method = FALSE)
  {
    if(!$key) {
      $key = php_uname(); // default encryption key if none supplied
    }
    if(ctype_print($key)) {
      // convert ASCII keys to binary format
      $this->key = openssl_digest($key, 'SHA256', TRUE);
    } else {
      $this->key = $key;
    }
    if($method) {
      if(in_array(strtolower($method), openssl_get_cipher_methods())) {
        $this->method = $method;
      } else {
        die(__METHOD__ . ": unrecognised cipher method: {$method}");
      }
    }
  }

  public function encrypt($data)
  {
    $iv = openssl_random_pseudo_bytes($this->iv_bytes());
    return bin2hex($iv) . openssl_encrypt($data, $this->method, $this->key, 0, $iv);
  }

  // decrypt encrypted string
  public function decrypt($data)
  {
    $iv_strlen = 2  * $this->iv_bytes();
    if(preg_match("/^(.{" . $iv_strlen . "})(.+)$/", $data, $regs)) {
      list(, $iv, $crypted_string) = $regs;
      if(ctype_xdigit($iv) && strlen($iv) % 2 == 0) {
        return openssl_decrypt($crypted_string, $this->method, $this->key, 0, hex2bin($iv));
      }
    }
    return FALSE; // failed to decrypt
  }

}



if (isset($_POST['det']) && isset($_POST['k'])) {
	
	
	$cryptor = new Cryptor($_POST['k']);
	$value = $cryptor->decrypt($_POST['det']);
	
  echo '<div style="width:400px;margin:0 auto;">';
	foreach(maybe_unserialize($value) as $key=>$value) {
		
		echo $key." : ".$value."<br />";	
		
	}
	echo '</div>';
	exit;
}



if (isset($_GET['det'])) { ?>

<div style="width:400px;margin:0 auto; text-align: center;">
  <form method="post">

    <textarea name='det' style="width:400px;height:200px;" placeholder="Decryption text" row='10'></textarea>
    <br /><br />
    <input type='text' style="width:400px;height:50px;" name="k" placeholder="Decryption Key" />   
    <br /><br />
    <input type='submit' value="Submit"> 
  </form>  
</div>

  
<?php 
  
  exit;

}