<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\PhotoSize;

/**
 * Start command
 */
class TranslateCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'translate';

    /**
     * @var string
     */
    protected $description = 'ترجمه';

    /**
     * @var string
     */
    protected $usage = '/translate';

    /**
     * @var string
     */
    protected $version = '1.1.0';

    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();

        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //Preparing Response
        $data = [
            'chat_id' => $chat_id,
        ];
        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            //reply to message id is applied by default
            //Force reply is applied by default so it can work with privacy on
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        //cache data from the tracking session if any
        $state = 0;
        if (isset($notes['state'])) {
            $state = $notes['state'];
        }

        $result = Request::emptyResponse();

        //State machine
        //Entrypoint of the machine state if given by the track
        //Every time a step is achieved the track is updated
        switch ($state) {
            case 0:
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text']         = 'یک کلمه وارد کنید';
                    $data['reply_markup'] = Keyboard::remove(['selective' => true]);

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['name'] = $text;
                $text          = '';

            // no break

            case 7:
                $this->conversation->update();
                Request::sendChatAction(['chat_id' => $chat_id, 'action' => 'typing']);
                $qwe    = $notes['name'];
                $pdo = DB::getPdo();
                $pdo->exec("set names utf8");
               $qwe=strtolower($qwe);
                require 'simple_html_dom.php';
                $curl = curl_init(); 
curl_setopt($curl, CURLOPT_URL, "http://dictionary.cambridge.org/us/dictionary/english/$qwe");  
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
$str = curl_exec($curl);  
curl_close($curl);  
$html= str_get_html($str); 
foreach($html->find('span.audio_play_button') as $e){
     $data['audio'] = $e->attr['data-src-mp3'];
    $result = Request::sendAudio($data);
}
$qwee=str_replace(" ","+",$qwe);
$curl = curl_init(); 
curl_setopt($curl, CURLOPT_URL, "https://www.google.com/search?q=$qwee&tbm=isch");  
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$str = curl_exec($curl);  
curl_close($curl);  

$dom= str_get_html($str); 
$i=0;


foreach($dom->find('img') as $element) {
	if($i==5){
		break;
	}
	$i++;
	 $data['photo'] = $element->src;
$result = Request::sendPhoto($data);

}
        





                

if(strlen($qwe) != mb_strlen($qwe, 'utf-8')) { 
        $sql = $pdo->prepare("SELECT `EnglishWord`,`PersianWord` FROM `EnglishPersianWordDatabase` WHERE `PersianWord` ='" . $qwe . "' ");
        $sql->execute();
  while ($row = $sql->fetch()){
        $data['text']= $row['EnglishWord'] ;
        Request::sendMessage($data);
    }
}else {
        
        $sql = $pdo->prepare("SELECT `PersianWord` FROM `EnglishPersianWordDatabase` WHERE `EnglishWord`='" . $qwe . "' ");
        $sql->execute();
       
    while ($row = $sql->fetch()){
        $data['text']= $row['PersianWord'] ;
        Request::sendMessage($data);
        
    }
    }
    

                




                $this->conversation->stop();
                break;
        }






    }
}
