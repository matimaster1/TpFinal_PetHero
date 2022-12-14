<?php
    namespace Controllers;

    use Helper\Validation as Validation;
    use \Exception as Exception;
    use DAO\KeeperDAODB as KeeperDAODB;
    use DAO\OwnerDAODB as OwnerDAODB;
    use DAO\ChatDAODB as ChatDAODB;
    use DAO\MessageDAODB as MessageDAODB;
    use Models\Keeper as Keeper;
    use Models\Owner as Owner;
    use Models\Chat as Chat;
    use Models\Message as Message;
    use Controllers\OwnerController as OwnerController;
    use Controllers\KeeperController as KeeperController;

    class ChatController
    {   
       
        private $ChatDAO;
        private $MessageDAO;
        private $OwnerDAO;
        private $KeeperDAO;
        private $OwnerController;
        private $KeeperController;

        public function __construct(){
            $this->ChatDAO = new ChatDAODB;
            $this->MessageDAO = new MessageDAODB;
            $this->OwnerDAO=new OwnerDAODB;
            $this->KeeperDAO = new KeeperDAODB;
            $this->OwnerController = new OwnerController;
            $this->KeeperController = new KeeperController;
        }

        public function ShowAddChatView(){
            Validation::ValidUser();
            try{
                if($_SESSION['loggedUser']->isKeeperOrOwner() == 0){
                    //vuelvo las notificaciones a 0 tuqui
                    $_SESSION['loggedUser']->setNotification(0);
                    $this->OwnerController->EditNotification($_SESSION['loggedUser']);
                    $chatsofowner=$this->ChatDAO->GetAllforOwner($_SESSION['loggedUser']);
                }else{
                    $_SESSION['loggedUser']->setNotification(0);
                    $this->KeeperController->EditNotification($_SESSION['loggedUser']);
                    $chatsofowner=$this->ChatDAO->GetAllforKeeper($_SESSION['loggedUser']);
                }
                require_once(VIEWS_PATH."user-chats.php");
            }catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }


        //message
        public function ChatView($chatId){
            Validation::ValidUser();
            try{
                $chatnew=$this->ChatDAO->GetOneChat($chatId);
               $messagechat=$this->MessageDAO->GetMessageforChat($chatnew);
                require_once(VIEWS_PATH."chat-view.php");
            }catch(Exception $ex )
            {
                echo $ex;
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function ChatBooking($keeperId){
            Validation::ValidUser();
            try{

                $flag=0;
                $chatsofowners=$this->ChatDAO->GetAllforOwner($_SESSION['loggedUser']);
                foreach($chatsofowners as $chats){
                    if($chats->getKeeperId()->getUserId()==$keeperId){
                        $this->ChatView($chats->getIdChat());
                        $flag=1;
                    }
                }
                if($flag==0){
                    $this->NewChat($keeperId);
                }
            }catch(Exception $ex )
            {
                echo $ex;
                require_once(VIEWS_PATH."error-page.php");
            }
        }

         //message
        public function MessageAdd($text, $chatId){
            Validation::ValidUser();
            try{
                $chatnew=$this->ChatDAO->GetOneChat($chatId);

                //sumarnotification
               if($_SESSION['loggedUser']->isKeeperOrOwner() == 0){
                $chatnew->getKeeperId()->setNotification($chatnew->getKeeperId()->getNotification()+1);
                $this->KeeperController->EditNotification($chatnew->getKeeperId());
               }else{
                $chatnew->getOwnerId()->setNotification($chatnew->getOwnerId()->getNotification()+1);
                $this->OwnerController->EditNotification($chatnew->getOwnerId());
               }
                

                $messageNew= new Message;
                $messageNew->setIdChat($chatnew);
                $messageNew->setDateTimer(date('Y-m-d H:i:s'));
                $messageNew->setUser($_SESSION['loggedUser']->isKeeperOrOwner());
                $messageNew->setTextMsg($text);

                $this->MessageDAO->Add($messageNew);
                $this->ChatView($chatId);
            }catch(Exception $ex )
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        public function NewChat($id_keeper){
            Validation::ValidUser();
            try{
                $flag=0;
                $chatsofowner=$this->ChatDAO->GetAllforOwner($_SESSION['loggedUser']);
                foreach ($chatsofowner as $chats){
                    if($chats->getKeeperId()->getUserId()==$id_keeper){
                        $flag=1;
                    }
                }
                if($flag==0){
                    $chatnew=new Chat;
                    $chatnew->setKeeperId($id_keeper);
                    $chatnew->setOwnerId($_SESSION['loggedUser']);

                    $this->ChatDAO->Add($chatnew);

                    //$this->ChatView();
                    $chatsofownerr=$this->ChatDAO->GetAllforOwner($_SESSION['loggedUser']);
                    foreach ($chatsofownerr as $chatss){
                        if($chatss->getKeeperId()->getUserId()==$id_keeper){
                            $this->ChatView($chatss->getIdChat());
                        }
                    }
                    
                }else{
                    echo "<script> confirm('Ya tienes un chat con este Cuidador');</script>";
                    $this->OwnerController->ShowHome();
                }
            }
            catch(Exception $ex)
            {
                require_once(VIEWS_PATH."error-page.php");
            }
        }

        
        
    }
?>