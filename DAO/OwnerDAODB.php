<?php
    namespace DAO;

    use \Exception as Exception;
    use DAO\IOwnerDAO as IOwnerDAO;
    use Models\Owner as Owner; 
    use Models\Pet as Pet;   
    use DAO\Connection as Connection;

    class OwnerDAODB implements IOwnerDAO
    {

        private $connection;
        private $tableName = "Owners";


        public function GetAll(){
            try
            {
                $ownerList = array();

                $query = "SELECT * FROM ".$this->tableName;

                $this->connection = Connection::GetInstance();

                $resultSet = $this->connection->Execute($query);
                
                foreach ($resultSet as $owner)
                {                
                    $ownerNew=new Owner();
                    $ownerNew->setUserId($owner['user_id']);
                    $ownerNew->setFirstName($owner['firstName']);
                    $ownerNew->setLastName($owner['lastName']);
                    $ownerNew->setDni($owner['dni']);
                    $ownerNew->setEmail($owner['email']);
                    $ownerNew->setPassword($owner['pass']);
                    $ownerNew->setPhoneNumber($owner['phoneNumber']);
                    $ownerNew->setNotification($owner["notifications"]);

                    array_push($ownerList, $ownerNew);
                }

                return $ownerList;
            }
            catch(Exception $ex)
            {
                throw $ex;
            }

        }
        public function Add_Owner(Owner $newOwner){
            try
            {
                $query = "INSERT INTO ".$this->tableName." (firstName, lastName, dni, email, pass, phoneNumber) VALUES (:firstName, :lastName, :dni, :email, :pass, :phoneNumber);";


                $parameters['firstName']=$newOwner->getFirstName();
                $parameters['lastName']=$newOwner->getLastName();
                $parameters['dni']=$newOwner->getDni();
                $parameters['email']=$newOwner->getEmail();
                $parameters['pass']=$newOwner->getPassword();
                $parameters['phoneNumber']=$newOwner->getPhoneNumber();

                $this->connection = Connection::GetInstance();

                $this->connection->ExecuteNonQuery($query, $parameters);
            }
            catch(Exception $ex)
            {
                throw $ex;
            }

        }
        public function Remove($id){

        }


        public function SearchEmail($email){
           
                try{
        
                    $query = "SELECT * FROM ".$this->tableName." WHERE email = :email";
                    $parameters["email"] = $email;
        
                    $this->connection = connection::GetInstance();
                    $result = $this->connection->Execute($query, $parameters);

                    $owner = new Owner;

                if(isset($result[0])){
                    $row = $result[0];

                    $owner->setUserId($row["user_id"]);
                    $owner->setFirstName($row["firstName"]);
                    $owner->setLastName($row["lastName"]);
                    $owner->setDni($row["dni"]);
                    $owner->setEmail($row["email"]);
                    $owner->setPassword($row["pass"]);
                    $owner->setPhoneNumber($row["phoneNumber"]);
                    $owner->setNotification($row["notifications"]);
                    
                }else
                {
                    $owner=null;
                }
                return $owner;

                }catch(Exception $ex){
                    throw $ex;
                }

        }
        /*public function AddPet($id, Pet $petnew){

        }*/

        public function EditUser($owner){
            try
            {
                $query = "UPDATE ".$this->tableName." SET firstName= :firstName, lastName= :lastName, dni= :dni, email= :email, pass= :pass, phoneNumber= :phoneNumber WHERE user_id= :user_id;";

                $parameters['user_id']=$owner->getUserId();
                $parameters['firstName']=$owner->getFirstName();
                $parameters['lastName']=$owner->getLastName();
                $parameters['dni']=$owner->getDni();
                $parameters['email']=$owner->getEmail();
                $parameters['pass']=$owner->getPassword();
                $parameters['phoneNumber']=$owner->getPhoneNumber();

                $this->connection = Connection::GetInstance();

                $this->connection->ExecuteNonQuery($query, $parameters);
            }
            catch(Exception $ex)
            {
                throw $ex;
            }


        }

        public function EditNotification($editOwner){
            try
            {
                $query = "UPDATE ".$this->tableName." SET  notifications = :notifications
                WHERE user_id = ".$editOwner->getUserId();

                $parameters["notifications"] = $editOwner->getNotification();
                
                $this->connection = Connection::GetInstance();
                $this->connection->ExecuteNonQuery($query, $parameters);
            }
            catch(Exception $ex)
            {
                throw $ex;
            }
        }
        






    }



?>