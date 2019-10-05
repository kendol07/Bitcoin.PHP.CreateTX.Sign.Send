<?php
require "autoload.php";
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;



$privKeyFactory = new PrivateKeyFactory();

//the private key of where you will send the bitcoin  == 1AYTQY23cwrKnHDRgnQPAkqaHGEvAdXsZV
$privateKey = $privKeyFactory->fromWif('L5dDz1zhu7oVt9BjSD7SEeApdwenqzxJzLXM6L9v1dKhMKCKSChT');


// Create a spend transaction
$addressCreator = new AddressCreator();
$transaction = TransactionFactory::build();
    

//the address of where you will send the bitcoin
$addr='1AYTQY23cwrKnHDRgnQPAkqaHGEvAdXsZV';

//Extract unused transactions
$objeto=file_get_contents("https://blockchain.info/unspent?active=$addr&confirmations=0");

//if you have transactions available to spend
if($http_response_header[0]=='HTTP/1.1 200 OK')
{
      //json decode
      $json=json_decode($objeto,true);
 
 
      //data for entries
      /* Scroll through the data with a loop to access all the entries you want to check  example: [0],[1],[2],[3],[4] */
      
      //transaction without spending number 1
      $balance=$json['unspent_outputs'][0]['value'];
      $inputhash=$json['unspent_outputs'][0]['tx_hash_big_endian'];
      $vout=$json['unspent_outputs'][0]['tx_index'];
      
      //transaction without spending number 2
      $balance2=$json['unspent_outputs'][1]['value'];
      $inputhash2=$json['unspent_outputs'][1]['tx_hash_big_endian'];
      $vout2=$json['unspent_outputs'][1]['tx_index'];
      

 
     //total entries you have used for this transaction 
     $totalInput=2;

     //create input 1 and 2
     $transaction=$transaction->input($inputhash, $vout);
     $transaction=$transaction->input($inputhash2, $vout2);


    /* 
      Remember that you must calculate the total balance of the tickets without spending,
      subtract what you have sent, subtract a small amount that will be the mining fee 
      and the rest you must send it to your own return address 
    */

    //create output
    /*
     example total is 1234567 satoshi and send 12282 to 1JzWmuAwGAZ9dNRTdTFQYQxBNxcCojJnTv, there are 1222285 left,
     we subtract 573 which is used for the fee and the rest I send to my own wallet
     */
     
    $transaction=$transaction->payToAddress(1221712, $addressCreator->fromString('1AYTQY23cwrKnHDRgnQPAkqaHGEvAdXsZV'));
    $transaction=$transaction->payToAddress(12282, $addressCreator->fromString('1JzWmuAwGAZ9dNRTdTFQYQxBNxcCojJnTv'));
    $transaction=$transaction->get();


   /*here I put the total bitcoin sent in satoshi 1234567, the truth is that I don't know very well what this number is 
    for because no matter what number you put it works
   */
   $txOut = new TransactionOutput(
     1234567,
     ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash())
   );

   $signer = new Signer($transaction);

   //Sign transacction
   for($i=0;$i<$totalInput;$i++)
   {
      $input = $signer->input($i, $txOut);
      $input->sign($privateKey);
   }

   $signed = $signer->get();

   echo "txid: {$signed->getTxId()->getHex()}\n";
   echo "raw: {$signed->getHex()}\n";
   echo "input valid? " . ($input->verify() ? "true" : "false") . PHP_EOL; 

  /* $raw=$signed->getHex();
    send raw transaction API POST METHOD
    https://api.smartbit.com.au/v1/blockchain/pushtx 
  */

}

